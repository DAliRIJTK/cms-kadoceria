<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CMS</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- FONT POPPINS -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
@stack('scripts')
<body class="font-[Poppins] bg-gray-100">

<div class="flex min-h-screen">

    <!-- SIDEBAR -->
    <div class="w-64 bg-blue-900 text-white flex flex-col">

        <!-- LOGO / TITLE -->
        <div class="p-6 text-lg font-semibold border-b border-blue-800">
            📚 CMS Buku Dwibahasa
        </div>

        <!-- MENU -->
        <nav class="flex-1 p-4 space-y-2">

            <a href="/books"
                class="flex items-center gap-3 p-3 rounded-xl {{ request()->is('books*') && !request()->is('books/*/pages*') ? 'bg-white text-blue-900' : 'hover:bg-blue-800' }} font-medium transition {{ !request()->is('books/*/pages*') ?: 'shadow' }}">
                📘 <span>Daftar Buku</span>
            </a>

            <a href="/pages-management"
                class="flex items-center gap-3 p-3 rounded-xl {{ request()->is('pages-management*') ? 'bg-white text-blue-900 shadow' : 'hover:bg-blue-800' }} font-medium transition">
                ⏱ <span>Kelola Halaman</span>
            </a>

            <a href="/audio-management"
                class="flex items-center gap-3 p-3 rounded-xl {{ request()->is('audio-management*') ? 'bg-white text-blue-900 shadow' : 'hover:bg-blue-800' }} font-medium transition">
                🔊 <span>Kelola Audio</span>
            </a>

        </nav>

        <!-- LOGOUT -->
        <div class="p-4 border-t border-blue-800">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full text-left flex items-center gap-2 hover:text-red-300 transition">
                    🚪 Keluar
                </button>
            </form>
        </div>

    </div>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col">

        <!-- NAVBAR -->
        <div class="bg-white px-6 py-4 shadow flex justify-between items-center">

            <div class="text-gray-600 text-sm">
                Dashboard
            </div>

            <div class="flex items-center gap-3">

                <!-- USER -->
                <div class="text-right">
                    <p class="font-medium text-gray-800">
                        {{ auth()->user()->name }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ auth()->user()->email }}
                    </p>
                </div>

                <!-- AVATAR -->
                <div class="w-10 h-10 rounded-full bg-blue-200 flex items-center justify-center text-blue-900 font-bold">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>

            </div>

        </div>

        <!-- CONTENT -->
        <div class="p-6 flex-1">
            @yield('content')
        </div>

       </div>

</div>

@stack('scripts')

</body>
</html>