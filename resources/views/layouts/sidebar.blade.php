<aside class="fixed left-0 top-0 z-30 h-screen w-56 border-r border-gray-200 bg-white shadow-sm hidden md:block">
    <div class="flex h-16 items-center border-b border-gray-100 px-4">
        <a href="{{ route('dashboard') }}" class="font-semibold text-gray-800">
            {{ config('app.name', 'Traffic Monitor') }}
        </a>
    </div>
    <nav class="flex flex-col gap-1 p-3">
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                  {{ request()->routeIs('dashboard') ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <svg class="h-5 w-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
            {{ __('Dashboard') }}
        </a>
        <a href="{{ route('speed.index') }}"
           class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                  {{ request()->routeIs('speed.*') ? 'bg-sky-50 text-sky-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <svg class="h-5 w-5 shrink-0 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            {{ __('Speed Violations') }}
        </a>
        <a href="{{ route('honk.index') }}"
           class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                  {{ request()->routeIs('honk.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <svg class="h-5 w-5 shrink-0 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707 4.707C11.923 15.657 14 14.828 14 12.586V11.414c0-2.242-2.077-3.071-3.707-1.293L5.586 15z" />
            </svg>
            {{ __('Honk Detections') }}
        </a>
        <a href="{{ route('camera.index') }}"
           class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                  {{ request()->routeIs('camera.*') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <svg class="h-5 w-5 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            {{ __('Camera') }}
        </a>
    </nav>
</aside>
