<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Speed Violations') }}
        </h2>
        <p class="text-sm text-gray-600 mt-0.5">
            {{ __('Images captured for speed violations.') }}
        </p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (empty($images))
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
                    {{ __('No speed violation images found yet. Images will appear here when the detection system captures violations.') }}
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    @foreach ($images as $img)
                        <button type="button" data-img-url="{{ $img['url'] }}"
                                data-img-date="{{ $img['date'] ?? '' }}"
                                data-img-time="{{ $img['time'] ?? '' }}"
                                data-img-speed="{{ $img['speed'] ?? '' }}"
                                data-img-vehicle="{{ $img['vehicle'] ?? '' }}"
                                data-img-direction="{{ $img['direction'] ?? '' }}"
                                data-img-filename="{{ $img['filename'] }}"
                                class="speed-violation-card group block w-full text-left bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md hover:border-sky-200 transition focus:outline-none focus:ring-2 focus:ring-sky-300">
                            <div class="aspect-video bg-gray-100 relative">
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
</x-app-layout>
