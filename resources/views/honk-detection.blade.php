<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Honk / Car Horn Detection') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    <div>
                        <h3 class="font-semibold text-lg text-gray-800">
                            {{ __('How to run the horn detector') }}
                        </h3>
                        <ol class="mt-3 list-decimal list-inside text-sm text-gray-700 space-y-1">
                            <li>Open a terminal in <code>C:\xampp\tapo\CarHornDetection</code>.</li>
                            <li>Activate the virtual environment: <code>.\venv\Scripts\activate</code>.</li>
                            <li>Start the detector: <code>python horn_detector_new.py</code>.</li>
                            <li>Keep that window open; when a horn is detected it will create JSON events and send them to your Laravel API.</li>
                        </ol>
                    </div>

                    <div class="border-t pt-4">
                        <h3 class="font-semibold text-lg text-gray-800">
                            {{ __('Recent horn detection events') }}
                        </h3>

                        @if (empty($events))
                            <p class="mt-2 text-sm text-gray-600">
                                {{ __('No horn events found yet. Run the detector and trigger a car horn sound near the microphone.') }}
                            </p>
                        @else
                            <div class="mt-3 overflow-x-auto">
                                <table class="min-w-full text-sm border divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-semibold text-gray-700">Detected at</th>
                                            <th class="px-3 py-2 text-left font-semibold text-gray-700">Plate</th>
                                            <th class="px-3 py-2 text-left font-semibold text-gray-700">Speed</th>
                                            <th class="px-3 py-2 text-left font-semibold text-gray-700">Decibel</th>
                                            <th class="px-3 py-2 text-left font-semibold text-gray-700">Status</th>
                                            <th class="px-3 py-2 text-left font-semibold text-gray-700">File</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($events as $event)
                                            <tr>
                                                <td class="px-3 py-2">
                                                    {{ $event['detected_at'] ?? $event['created_at'] ?? 'N/A' }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    {{ $event['plate_number'] ?? 'UNKNOWN' }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    {{ $event['speed'] ?? '—' }}
                                                    @if(isset($event['speed'])) km/h @endif
                                                </td>
                                                <td class="px-3 py-2">
                                                    {{ $event['decibel_level'] ?? '—' }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    <span class="inline-flex px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                                        {{ $event['status'] ?? 'flagged' }}
                                                    </span>
                                                </td>
                                                <td class="px-3 py-2 text-xs text-gray-500">
                                                    {{ $event['_filename'] ?? '' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div class="border-t pt-4">
                        <h3 class="font-semibold text-lg text-gray-800 mb-2">
                            {{ __('Cars who violated (speed)') }}
                        </h3>
                        <p class="text-sm text-gray-600 mb-4">
                            {{ __('Speed violation images from YOLOv8 detection.') }}
                        </p>
                        @if (empty($speedViolationImages))
                            <p class="text-sm text-gray-500">
                                {{ __('No speed violation images yet. Run the YOLOv8 detector (mainh.py) to capture violations.') }}
                            </p>
                        @else
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                                @foreach ($speedViolationImages as $img)
                                    <button type="button" data-img-url="{{ $img['url'] }}"
                                            data-img-date="{{ $img['date'] ?? '' }}"
                                            data-img-time="{{ $img['time'] ?? '' }}"
                                            data-img-speed="{{ $img['speed'] ?? '' }}"
                                            data-img-vehicle="{{ $img['vehicle'] ?? '' }}"
                                            data-img-direction="{{ $img['direction'] ?? '' }}"
                                            data-img-filename="{{ $img['filename'] }}"
                                            class="speed-violation-card group block w-full text-left bg-gray-50 rounded-lg border border-gray-100 overflow-hidden hover:shadow-md hover:border-sky-200 transition focus:outline-none focus:ring-2 focus:ring-sky-300">
                                        <div class="aspect-video bg-gray-200 relative">
                                            <img src="{{ $img['url'] }}" alt="{{ $img['filename'] }}"
                                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-200"
                                                 loading="lazy" />
                                            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent px-2 py-1.5 text-white text-[10px] sm:text-xs">
                                                @if(!empty($img['date']))<span>{{ $img['date'] }}</span>@endif
                                                @if(!empty($img['time']))<span>{{ $img['time'] }}</span>@endif
                                                @if(!empty($img['speed']))<span class="font-semibold"> · {{ $img['speed'] }}</span>@endif
                                            </div>
                                        </div>
                                        <div class="p-2 space-y-0.5">
                                            @if(!empty($img['date']) || !empty($img['time']))
                                                <p class="text-xs font-medium text-gray-700">
                                                    {{ $img['date'] ?? '' }}{{ !empty($img['date']) && !empty($img['time']) ? ' ' : '' }}{{ $img['time'] ?? '' }}
                                                </p>
                                            @endif
                                            @if(!empty($img['speed']))
                                                <p class="text-xs text-sky-600">{{ $img['speed'] }}</p>
                                            @endif
                                            <p class="truncate text-xs text-gray-500" title="{{ $img['filename'] }}">{{ $img['filename'] }}</p>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                            {{-- Floating modal for image details (no new tab) --}}
                            <div id="speedViolationModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
                                <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col" onclick="event.stopPropagation()">
                                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
                                        <h3 id="modalTitle" class="font-semibold text-gray-900">{{ __('Violation details') }}</h3>
                                        <button type="button" id="closeSpeedModal" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700" aria-label="{{ __('Close') }}">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    </div>
                                    <div class="p-4 space-y-3 overflow-y-auto">
                                        <img id="modalImage" src="" alt="" class="w-full rounded-lg border border-gray-200" />
                                        <dl class="grid grid-cols-2 gap-2 text-sm">
                                            <dt class="text-gray-500">{{ __('Date') }}</dt><dd id="modalDate" class="font-medium text-gray-900">—</dd>
                                            <dt class="text-gray-500">{{ __('Time') }}</dt><dd id="modalTime" class="font-medium text-gray-900">—</dd>
                                            <dt class="text-gray-500">{{ __('Speed') }}</dt><dd id="modalSpeed" class="font-medium text-sky-600">—</dd>
                                            <dt class="text-gray-500">{{ __('Vehicle') }}</dt><dd id="modalVehicle" class="font-medium text-gray-900">—</dd>
                                            <dt class="text-gray-500">{{ __('Direction') }}</dt><dd id="modalDirection" class="font-medium text-gray-900">—</dd>
                                            <dt class="text-gray-500">{{ __('File') }}</dt><dd id="modalFilename" class="font-mono text-xs text-gray-600 truncate" title="">—</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                            <script>
                                document.querySelectorAll('.speed-violation-card').forEach(function(btn) {
                                    btn.addEventListener('click', function() {
                                        var modal = document.getElementById('speedViolationModal');
                                        document.getElementById('modalImage').src = btn.dataset.imgUrl;
                                        document.getElementById('modalImage').alt = btn.dataset.imgFilename;
                                        document.getElementById('modalDate').textContent = btn.dataset.imgDate || '—';
                                        document.getElementById('modalTime').textContent = btn.dataset.imgTime || '—';
                                        document.getElementById('modalSpeed').textContent = btn.dataset.imgSpeed || '—';
                                        document.getElementById('modalVehicle').textContent = btn.dataset.imgVehicle || '—';
                                        document.getElementById('modalDirection').textContent = btn.dataset.imgDirection || '—';
                                        document.getElementById('modalFilename').textContent = btn.dataset.imgFilename || '—';
                                        document.getElementById('modalFilename').title = btn.dataset.imgFilename || '';
                                        modal.classList.remove('hidden');
                                        document.body.style.overflow = 'hidden';
                                    });
                                });
                                function closeSpeedModal() {
                                    var modal = document.getElementById('speedViolationModal');
                                    modal.classList.add('hidden');
                                    document.body.style.overflow = '';
                                }
                                document.getElementById('closeSpeedModal').addEventListener('click', closeSpeedModal);
                                document.getElementById('speedViolationModal').addEventListener('click', function(e) {
                                    if (e.target === this) closeSpeedModal();
                                });
                                document.addEventListener('keydown', function(e) {
                                    if (e.key === 'Escape') closeSpeedModal();
                                });
                            </script>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

