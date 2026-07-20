<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="{{ asset('assets/logobalai.png') }}">
    <title>CMS - Kado Ceria</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- FONT POPPINS -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
@stack('scripts')
<body class="font-[Poppins] bg-gray-100">

<div class="flex min-h-screen">

    <!-- MAIN CONTENT -->
    <div class="flex-1 min-w-0 flex flex-col">

        <!-- NAVBAR -->
        <div class="bg-white px-6 py-4 shadow">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <a href="{{ route('dashboard') }}" class="text-lg font-bold text-blue-900 hover:text-blue-700 transition-colors duration-200">
                        📚 CMS Buku Dwibahasa
                    </a>
                    <!-- <span class="text-gray-300">|</span>
                    <a href="{{ route('dashboard') }}" class="text-sm font-medium {{ request()->routeIs('dashboard') ? 'text-blue-600' : 'text-gray-600 hover:text-blue-600' }} transition-colors duration-200">
                         📝 Daftar Buku Cerita
                    </a>
                    <span class="text-gray-300">|</span>
                    <a href="{{ route('buku.create') }}" class="text-sm font-medium {{ request()->routeIs('buku.create') ? 'text-blue-600' : 'text-gray-600 hover:text-blue-600' }} transition-colors duration-200">
                         ➕ Tambah Buku Cerita
                    </a>

                    <span class="text-gray-300">|</span>
                    <a href="{{ route('audio-latar.index') }}" class="text-sm font-medium {{ request()->routeIs('audio-latar.*') ? 'text-blue-600' : 'text-gray-600 hover:text-blue-600' }} transition-colors duration-200">
                        🎵 Kelola Audio Latar
                    </a> -->
                </div>

                <div class="flex items-center gap-3">

                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="ml-4 text-sm text-red-600 hover:text-red-700 font-medium">🚪 Keluar</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="p-6 flex-1 min-w-0">
            @yield('content')
        </div>

       </div>

</div>

@stack('scripts')
<x-modal-loading id="globalLoadingModal" message="Sistem sedang memproses. Mohon tunggu..." />
<x-modal-alert id="globalConfirmModal" type="confirm" title="Konfirmasi" subtitle="Apakah Anda yakin?" confirm-label="Ya" cancel-label="Batal" />
<x-modal-alert id="globalConfirmModal" type="confirm" title="Konfirmasi" subtitle="Apakah Anda yakin?" confirm-label="Ya" cancel-label="Batal" />
<x-modal-alert id="globalLogoutModal" type="logout" title="Keluar Aplikasi" subtitle="Apakah Anda yakin?" confirm-label="Keluar" cancel-label="Batal" />
<x-modal-scripts />
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('submit', function (e) {
        const form = e.target;
        const methodInput = form.querySelector('input[name="_method"]');
        const isDelete = (methodInput && methodInput.value.toUpperCase() === 'DELETE') || form.action.includes('removeBacksound');
        const isLogout = form.action.includes('logout');

        if ((isDelete || isLogout) && !form.dataset.confirmed) {
            e.preventDefault();
            
            let title = 'Konfirmasi Hapus';
            let subtitle = 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.';
            
            if (isLogout) {
                title = 'Keluar Aplikasi';
                subtitle = 'Apakah Anda yakin ingin keluar dari sistem?';
            } else if (form.id === 'delete-narasi-indo-form') {
                title = 'Hapus Narasi Indonesia';
                subtitle = 'Apakah Anda yakin ingin menghapus audio narasi Bahasa Indonesia ini?';
            } else if (form.id === 'delete-narasi-sunda-form') {
                title = 'Hapus Narasi Sunda';
                subtitle = 'Apakah Anda yakin ingin menghapus audio narasi Bahasa Sunda ini?';
            } else if (form.action.includes('deleteNarasi')) {
                title = 'Hapus Narasi';
                subtitle = 'Apakah Anda yakin ingin menghapus audio narasi ini?';
            } else if (form.action.includes('removeBacksound')) {
                title = 'Hapus Audio Latar';
                subtitle = 'Apakah Anda yakin ingin menghapus Audio Latar halaman ini?';
            } else if (form.action.includes('deleteAreaAudio')) {
                const langVal = form.querySelector('input[name="audio_type"]')?.value;
                const langLabel = langVal === 'sunda' ? 'Sunda' : 'Indonesia';
                title = 'Hapus Audio Objek';
                subtitle = `Apakah Anda yakin ingin menghapus audio objek Bahasa ${langLabel} untuk area ini?`;
            } else if (form.action.includes('buku') && methodInput && methodInput.value.toUpperCase() === 'DELETE') {
                title = 'Hapus Buku';
                subtitle = 'Apakah Anda yakin ingin menghapus buku ini beserta seluruh isinya? Tindakan ini tidak dapat dibatalkan.';
            } else if (form.action.includes('halaman') && methodInput && methodInput.value.toUpperCase() === 'DELETE') {
                title = 'Hapus Halaman';
                subtitle = 'Apakah Anda yakin ingin menghapus halaman ini? Tindakan ini tidak dapat dibatalkan.';
            } else if (form.action.includes('area-interaktif') && methodInput && methodInput.value.toUpperCase() === 'DELETE') {
                title = 'Hapus Area Interaktif';
                subtitle = 'Apakah Anda yakin ingin menghapus area interaktif ini?';
            } else if (form.action.includes('audio-latar') && methodInput && methodInput.value.toUpperCase() === 'DELETE') {
                title = 'Hapus Audio Latar';
                subtitle = 'Apakah Anda yakin ingin menghapus audio latar ini?';
            }

            const modalId = isLogout ? 'globalLogoutModal' : 'globalConfirmModal';
            ModalAlert.confirm(modalId, { title, subtitle }, function () {
                form.dataset.confirmed = 'true';
                ModalAlert.loading('globalLoadingModal');
                form.submit();
            });
        }
    });
});
</script>

</body>
</html>