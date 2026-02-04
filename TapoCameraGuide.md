## Tapo C310 Camera – How to Run

This project is configured to show your Tapo C310 camera in the `/camera` page of your Laravel app.

Follow these steps each time you want to use it:

---

### 1. Make sure the camera is set up

- **Same network**: Your Windows PC and the Tapo C310 camera must be on the same LAN/Wi‑Fi.
- **Camera IP**: In the Tapo app, open your C310 → tap the **settings (⚙)** → **Device Info** and confirm the IP.
  - Your current IP is **`192.168.1.52`** (also stored in `.env` as `TAPO_IP`).
- **Camera Account**:
  - In the Tapo app: C310 → **⚙ Settings** → **Advanced Settings** → **Camera Account**.
  - Create a username and password (you are using `admin123` / `admin123`).

Test RTSP with VLC on your PC:

```text
rtsp://admin123:admin123@192.168.1.52:554/stream1
```

If VLC shows video, the camera and RTSP are working.

---

### 2. Start FFmpeg (RTSP → HLS)

This converts the RTSP stream to HLS files that the browser can play.

1. Ensure the output folder exists:

```powershell
mkdir D:\tapo\tapo\public\stream
```

2. Start FFmpeg from PowerShell (keep this window **open** while you view the camera):

**Method 1: Using the call operator (&) - RECOMMENDED**
```powershell
& "D:\tapo\tapo\ffmpeg-2026-01-22-git-4561fc5e48-full_build\ffmpeg-2026-01-22-git-4561fc5e48-full_build\bin\ffmpeg.exe" -rtsp_transport tcp -i "rtsp://admin123:admin123@192.168.1.52:554/stream1" -fflags +genpts -flags -global_header -hls_time 2 -hls_list_size 3 -hls_flags delete_segments -vcodec copy -acodec aac -f hls "D:\tapo\tapo\public\stream\index.m3u8"
```

**Method 2: For your specific IP (192.168.1.152)**
```powershell
& "D:\tapo\tapo\ffmpeg-2026-01-22-git-4561fc5e48-full_build\ffmpeg-2026-01-22-git-4561fc5e48-full_build\bin\ffmpeg.exe" -rtsp_transport tcp -i "rtsp://admin123:admin123@192.168.1.152:554/stream1" -fflags +genpts -flags -global_header -hls_time 2 -hls_list_size 3 -hls_flags delete_segments -vcodec copy -acodec aac -f hls "D:\tapo\tapo\public\stream\index.m3u8"
```

**Method 3: Using Command Prompt (cmd) if PowerShell gives trouble**
```cmd
"D:\tapo\tapo\ffmpeg-2026-01-22-git-4561fc5e48-full_build\ffmpeg-2026-01-22-git-4561fc5e48-full_build\bin\ffmpeg.exe" -rtsp_transport tcp -i "rtsp://admin123:admin123@192.168.1.152:554/stream1" -fflags +genpts -flags -global_header -hls_time 2 -hls_list_size 3 -hls_flags delete_segments -vcodec copy -acodec aac -f hls "D:\tapo\tapo\public\stream\index.m3u8"
```

You should see the PowerShell window stay busy (not return to `D:\>`).  
In `D:\tapo\tapo\public\stream` you should see `index.m3u8` and small `.ts` files.

---

### 3. Start Laravel and open the camera page

1. From another terminal in `D:\tapo\tapo`:

```powershell
php artisan serve
```

2. Open your browser and go to:

```text
http://127.0.0.1:8000
```

3. Log in (Laravel Breeze auth).
4. Visit the camera page:

```text
http://127.0.0.1:8000/camera
```

You should see:

- Camera information with IP `192.168.1.52`.
- The RTSP URL text.
- A **Live View (browser)** player showing the Tapo C310 video (if FFmpeg is running).

---

### 4. Run YOLOv8 vehicle detection (optional)

The **yolov8-multiple-vehicle-detection** script reads the same HLS stream, detects vehicles, and saves speed violation images. Run it **after** FFmpeg and Laravel are running.

1. **Install Python dependencies** (once) in a terminal:

```powershell
cd D:\tapo\tapo\yolov8-multiple-vehicle-detection
pip install ultralytics opencv-python pandas cvzone
```

2. **Start YOLOv8** (keep this window open):

```powershell
cd D:\tapo\tapo\yolov8-multiple-vehicle-detection
python mainh.py
```

- The script reads from `http://127.0.0.1:8000/stream/index.m3u8`, so **FFmpeg** and **php artisan serve** must already be running.
- A window opens showing the live stream with vehicle boxes and speed; speed violations are saved under `yolov8-multiple-vehicle-detection\speed_violations\`.
- View those images in the Laravel app at **Speed Violations** (left sidebar → **Speed Violations**).

3. **Stop YOLOv8**: press **Ctrl + C** in the YOLOv8 window, or close the window.

---

### 5. Stopping everything

- To stop streaming: press **Ctrl + C** in the PowerShell window running FFmpeg.
- To stop YOLOv8: press **Ctrl + C** in the `python mainh.py` window (if running).
- To stop Laravel: press **Ctrl + C** in the `php artisan serve` window.

