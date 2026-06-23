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

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col">

        <!-- NAVBAR -->
        <div class="bg-white px-6 py-4 shadow">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-6">
                    <h1 class="text-lg font-bold text-blue-900">📚 CMS Buku Dwibahasa</h1>
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

                    <!-- LOGOUT -->
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="ml-4 text-sm text-red-600 hover:text-red-700 font-medium">🚪 Keluar</button>
                    </form>
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
<x-modal-scripts />

</body>
</html>