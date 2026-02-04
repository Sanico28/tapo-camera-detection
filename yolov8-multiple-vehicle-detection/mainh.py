import cv2
import pandas as pd
from ultralytics import YOLO
import cvzone
from tracker import Tracker
from pathlib import Path
from datetime import datetime
import subprocess
import os

# ------------------------------
# Configuration
# ------------------------------
CAPACITY_TONS = 20.0
VEHICLE_WEIGHTS_TONS = {
    'car': 1.5,
    'bus': 12.0,
    'truck': 15.0,
}

# Entry and exit LINES AS VERTICAL (Y-axis) LINES -> constant X positions
# Two entry lines (left and right) and two exit lines (left and right)
# Vehicles moving RIGHT: cross LEFT_ENTRY -> RIGHT_EXIT
# Vehicles moving LEFT: cross RIGHT_ENTRY -> LEFT_EXIT
LEFT_ENTRY_LINE_X = 260  # left entry line (pixels, X axis)
RIGHT_ENTRY_LINE_X = 790  # right entry line (pixels, X axis)
LEFT_EXIT_LINE_X = 270   # left exit line (pixels, X axis)
RIGHT_EXIT_LINE_X = 780   # right exit line (pixels, X axis)
LINE_TOLERANCE = 8
STALE_FRAMES_TO_EVICT = 120  # frames after last seen to evict lost IDs

# Process every Nth frame (used for speed calculation timing)
FRAME_STRIDE = 3

# Approximate scale: how many meters per pixel along Y.
# You MUST tune this based on your camera / scene.
METERS_PER_PIXEL = 0.1  # example: 0.1 m per pixel (10 cm/pixel)

# Speed limit capture
SPEED_LIMIT_KPH = 20.0
VIOLATIONS_DIR = Path("speed_violations")  # folder will be created next to this script when you run it
VIOLATION_CROP_PADDING_PX = 10

# HLS output for web streaming
# Path to Laravel public/stream folder for detection output
HLS_OUTPUT_DIR = Path(r"C:\xampp\tapo\public\stream")
HLS_OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
HLS_PLAYLIST = str(HLS_OUTPUT_DIR / "detection.m3u8")
HLS_SEGMENT = str(HLS_OUTPUT_DIR / "detection%03d.ts")

# ------------------------------
# Optional: Mouse position debug
# ------------------------------
def RGB(event, x, y, flags, param):
    if event == cv2.EVENT_MOUSEMOVE:
        point = [x, y]
        # print(point)  # Uncomment for debugging


# ------------------------------
# Setup
# ------------------------------
model = YOLO('yolov8s.pt')
# Read video from Tapo HLS stream served by Laravel
# Make sure:
# 1) FFmpeg is running and writing to tapo/public/stream/index.m3u8
# 2) php artisan serve is running on http://127.0.0.1:8000
cap = cv2.VideoCapture('http://127.0.0.1:8000/stream/index.m3u8')

# FPS from video (fallback to 30 if not available)
FPS = cap.get(cv2.CAP_PROP_FPS) or 30.0

# Output FPS for the YOLO (detection) stream.
# We only process every FRAME_STRIDE frame, so the effective output FPS is lower.
OUTPUT_FPS = max(1, int(round(FPS / FRAME_STRIDE)))

# Create output folder for speeding vehicles
VIOLATIONS_DIR.mkdir(parents=True, exist_ok=True)

with open("coco.txt", "r") as my_file:
    class_list = my_file.read().split("\n")

cv2.namedWindow('RGB')
cv2.setMouseCallback('RGB', RGB)

# Setup FFmpeg for HLS output
# FFmpeg command to pipe frames and create HLS stream
ffmpeg_path = r"C:\xampp\tapo\ffmpeg-2026-01-22-git-4561fc5e48-full_build\ffmpeg-2026-01-22-git-4561fc5e48-full_build\bin\ffmpeg.exe"
ffmpeg_command = [
    ffmpeg_path,
    '-y',  # Overwrite output files
    '-loglevel', 'error',
    '-f', 'rawvideo',
    '-vcodec', 'rawvideo',
    '-s', '1020x500',  # Match frame size
    '-pix_fmt', 'bgr24',
    '-r', str(OUTPUT_FPS),  # Frame rate
    '-i', '-',  # Input from stdin
    '-c:v', 'libx264',
    '-preset', 'ultrafast',
    '-tune', 'zerolatency',
    '-f', 'hls',
    '-hls_time', '2',
    '-hls_list_size', '3',
    '-hls_flags', 'delete_segments',
    '-hls_segment_filename', HLS_SEGMENT,
    HLS_PLAYLIST
]

# Start FFmpeg process
ffmpeg_process = subprocess.Popen(
    ffmpeg_command,
    stdin=subprocess.PIPE,
    stdout=subprocess.DEVNULL,
    stderr=subprocess.DEVNULL
)
print(f"✓ FFmpeg HLS output started: {HLS_PLAYLIST}")

# Use separate trackers per class for stable IDs
car_tracker = Tracker()
bus_tracker = Tracker()
truck_tracker = Tracker()

# Runtime state
on_bridge_ids = set()  # set of tuples like ('car', id)
id_to_last_cx = {}     # map of ('car', id) -> last center x (for crossings)
id_to_last_cy = {}     # map of ('car', id) -> last center y (for vertical speed)
id_to_direction = {}   # map of ('car', id) -> 'left' or 'right' (movement direction)
current_load_tons = 0.0

# Track last seen frame for each key to evict stale IDs
id_to_last_seen_frame = {}

# Per-object horizontal speed (pixels/second) along X axis (for left/right movement)
id_to_speed_x = {}

# Last measured speeds at each EXIT line (km/h), for display near the lines
last_exit_speed_left_kmh = 0.0
last_exit_speed_right_kmh = 0.0

# Show "exit speed" on the car for N frames after crossing exit line.
# Note: because we skip frames (FRAME_STRIDE), keep this a bit larger.
SHOW_EXIT_SPEED_FOR_FRAMES = 120
id_to_exit_speed_kmh = {}        # map of ('car', id) -> last exit speed in km/h
id_to_exit_speed_until = {}      # map of ('car', id) -> frame_idx until we show it

# Save only once per tracked object
saved_speed_violations = set()   # set of keys (cls_name, obj_id) already saved

# Diagnostics (optional)
entries = {'car': 0, 'bus': 0, 'truck': 0}
exits = {'car': 0, 'bus': 0, 'truck': 0}

# Process frames
frame_idx = 0
while True:
    ret, frame = cap.read()
    if not ret:
        break

    frame_idx += 1
    # Process every Nth frame to reduce load
    if frame_idx % FRAME_STRIDE != 0:
        continue

    frame = cv2.resize(frame, (1020, 500))

    # Run detection
    results = model.predict(frame)
    detections = results[0].boxes.data
    px = pd.DataFrame(detections).astype("float")

    # Collect detections by class
    cars, buses, trucks = [], [], []
    for _, row in px.iterrows():
        x1 = int(row[0])
        y1 = int(row[1])
        x2 = int(row[2])
        y2 = int(row[3])
        cls_idx = int(row[5])
        cls_name = class_list[cls_idx] if 0 <= cls_idx < len(class_list) else ''

        if 'car' in cls_name:
            cars.append([x1, y1, x2, y2])
        elif 'bus' in cls_name:
            buses.append([x1, y1, x2, y2])
        elif 'truck' in cls_name:
            trucks.append([x1, y1, x2, y2])

    # Update trackers
    car_boxes = car_tracker.update(cars)
    bus_boxes = bus_tracker.update(buses)
    truck_boxes = truck_tracker.update(trucks)

    # Determine barrier state
    barrier_closed = current_load_tons >= CAPACITY_TONS

    # Draw entry/exit lines as VERTICAL lines (color reflects barrier state)
    entry_color = (0, 0, 255) if barrier_closed else (0, 255, 0)
    exit_color = (0, 0, 255)
    h, w = frame.shape[:2]
    # Make lines thicker so they are easier to see
    # Draw all 4 lines: left entry, right entry, left exit, right exit
    cv2.line(frame, (LEFT_ENTRY_LINE_X, 0), (LEFT_ENTRY_LINE_X, h - 1), entry_color, 4)
    cv2.line(frame, (RIGHT_ENTRY_LINE_X, 0), (RIGHT_ENTRY_LINE_X, h - 1), entry_color, 4)
    cv2.line(frame, (LEFT_EXIT_LINE_X, 0), (LEFT_EXIT_LINE_X, h - 1), exit_color, 4)
    cv2.line(frame, (RIGHT_EXIT_LINE_X, 0), (RIGHT_EXIT_LINE_X, h - 1), exit_color, 4)

    # Show latest exit speeds (km/h) ON the red EXIT lines (not on the edges)
    exit_label_y = 40
    exit_label_half_width = 70  # used to center the label on the line

    left_exit_label_x = LEFT_EXIT_LINE_X - exit_label_half_width
    right_exit_label_x = RIGHT_EXIT_LINE_X - exit_label_half_width

    # keep inside the frame if user sets lines near the edges
    left_exit_label_x = max(0, min(w - 2 * exit_label_half_width, left_exit_label_x))
    right_exit_label_x = max(0, min(w - 2 * exit_label_half_width, right_exit_label_x))

    cvzone.putTextRect(
        frame,
        f'{last_exit_speed_left_kmh:.1f} km/h',
        (left_exit_label_x, exit_label_y),
        1,
        1,
        colorR=(0, 0, 255),
        colorT=(255, 255, 255)
    )
    cvzone.putTextRect(
        frame,
        f'{last_exit_speed_right_kmh:.1f} km/h',
        (right_exit_label_x, exit_label_y),
        1,
        1,
        colorR=(0, 0, 255),
        colorT=(255, 255, 255)
    )

    # Helper to process a single detection list
    def process_boxes(boxes, cls_name):
        global current_load_tons
        global last_exit_speed_left_kmh, last_exit_speed_right_kmh
        for bbox in boxes:
            x1, y1, x2, y2, obj_id = bbox
            cx = int((x1 + x2) / 2)
            cy = int((y1 + y2) / 2)

            key = (cls_name, obj_id)
            prev_cx = id_to_last_cx.get(key)
            prev_cy = id_to_last_cy.get(key)

            # Detect crossings using previous and current center X positions (vertical lines)
            if prev_cx is not None:
                # Determine direction: moving right (cx increasing) or left (cx decreasing)
                direction = id_to_direction.get(key)
                if direction is None:
                    # First time seeing this vehicle, determine direction from movement
                    if cx > prev_cx:
                        direction = 'right'
                    elif cx < prev_cx:
                        direction = 'left'
                    else:
                        direction = 'right'  # default
                    id_to_direction[key] = direction
                else:
                    # Update direction if vehicle changes direction
                    if cx > prev_cx + 5:  # threshold to avoid noise
                        direction = 'right'
                        id_to_direction[key] = direction
                    elif cx < prev_cx - 5:
                        direction = 'left'
                        id_to_direction[key] = direction

                # Detect crossings based on direction
                # Moving RIGHT: crosses LEFT_ENTRY -> RIGHT_EXIT
                # Moving LEFT: crosses RIGHT_ENTRY -> LEFT_EXIT
                crossed_entry = False
                crossed_exit = False
                
                if direction == 'right':
                    # Moving right: check left entry and right exit
                    crossed_entry = (prev_cx < LEFT_ENTRY_LINE_X - LINE_TOLERANCE) and (cx >= LEFT_ENTRY_LINE_X + LINE_TOLERANCE)
                    crossed_exit = (prev_cx < RIGHT_EXIT_LINE_X - LINE_TOLERANCE) and (cx >= RIGHT_EXIT_LINE_X + LINE_TOLERANCE)
                else:  # direction == 'left'
                    # Moving left: check right entry and left exit
                    crossed_entry = (prev_cx > RIGHT_ENTRY_LINE_X + LINE_TOLERANCE) and (cx <= RIGHT_ENTRY_LINE_X - LINE_TOLERANCE)
                    crossed_exit = (prev_cx > LEFT_EXIT_LINE_X + LINE_TOLERANCE) and (cx <= LEFT_EXIT_LINE_X - LINE_TOLERANCE)

                # Calculate X-AXIS speed (horizontal movement for left/right)
                # dx = change in X position (positive = moving right, negative = moving left)
                dx = abs(cx - prev_cx)  # Use absolute value for speed magnitude
                dt = FRAME_STRIDE / FPS if FPS > 0 else 0
                if dt > 0:
                    v_x = dx / dt  # Horizontal speed in pixels per second
                    id_to_speed_x[key] = v_x

                # Handle entry
                if crossed_entry and key not in on_bridge_ids:
                    weight = VEHICLE_WEIGHTS_TONS.get(cls_name, 0.0)
                    # Only allow entry if this vehicle would NOT exceed capacity
                    if current_load_tons + weight <= CAPACITY_TONS:
                        on_bridge_ids.add(key)
                        current_load_tons += weight
                        entries[cls_name] += 1
                    # else: barrier is effectively closing; do not admit

                # Handle exit
                if crossed_exit:
                    # Capture speed when crossing an EXIT line (in km/h),
                    # even if the vehicle did not cross the entry line first.
                    v_x = id_to_speed_x.get(key)
                    if v_x is not None:
                        v_mps = v_x * METERS_PER_PIXEL
                        v_kmh = v_mps * 3.6

                        # Decide which EXIT line was used based on direction
                        if direction == 'right':
                            # Moving right exits at RIGHT_EXIT_LINE_X
                            last_exit_speed_right_kmh = v_kmh
                        else:  # direction == 'left'
                            # Moving left exits at LEFT_EXIT_LINE_X
                            last_exit_speed_left_kmh = v_kmh

                        # Show exit speed on the car for a short time
                        id_to_exit_speed_kmh[key] = v_kmh
                        id_to_exit_speed_until[key] = frame_idx + SHOW_EXIT_SPEED_FOR_FRAMES

                        # Save speeding vehicle crop when it crosses the EXIT line
                        if v_kmh >= SPEED_LIMIT_KPH and key not in saved_speed_violations:
                            pad = VIOLATION_CROP_PADDING_PX
                            h_img, w_img = frame.shape[:2]
                            x1c = max(0, x1 - pad)
                            y1c = max(0, y1 - pad)
                            x2c = min(w_img - 1, x2 + pad)
                            y2c = min(h_img - 1, y2 + pad)

                            crop = frame[y1c:y2c, x1c:x2c]
                            if crop.size > 0:
                                ts = datetime.now().strftime("%Y%m%d_%H%M%S_%f")
                                sp = f"{v_kmh:.1f}".replace(".", "p")
                                fname = f"{ts}_frame{frame_idx}_{cls_name}_{obj_id}_{direction}_{sp}kmh.jpg"
                                cv2.imwrite(str(VIOLATIONS_DIR / fname), crop)
                                saved_speed_violations.add(key)

                    # Bridge accounting only if it was admitted (on_bridge)
                    if key in on_bridge_ids:
                        weight = VEHICLE_WEIGHTS_TONS.get(cls_name, 0.0)
                        if weight > 0:
                            current_load_tons = max(0.0, current_load_tons - weight)
                        on_bridge_ids.discard(key)
                        exits[cls_name] += 1

            # Update last seen X/Y and last seen frame
            id_to_last_cx[key] = cx
            id_to_last_cy[key] = cy
            id_to_last_seen_frame[key] = frame_idx

            # Draw bbox and ID label
            cv2.rectangle(frame, (x1, y1), (x2, y2), (255, 0, 255), 2)

            # Show current km/h on the CAR all the time,
            # and highlight "EXIT" speed for a short time after crossing an exit line.
            v_x = id_to_speed_x.get(key)
            label_speed = ''
            v_kmh_inst = None
            if v_x is not None:
                v_mps_inst = v_x * METERS_PER_PIXEL
                v_kmh_inst = v_mps_inst * 3.6
                label_speed = f'{v_kmh_inst:.1f} km/h'

                # LIVE speeding capture (not only at exit line)
                if v_kmh_inst >= SPEED_LIMIT_KPH and key not in saved_speed_violations:
                    pad = VIOLATION_CROP_PADDING_PX
                    h_img, w_img = frame.shape[:2]
                    x1c = max(0, x1 - pad)
                    y1c = max(0, y1 - pad)
                    x2c = min(w_img - 1, x2 + pad)
                    y2c = min(h_img - 1, y2 + pad)

                    crop = frame[y1c:y2c, x1c:x2c]
                    if crop.size > 0:
                        ts = datetime.now().strftime("%Y%m%d_%H%M%S_%f")
                        sp = f"{v_kmh_inst:.1f}".replace(".", "p")
                        fname = f"{ts}_frame{frame_idx}_{cls_name}_{obj_id}_{direction}_{sp}kmh_live.jpg"
                        cv2.imwrite(str(VIOLATIONS_DIR / fname), crop)
                        saved_speed_violations.add(key)

            exit_until = id_to_exit_speed_until.get(key, -1)
            exit_kmh = id_to_exit_speed_kmh.get(key)

            if exit_kmh is not None and frame_idx <= exit_until:
                # Show exit-highlighted speed
                label = f'{cls_name} #{obj_id} EXIT {exit_kmh:.1f} km/h'
            elif label_speed:
                # Show live instantaneous speed
                label = f'{cls_name} #{obj_id} {label_speed}'
            else:
                # Fallback: ID only
                label = f'{cls_name} #{obj_id}'

            # Keep label visible even when the box is near the right edge
            label_x = x1
            if label_x > w - 260:
                label_x = max(0, x2 - 260)

            cvzone.putTextRect(frame, label, (label_x, max(0, y1 - 10)), 1, 1)
            # Make the yellow dot bigger so it's more visible
            cv2.circle(frame, (cx, cy), 6, (0, 255, 255), -1)

    # Process detections by class
    process_boxes(car_boxes, 'car')
    process_boxes(bus_boxes, 'bus')
    process_boxes(truck_boxes, 'truck')

    # Evict stale IDs that have not been seen for a while
    to_evict = []
    for key in list(on_bridge_ids):
        last_seen = id_to_last_seen_frame.get(key, 0)
        if frame_idx - last_seen > STALE_FRAMES_TO_EVICT:
            to_evict.append(key)
    for key in to_evict:
        cls_name, _ = key
        weight = VEHICLE_WEIGHTS_TONS.get(cls_name, 0.0)
        if weight > 0:
            current_load_tons = max(0.0, current_load_tons - weight)
        on_bridge_ids.discard(key)
        # Clean up tracking dictionaries
        id_to_last_cx.pop(key, None)
        id_to_last_cy.pop(key, None)
        id_to_direction.pop(key, None)
        id_to_speed_x.pop(key, None)
        id_to_exit_speed_kmh.pop(key, None)
        id_to_exit_speed_until.pop(key, None)
        saved_speed_violations.discard(key)

    # Recompute barrier state after any changes
    barrier_closed = current_load_tons >= CAPACITY_TONS

    # Overlay current load and barrier status
    status_text = 'CLOSED' if barrier_closed else 'OPEN'
    status_color = (0, 0, 255) if barrier_closed else (0, 200, 0)
    cvzone.putTextRect(
        frame,
        f'Bridge: {status_text} | Load: {current_load_tons:.1f}/{CAPACITY_TONS:.1f} tons',
        (10, 30),
        1,
        2,
        colorR=status_color,
        colorT=(255, 255, 255)
    )

    # Visual barrier at both entry lines when closed (vertical red bars)
    if barrier_closed:
        h, w = frame.shape[:2]
        # Left entry barrier
        cv2.rectangle(
            frame,
            (max(0, LEFT_ENTRY_LINE_X - 6), 0),
            (min(w - 1, LEFT_ENTRY_LINE_X + 6), h),
            (0, 0, 255),
            -1
        )
        # Right entry barrier
        cv2.rectangle(
            frame,
            (max(0, RIGHT_ENTRY_LINE_X - 6), 0),
            (min(w - 1, RIGHT_ENTRY_LINE_X + 6), h),
            (0, 0, 255),
            -1
        )
        cvzone.putTextRect(frame, 'NO ENTRY - OVER CAPACITY', (10, 30 + 35), 1, 1, colorR=(0, 0, 255))

    # Show frame locally (optional - comment out if you don't want local window)
    cv2.imshow("RGB", frame)
    
    # Write processed frame to FFmpeg pipe for HLS streaming
    try:
        ffmpeg_process.stdin.write(frame.tobytes())
        ffmpeg_process.stdin.flush()
    except (BrokenPipeError, OSError):
        print("FFmpeg pipe closed, continuing without HLS output...")
    
    if cv2.waitKey(1) & 0xFF == 27:
        break

# Final stats
print(f"Entries: cars={entries['car']}, buses={entries['bus']}, trucks={entries['truck']}")
print(f"Exits:   cars={exits['car']}, buses={exits['bus']}, trucks={exits['truck']}")
print(f'Final on-bridge count: {len(on_bridge_ids)} | Load: {current_load_tons:.1f} tons')

cap.release()
cv2.destroyAllWindows()

# Close FFmpeg process
if ffmpeg_process:
    try:
        ffmpeg_process.stdin.close()
        ffmpeg_process.wait(timeout=5)
    except:
        ffmpeg_process.terminate()
    print("✓ FFmpeg process closed")