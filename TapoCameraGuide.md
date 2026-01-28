## Tapo C310 Camera – How to Run

This project is configured to show your Tapo C310 camera in the `/camera` page of your Laravel app.

Follow these steps each time you want to use it:

---

### 1. Make sure the camera is set up

- **Same network**: Your Windows PC and the Tapo C310 camera must be on the same LAN/Wi‑Fi.
- **Camera IP**: In the Tapo app, open your C310 → tap the **settings (⚙)** → **Device Info** and confirm the IP.
  - Your current IP is **`10.169.1.38`** (also stored in `.env` as `TAPO_IP`).
- **Camera Account**:
  - In the Tapo app: C310 → **⚙ Settings** → **Advanced Settings** → **Camera Account**.
  - Create a username and password (you are using `admin123` / `admin123`).

Test RTSP with VLC on your PC:

```text
rtsp://admin123:admin123@10.169.1.38:554/stream1
```

If VLC shows video, the camera and RTSP are working.

---

### 2. Start FFmpeg (RTSP → HLS)

This converts the RTSP stream to HLS files that the browser can play.

1. Ensure the output folder exists:

```powershell
mkdir C:\xampp\tapo\public\stream
```

2. Start FFmpeg from PowerShell (keep this window **open** while you view the camera):

```powershell
"C:\xampp\tapo\ffmpeg-2026-01-22-git-4561fc5e48-full_build\ffmpeg-2026-01-22-git-4561fc5e48-full_build\bin\ffmpeg.exe" -rtsp_transport tcp -i "rtsp://admin123:admin123@10.169.1.38:554/stream1" -fflags +genpts -flags -global_header -hls_time 2 -hls_list_size 3 -hls_flags delete_segments -vcodec copy -acodec aac -f hls "C:\xampp\tapo\public\stream\index.m3u8"
```

You should see the PowerShell window stay busy (not return to `C:\>`).  
In `C:\xampp\tapo\public\stream` you should see `index.m3u8` and small `.ts` files.

---

### 3. Start Laravel and open the camera page

1. From another terminal in `C:\xampp\tapo`:

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

- Camera information with IP `10.169.1.38`.
- The RTSP URL text.
- A **Live View (browser)** player showing the Tapo C310 video (if FFmpeg is running).

---

### 4. Stopping everything

- To stop streaming: press **Ctrl + C** in the PowerShell window running FFmpeg.
- To stop Laravel: press **Ctrl + C** in the `php artisan serve` window.

