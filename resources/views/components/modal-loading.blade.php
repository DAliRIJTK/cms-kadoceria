{{-- resources/views/components/modal-loading.blade.php --}}
{{-- Usage: <x-modal-loading id="loadingModal" message="Sedang memproses..." /> --}}

@props(['id' => 'modalLoading', 'message' => 'Sedang memproses...'])

<div id="{{ $id }}"
     class="fixed inset-0 z-50 hidden items-center justify-center"
     aria-modal="true" role="dialog">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

    {{-- Card --}}
    <div class="relative bg-white rounded-2xl shadow-2xl px-16 py-12 flex flex-col items-center gap-6 min-w-[340px] max-w-[90vw]">

        {{-- Animated spinner --}}
        <div class="relative w-20 h-20">
            <svg class="animate-spin w-20 h-20" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="40" cy="40" r="34" stroke="#e5e7eb" stroke-width="8"/>
                <path d="M40 6 A34 34 0 0 1 74 40" stroke="#1d4ed8" stroke-width="8" stroke-linecap="round"/>
            </svg>
        </div>

        <p class="text-gray-700 text-base font-medium text-center">{{ $message }}</p>
    </div>
</div>