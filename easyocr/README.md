# ANPR – How to Run (Number Plate Recognition)

This project uses **YOLO** and **EasyOCR** to detect license plates in video and read the text in real time. It can use your **Tapo camera RTSP stream**, a video file, or your webcam.

---

## 1. Prerequisites

- **Python 3.8+** (e.g. from python.org or your system)
- **Tapo camera** on the same network (if using RTSP), with **Camera Account** set in the Tapo app (Advanced Settings → Camera Account)
- **Model file** `anpr_best.pt` in this folder (already included)

---

## 2. Setup (one time)

### 2.1 Open a terminal in the project folder

```powershell
cd C:\xampp\tapo\easyocr
```

### 2.2 Create a virtual environment (recommended)

```powershell
python -m venv venv
.\venv\Scripts\activate
```

You should see `(venv)` at the start of the line.

### 2.3 Install dependencies

```powershell
pip install -r requirements.txt
```

This installs OpenCV, PyTorch, EasyOCR, Ultralytics, and NumPy. The first run can take several minutes.

### 2.4 Check the model

Make sure **`anpr_best.pt`** is in `C:\xampp\tapo\easyocr`. If it’s missing, add your trained YOLO license-plate model there.

---

## 3. How to run

### 3.1 Run from Tapo camera (RTSP) – default

The script is set to use this RTSP URL by default:

- **URL:** `rtsp://admin123:admin123@192.168.1.152:554/stream1`
- **Camera IP:** `192.168.1.152` (change in the script if your camera has a different IP)

**Command:**

```powershell
python number-plate-recognition.py
```

A window opens with the live camera view and detected plates. Press **q** to quit.

**Optional – save output to a file:**

```powershell
python number-plate-recognition.py --output anpr_rtsp.mp4
```

### 3.2 Run from a video file

```powershell
python number-plate-recognition.py --source acar.MOV --output anpr_output.mp4
```

Replace `acar.MOV` with your video path.

### 3.3 Run from webcam

```powershell
python number-plate-recognition.py --source 0
```

Use `0` for the default camera, or `1`, `2`, etc. for other cameras.

### 3.4 Run with a different RTSP URL

```powershell
python number-plate-recognition.py --source "rtsp://admin123:admin123@192.168.1.152:554/stream1"
```

Or change the **`TAPO_RTSP`** constant at the top of **`number-plate-recognition.py`** (around line 8) to your camera’s RTSP URL and IP.

---

## 4. Command-line options

| Option | Short | Default | Description |
|--------|--------|---------|-------------|
| `--source` | `-s` | Tapo RTSP URL | Video source: RTSP URL, file path, or `0` for webcam |
| `--output` | `-o` | None | Save output video to this file (e.g. `anpr_output.mp4`) |
| `--model` | `-m` | `anpr_best.pt` | Path to YOLO license plate model |
| `--no-display` | — | False | Run without showing the window (e.g. when recording only) |

**Examples:**

```powershell
python number-plate-recognition.py
python number-plate-recognition.py --source 0 --output webcam_out.mp4
python number-plate-recognition.py --source "rtsp://user:pass@192.168.1.100:554/stream1" --no-display --output headless.mp4
```

---

## 5. Changing the Tapo camera IP or credentials

1. Open **`number-plate-recognition.py`** in an editor.
2. Near the top (around line 8) find:
   ```python
   TAPO_RTSP = "rtsp://admin123:admin123@192.168.1.152:554/stream1"
   ```
3. Replace:
   - **IP:** `192.168.1.152` → your camera’s IP (from Tapo app → Device Info).
   - **Username/password:** `admin123:admin123` → the Camera Account you set in the Tapo app (Advanced Settings → Camera Account).
4. Save the file. The default run will then use the new URL.

---

## 6. Troubleshooting

- **“Model not found”**  
  Ensure **`anpr_best.pt`** is in `C:\xampp\tapo\easyocr` (same folder as `number-plate-recognition.py`).

- **“Cannot open video source” (RTSP)**  
  - Check camera IP and that the PC is on the same network.  
  - In the Tapo app: Advanced Settings → Camera Account created and credentials correct.  
  - Test the stream in VLC: Media → Open Network Stream → paste the RTSP URL.

- **Slow or laggy RTSP**  
  The script sets a small buffer for RTSP. If it’s still slow, check your Wi‑Fi/network or use a wired connection for the camera.

- **Using `anpr.py` instead**  
  You can run **`anpr.py`** the same way; pass the RTSP URL with `--source`:
  ```powershell
  python anpr.py --source "rtsp://admin123:admin123@192.168.1.152:554/stream1"
  ```

---

## 7. Quick reference

| Goal | Command |
|------|--------|
| Live from Tapo (default) | `python number-plate-recognition.py` |
| Tapo + save video | `python number-plate-recognition.py --output out.mp4` |
| Video file | `python number-plate-recognition.py -s acar.MOV -o out.mp4` |
| Webcam | `python number-plate-recognition.py -s 0` |
| Quit | Press **q** in the ANPR window |
