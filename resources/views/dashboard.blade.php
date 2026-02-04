<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-2xl text-gray-900 leading-tight">
                {{ __('Welcome, Admin User!') }}
            </h2>
            <p class="text-sm text-gray-600">
                {{ __("Here’s what’s happening with your traffic monitoring system today.") }}
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Top summary cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Today\'s Violations --}}
                <div class="bg-white shadow-sm rounded-xl border border-orange-100">
                    <div class="p-5 flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold text-orange-500 uppercase tracking-wide">
                                {{ __("Today’s Violations") }}
                            </p>
                            <p class="mt-2 text-3xl font-bold text-gray-900">
                                {{-- Replace with real count from backend --}}
                                12
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ __("Total violations detected today.") }}
                            </p>
                        </div>
                        <div
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-orange-50 text-orange-500">
                            {{-- Alert icon --}}
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M12 9v3.75M12 15.75h.007M10.34 4.5l-6.16 10.5A1.125 1.125 0 0 0 5.078 17.25h13.844a1.125 1.125 0 0 0 .898-1.75l-6.16-10.5a1.125 1.125 0 0 0-1.936 0Z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Speed Violations --}}
                <div class="bg-white shadow-sm rounded-xl border border-sky-100">
                    <div class="p-5 flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold text-sky-500 uppercase tracking-wide">
                                {{ __("Speed Violations") }}
                            </p>
                            <p class="mt-2 text-3xl font-bold text-gray-900">
                                8
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ __("vs. 6 yesterday") }}
                                <span class="text-emerald-500 font-semibold">+33%</span>
                            </p>
                        </div>
                        <div
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-sky-50 text-sky-500">
                            {{-- Speedometer icon --}}
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M12 3a9 9 0 0 0-9 9h3m12 0h3a9 9 0 0 0-9-9v0m-4.5 9a4.5 4.5 0 1 0 9 0A4.5 4.5 0 0 0 10.5 12Zm6.364-4.364L15 9.5" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Honk Detections --}}
                <div class="bg-white shadow-sm rounded-xl border border-indigo-100">
                    <div class="p-5 flex items-start justify-between">
                        <div>
                            <p class="text-xs font-semibold text-indigo-500 uppercase tracking-wide">
                                {{ __("Honk Detections") }}
                            </p>
                            <p class="mt-2 text-3xl font-bold text-gray-900">
                                4
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ __("Based on real-time horn detector input.") }}
                            </p>
                        </div>
                        <div
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-indigo-50 text-indigo-500">
                            {{-- Horn / sound icon --}}
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M4 10v4a2 2 0 0 0 2 2h1l4 3v-14l-4 3H6a2 2 0 0 0-2 2Zm11.5-2.5a3.5 3.5 0 0 1 0 7M18 7a6 6 0 0 1 0 10" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Charts section --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Violation Distribution --}}
                <div class="bg-white shadow-sm rounded-xl border border-gray-100">
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold text-gray-800">
                                {{ __('Violation Distribution') }}
                            </h3>
                            <span class="text-xs text-gray-400">
                                {{ __('Today') }}
                            </span>
                        </div>
                        <div class="h-56 flex items-center justify-center">
                            {{-- Placeholder for chart (you can replace with Chart.js later) --}}
                            <div class="w-40 h-40 rounded-full bg-gradient-to-tr from-orange-100 via-sky-100 to-indigo-100 flex items-center justify-center">
                                <div class="w-24 h-24 rounded-full bg-white flex flex-col items-center justify-center">
                                    <span class="text-xs text-gray-500">{{ __('Speed') }}</span>
                                    <span class="text-xs text-gray-500">{{ __('Honk') }}</span>
                                    <span class="text-xs text-gray-500">{{ __('Other') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-3 gap-3 text-xs">
                            <div class="flex items-center gap-2">
                                <span class="inline-block h-2 w-2 rounded-full bg-sky-500"></span>
                                <span class="text-gray-700">{{ __('Speed') }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-block h-2 w-2 rounded-full bg-indigo-500"></span>
                                <span class="text-gray-700">{{ __('Honk') }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-block h-2 w-2 rounded-full bg-orange-400"></span>
                                <span class="text-gray-700">{{ __('Other') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Monthly Violations --}}
                <div class="bg-white shadow-sm rounded-xl border border-gray-100">
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold text-gray-800">
                                {{ __('Monthly Violations') }}
                            </h3>
                            <span class="text-xs text-gray-400">
                                {{ __('Current month') }}
                            </span>
                        </div>
                        <div class="h-56 flex items-end gap-2">
                            {{-- Simple fake bar chart; replace with real data later --}}
                            @php
                                $bars = [18, 24, 15, 30, 22, 27, 20];
                            @endphp
                            @foreach ($bars as $height)
                                <div class="flex-1 flex flex-col items-center justify-end">
                                    <div class="w-full rounded-t-lg bg-gradient-to-t from-orange-400 to-orange-500"
                                         style="height: {{ 20 + $height }}%">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-3 flex justify-between text-[11px] text-gray-500">
                            <span>{{ __('Week 1') }}</span>
                            <span>{{ __('Week 2') }}</span>
                            <span>{{ __('Week 3') }}</span>
                            <span>{{ __('Week 4') }}</span>
                            <span>{{ __('Week 5') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
