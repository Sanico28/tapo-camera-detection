import cv2
import pandas as pd
from ultralytics import YOLO
import cvzone
from tracker import Tracker

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
# Tune these X values to where you want the vertical lines on screen
ENTRY_LINE_X = 350   # entry vertical line (pixels, X axis)
EXIT_LINE_X = 650    # exit  vertical line (pixels, X axis)
LINE_TOLERANCE = 8
STALE_FRAMES_TO_EVICT = 120  # frames after last seen to evict lost IDs

# Process every Nth frame (used for speed calculation timing)
FRAME_STRIDE = 3

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
cap = cv2.VideoCapture('tf.mp4')

# FPS from video (fallback to 30 if not available)
FPS = cap.get(cv2.CAP_PROP_FPS) or 30.0

with open("coco.txt", "r") as my_file:
    class_list = my_file.read().split("\n")

cv2.namedWindow('RGB')
cv2.setMouseCallback('RGB', RGB)

# Use separate trackers per class for stable IDs
car_tracker = Tracker()
bus_tracker = Tracker()
truck_tracker = Tracker()

# Runtime state
on_bridge_ids = set()  # set of tuples like ('car', id)
id_to_last_cx = {}     # map of ('car', id) -> last center x (for crossings)
id_to_last_cy = {}     # map of ('car', id) -> last center y (for vertical speed)
current_load_tons = 0.0

# Track last seen frame for each key to evict stale IDs
id_to_last_seen_frame = {}

# Per-object vertical speed (pixels/second) along Y axis
id_to_speed_y = {}

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
    cv2.line(frame, (ENTRY_LINE_X, 0), (ENTRY_LINE_X, h - 1), entry_color, 2)
    cv2.line(frame, (EXIT_LINE_X, 0), (EXIT_LINE_X, h - 1), exit_color, 2)

    # Helper to process a single detection list
    def process_boxes(boxes, cls_name):
        global current_load_tons
        for bbox in boxes:
            x1, y1, x2, y2, obj_id = bbox
            cx = int((x1 + x2) / 2)
            cy = int((y1 + y2) / 2)

            key = (cls_name, obj_id)
            prev_cx = id_to_last_cx.get(key)
            prev_cy = id_to_last_cy.get(key)

            # Detect crossings using previous and current center X positions (vertical lines)
            if prev_cx is not None:
                crossed_entry = (prev_cx < ENTRY_LINE_X - LINE_TOLERANCE) and (cx >= ENTRY_LINE_X + LINE_TOLERANCE)
                crossed_exit = (prev_cx < EXIT_LINE_X - LINE_TOLERANCE) and (cx >= EXIT_LINE_X + LINE_TOLERANCE)

                # Calculate Y-AXIS speed (vertical movement only, NOT horizontal/X-axis)
                # dy = change in Y position (positive = moving down, negative = moving up)
                dy = cy - prev_cy if prev_cy is not None else 0  # ONLY using Y coordinate, NOT X
                dt = FRAME_STRIDE / FPS if FPS > 0 else 0
                if dt > 0:
                    v_y = dy / dt  # Vertical speed in pixels per second
                    id_to_speed_y[key] = v_y

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
                if crossed_exit and key in on_bridge_ids:
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

            # Display Y-axis speed (vertical movement only)
            v_y = id_to_speed_y.get(key)
            if v_y is not None:
                # vy = vertical speed along Y-axis (positive = down, negative = up)
                label = f'{cls_name} #{obj_id} vy={v_y:.1f}px/s'
            else:
                label = f'{cls_name} #{obj_id}'

            cvzone.putTextRect(frame, label, (x1, max(0, y1 - 10)), 1, 1)
            cv2.circle(frame, (cx, cy), 3, (0, 255, 255), -1)

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

    # Visual barrier at the entry line when closed (vertical red bar)
    if barrier_closed:
        h, w = frame.shape[:2]
        cv2.rectangle(
            frame,
            (max(0, ENTRY_LINE_X - 6), 0),
            (min(w - 1, ENTRY_LINE_X + 6), h),
            (0, 0, 255),
            -1
        )
        cvzone.putTextRect(frame, 'NO ENTRY - OVER CAPACITY', (10, 30 + 35), 1, 1, colorR=(0, 0, 255))

    # Show frame
    cv2.imshow("RGB", frame)
    if cv2.waitKey(1) & 0xFF == 27:
        break

# Final stats
print(f"Entries: cars={entries['car']}, buses={entries['bus']}, trucks={entries['truck']}")
print(f"Exits:   cars={exits['car']}, buses={exits['bus']}, trucks={exits['truck']}")
print(f'Final on-bridge count: {len(on_bridge_ids)} | Load: {current_load_tons:.1f} tons')

cap.release()
cv2.destroyAllWindows()