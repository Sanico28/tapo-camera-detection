# ANPR – Automatic Number Plate Recognition

YOLO + EasyOCR for license plate detection and text extraction.

## Setup

1. **Create virtual environment** (recommended):

   ```powershell
   cd C:\xampp\tapo\easyocr
   python -m venv venv
   .\venv\Scripts\activate
   ```

2. **Install dependencies**:

   ```powershell
   pip install -r requirements.txt
   ```

3. **Model**: Place `anpr_best.pt` in this folder (already done).

## Run

**Video file**:

```powershell
python anpr.py --source videos/acar.mp4 --output anpr_output.mp4
```

**Webcam** (camera 0):

```powershell
python anpr.py --source 0
```

**HLS stream** (e.g. Tapo camera):

```powershell
python anpr.py --source "http://127.0.0.1:8000/stream/index.m3u8"
```

### Options

- `--source`, `-s`: Video file path, stream URL, or `0` for webcam
- `--output`, `-o`: Save output video to file
- `--model`, `-m`: Path to YOLO model (default: `anpr_best.pt`)
- `--no-display`: Run without display window
