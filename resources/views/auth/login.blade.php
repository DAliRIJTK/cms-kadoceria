<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="{{ asset('assets/logobalai.png') }}">
    <title>CMS - Kado Ceria</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-screen flex">

    <!-- LEFT SIDE -->
    <div class="w-1/2 flex flex-col items-center justify-center bg-white">

        <img src="{{ asset('assets/logobalai.png') }}" class="w-40 mb-6" alt="Logo Balai Bahasa">

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

            <x-modal-alert id="loginAlertModal" type="error" />
            <x-modal-alert id="loginSuccessModal" type="success" />

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email -->
                <div class="mb-4">
                    <label class="block font-medium mb-2">Email atau Username</label>
                    <input 
                        type="text" 
                        name="email"
                        value="{{ old('email') }}"
                        class="w-full p-3 rounded-lg text-black mt-1 focus:outline-none focus:ring-2 focus:ring-blue-300 {{ $errors->has('email') ? 'ring-2 ring-red-400' : '' }}"
                        placeholder="Email atau Username"
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

    <x-modal-scripts />
    @if ($errors->any())
        <script>
            ModalAlert.show('loginAlertModal', {
                title: 'Gagal Masuk',
                subtitle: '{{ $errors->first() }}'
            });
        </script>
    @endif
    @if (session('success'))
        <script>
            ModalAlert.show('loginSuccessModal', {
                title: 'Berhasil!',
                subtitle: '{{ session('success') }}'
            });
        </script>
    @endif
</body>
</html>