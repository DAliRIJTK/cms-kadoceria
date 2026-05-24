<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-screen flex">

    <!-- LEFT SIDE -->
    <div class="w-1/2 flex flex-col items-center justify-center bg-white">

        <img src="/logo.png" class="w-40 mb-6">

        <h2 class="text-xl text-gray-700">
            Sistem Manajemen Konten Buku Dwibahasa
        </h2>

        <h1 class="text-4xl font-bold mt-2">
            KADO CERIA
        </h1>
    </div>

    <!-- RIGHT SIDE -->
    <div class="w-1/2 bg-blue-900 flex items-center justify-center">

        <div class="w-2/3 text-white">

            <h1 class="text-3xl font-bold mb-2">Masuk</h1>
            <p class="mb-6 opacity-90">Untuk melanjutkan kedalam aplikasi.</p>

            <!-- Error Messages (FR-4) -->
            @if ($errors->any())
                <div class="bg-red-500 bg-opacity-20 border border-red-300 rounded-lg p-4 mb-6">
                    <div class="flex gap-3">
                        <span class="text-xl">⚠️</span>
                        <div class="flex-1">
                            <h3 class="font-semibold mb-2">Gagal Masuk</h3>
                            <ul class="text-sm space-y-1 list-disc list-inside opacity-90">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Success Message (if any) -->
            @if (session('success'))
                <div class="bg-green-500 bg-opacity-20 border border-green-300 rounded-lg p-4 mb-6">
                    <div class="flex gap-3">
                        <span class="text-xl">✅</span>
                        <p class="font-medium">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email -->
                <div class="mb-4">
                    <label class="block font-medium mb-2">Email atau Username</label>
                    <input 
                        type="email" 
                        name="email"
                        value="{{ old('email') }}"
                        class="w-full p-3 rounded-lg text-black mt-1 focus:outline-none focus:ring-2 focus:ring-blue-300 {{ $errors->has('email') ? 'ring-2 ring-red-400' : '' }}"
                        placeholder="user@gmail.com"
                        required
                        autofocus
                    >
                    @error('email')<p class="text-red-200 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label class="block font-medium mb-2">Password</label>
                    <input 
                        type="password" 
                        name="password"
                        class="w-full p-3 rounded-lg text-black mt-1 focus:outline-none focus:ring-2 focus:ring-blue-300 {{ $errors->has('password') ? 'ring-2 ring-red-400' : '' }}"
                        placeholder="••••••••"
                        required
                    >
                    @error('password')<p class="text-red-200 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Remember Me -->
                <div class="mb-6 flex items-center">
                    <input 
                        type="checkbox" 
                        name="remember"
                        id="remember"
                        class="rounded"
                    >
                    <label for="remember" class="ml-2 text-sm opacity-90">Ingat saya</label>
                </div>

                <!-- Button -->
                <button type="submit" class="w-full py-3 rounded-lg bg-blue-600 hover:bg-blue-700 font-semibold transition-colors">
                    🔓 Masuk
                </button>

            </form>

            <!-- Footer -->
            <p class="text-sm mt-10 text-center opacity-80">
                Balai Bahasa Provinsi Jawa Barat<br>
                All Right Reserved | 2026
            </p>

        </div>

    </div>

</body>
</html>