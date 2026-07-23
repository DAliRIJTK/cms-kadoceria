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
            Sistem Manajemen Konten untuk Buku Anak Dwibahasa
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
                        class="w-full p-3 rounded-lg bg-white text-black mt-1 focus:outline-none focus:ring-2 focus:ring-blue-300 {{ $errors->has('email') ? 'ring-2 ring-red-400' : '' }}"
                        placeholder="Email atau Username"
                        required
                        autofocus
                    >
                    @error('email')<p class="text-red-200 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label class="block font-medium mb-2">Password</label>
                    <div class="relative">
                        <input 
                            id="password-input"
                            type="password" 
                            name="password"
                            class="w-full p-3 pr-10 rounded-lg bg-white text-black mt-1 focus:outline-none focus:ring-2 focus:ring-blue-300 {{ $errors->has('password') ? 'ring-2 ring-red-400' : '' }}"
                            placeholder="••••••••"
                            required
                        >
                        <button type="button" id="toggle-password" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 mt-0.5">
                            <!-- Icon Eye (Menampilkan) -->
                            <svg id="icon-show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <!-- Icon Eye Slash (Menyembunyikan) -->
                            <svg id="icon-hide" style="display: none;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                    @error('password')<p class="text-red-200 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Button -->
                <button type="submit" class="w-full py-3 rounded-lg bg-blue-600 hover:bg-blue-700 font-semibold transition-colors">
                    🔓 Masuk
                </button>

            </form>

            <!-- Footer -->
            <p class="text-sm mt-10 text-center opacity-80">
                Balai Bahasa Provinsi Jawa Barat<br>
                Hak Cipta Dilindungi | 2026
            </p>

        </div>

    </div>

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
                subtitle: '{{ Session::get("success") }}'
            });
        </script>
    @endif
    <script>
        document.getElementById('toggle-password').addEventListener('click', function () {
            const passwordInput = document.getElementById('password-input');
            const iconShow = document.getElementById('icon-show');
            const iconHide = document.getElementById('icon-hide');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                iconShow.style.display = 'none';
                iconHide.style.display = 'block';
            } else {
                passwordInput.type = 'password';
                iconShow.style.display = 'block';
                iconHide.style.display = 'none';
            }
        });
    </script>
</body>
</html>