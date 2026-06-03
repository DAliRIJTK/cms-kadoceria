{{-- resources/views/components/modal-alert.blade.php --}}
{{-- Usage:
    <x-modal-alert id="successModal" type="success" title="Berhasil!" />
    <x-modal-alert id="errorModal"   type="error"   title="Terjadi Kesalahan" subtitle="Pesan detail" />
    <x-modal-alert id="confirmModal" type="confirm" title="Hapus?" subtitle="Yakin?" confirm-label="Hapus" cancel-label="Batal" />
--}}

@props([
    'id'           => 'modalAlert',
    'type'         => 'success',   // success | error | confirm | warning
    'title'        => '',
    'subtitle'     => '',
    'autoDismiss'  => true,        // auto close after 3s (not for confirm type)
    'confirmLabel' => 'Ya, Lanjutkan',
    'cancelLabel'  => 'Batal',
    'confirmAction'=> '',          // JS expression or form id to submit
])

@php
$iconMap = [
    'success' => [
        'bg'    => 'bg-green-100',
        'svg'   => '<svg class="w-10 h-10 text-green-500 animate-pop" viewBox="0 0 40 40" fill="none"><circle cx="20" cy="20" r="20" fill="currentColor" fill-opacity=".15"/><path d="M12 20l6 6 10-12" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="animate-draw"/></svg>',
    ],
    'error'   => [
        'bg'    => 'bg-red-100',
        'svg'   => '<svg class="w-10 h-10 text-red-500 animate-pop" viewBox="0 0 40 40" fill="none"><circle cx="20" cy="20" r="20" fill="currentColor" fill-opacity=".15"/><path d="M14 14l12 12M26 14L14 26" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>',
    ],
    'confirm' => [
        'bg'    => 'bg-red-100',
        'svg'   => '<svg class="w-12 h-12 text-red-600 animate-pop" viewBox="0 0 48 48" fill="none"><rect x="8" y="14" width="32" height="28" rx="3" fill="currentColor" fill-opacity=".12" stroke="currentColor" stroke-width="2.5"/><path d="M18 14v-2a2 2 0 012-2h8a2 2 0 012 2v2" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/><line x1="4" y1="14" x2="44" y2="14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/><line x1="20" y1="22" x2="20" y2="34" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/><line x1="28" y1="22" x2="28" y2="34" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>',
    ],
    'warning' => [
        'bg'    => 'bg-yellow-100',
        'svg'   => '<svg class="w-10 h-10 text-yellow-500 animate-pop" viewBox="0 0 40 40" fill="none"><circle cx="20" cy="20" r="20" fill="currentColor" fill-opacity=".15"/><path d="M20 13v9M20 27v1" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>',
    ],
];
$icon = $iconMap[$type] ?? $iconMap['error'];
@endphp

<div id="{{ $id }}"
     data-auto-dismiss="{{ $autoDismiss && $type !== 'confirm' ? 'true' : 'false' }}"
     data-type="{{ $type }}"
     class="fixed inset-0 z-50 hidden items-center justify-center"
     aria-modal="true" role="dialog">

    {{-- Backdrop --}}
    <div class="modal-backdrop absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity duration-300 opacity-0"></div>

    {{-- Card --}}
    <div class="modal-card relative bg-white rounded-2xl shadow-2xl px-10 py-10 flex flex-col items-center gap-4
                min-w-[320px] max-w-[90vw] scale-90 opacity-0 transition-all duration-300">

        {{-- Icon --}}
        <div class="w-20 h-20 rounded-full {{ $icon['bg'] }} flex items-center justify-center">
            {!! $icon['svg'] !!}
        </div>

        {{-- Text --}}
        <div class="text-center">
            <p id="{{ $id }}-title" class="text-xl font-bold text-gray-900 mt-1">{{ $title }}</p>
            @if($subtitle)
                <p id="{{ $id }}-subtitle" class="text-sm text-gray-500 mt-1">{{ $subtitle }}</p>
            @endif
        </div>

        {{-- Confirm buttons --}}
        @if($type === 'confirm')
            <div class="flex gap-3 mt-2 w-full">
                <button type="button"
                        onclick="ModalAlert.close('{{ $id }}')"
                        class="flex-1 px-6 py-3 rounded-xl border-2 border-red-400 text-red-600 font-bold text-base hover:bg-red-50 transition-colors">
                    {{ $cancelLabel }}
                </button>
                <button type="button"
                        id="{{ $id }}-confirm-btn"
                        class="flex-1 px-6 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-base transition-colors shadow-md">
                    {{ $confirmLabel }}
                </button>
            </div>
        @endif

        {{-- Auto-dismiss progress bar --}}
        @if($autoDismiss && $type !== 'confirm')
            <div class="w-full h-1 bg-gray-100 rounded-full overflow-hidden mt-1">
                <div id="{{ $id }}-progress"
                     class="h-full rounded-full transition-none
                            {{ $type === 'success' ? 'bg-green-400' : ($type === 'error' ? 'bg-red-400' : 'bg-yellow-400') }}"
                     style="width: 100%;">
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Inline styles for keyframe animations (safe to repeat, browser deduplicates) --}}
<style>
@keyframes pop-in {
    0%   { transform: scale(0.5); opacity: 0; }
    70%  { transform: scale(1.1); }
    100% { transform: scale(1);   opacity: 1; }
}
.animate-pop { animation: pop-in .45s cubic-bezier(.34,1.56,.64,1) both; }

@keyframes draw {
    from { stroke-dashoffset: 40; }
    to   { stroke-dashoffset: 0; }
}
.animate-draw {
    stroke-dasharray: 40;
    stroke-dashoffset: 40;
    animation: draw .4s .3s ease forwards;
}
</style>