<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tapo C310 Camera') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    <div>
                        <h3 class="font-semibold text-lg text-gray-800">
                            {{ __('Camera Information') }}
                            <div class="border-t pt-4">
    <h3 class="font-semibold text-lg text-gray-800">
        {{ __('Live View (browser)') }}
    </h3>

    <video id="tapoPlayer" controls autoplay muted class="mt-2 w-full max-w-xl bg-black rounded">
        Your browser does not support HTML5 video.
    </video>

    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const video = document.getElementById('tapoPlayer');
            const src = '{{ url('stream/index.m3u8') }}';

            if (Hls.isSupported()) {
                const hls = new Hls();
                hls.loadSource(src);
                hls.attachMedia(video);
            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                video.src = src;
            }
        });
    </script>
</div>
                        </h3>
                        <p class="mt-2 text-gray-700">
                            <span class="font-semibold">{{ __('IP Address:') }}</span>
                            <span>{{ $cameraIp }}</span>
                        </p>
                    </div>

                    <div class="border-t pt-4">
                        <h3 class="font-semibold text-lg text-gray-800">
                            {{ __('RTSP Stream URL (for VLC / NVR)') }}
                        </h3>
                        <p class="mt-2 text-gray-700">
                            {{ __('Use this kind of URL in VLC or another player (replace username/password):') }}
                        </p>
                        <pre class="mt-2 p-3 bg-gray-100 rounded text-sm overflow-x-auto">
                        rtsp://admin123:admin123@10.169.1.38:554/stream1
                        </pre>
                        <p class="mt-2 text-sm text-gray-500">
                            {{ __('RTSP uses the dedicated Camera Account credentials you create in the Tapo app.') }}
                        </p>
                    </div>

                    <div class="border-t pt-4">
                        <h3 class="font-semibold text-lg text-gray-800">
                            {{ __('Next steps') }}
                        </h3>
                        <ul class="mt-2 list-disc list-inside text-gray-700 text-sm space-y-1">
                            <li>{{ __('In the Tapo app: go to your C310 → settings icon (⚙) → Advanced Settings → Camera Account, then create a username and password.') }}</li>
                            <li>{{ __('Use that Camera Account username/password in the RTSP URL above in VLC or your NVR software to view the live stream.') }}</li>
                            <li>{{ __('Later, you can add a streaming proxy (e.g. ffmpeg, motion, or an NVR) and embed it here for in-browser viewing.') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

