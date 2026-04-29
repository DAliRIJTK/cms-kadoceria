<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-screen flex">

    <!-- LEFT -->
    <div class="w-1/2 flex flex-col items-center justify-center bg-white">
        <img src="/logo.png" class="w-40 mb-6">

        <h2 class="text-xl text-gray-700">
            Sistem Manajemen Konten Buku Dwibahasa
        </h2>

        <h1 class="text-4xl font-bold mt-2">
            KADO CERIA
        </h1>
    </div>

    <!-- RIGHT -->
    <div class="w-1/2 bg-teal-700 flex items-center justify-center">

        <div class="w-2/3 text-white">

            <h1 class="text-3xl font-bold mb-2">Daftar</h1>
            <p class="mb-6">Buat akun untuk melanjutkan.</p>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <!-- Name -->
                <div class="mb-4">
                    <label>Nama</label>
                    <input type="text" name="name" required
                        class="w-full p-3 rounded-lg text-black mt-1">
                </div>

                <!-- Email -->
                <div class="mb-4">
                    <label>Email</label>
                    <input type="email" name="email" required
                        class="w-full p-3 rounded-lg text-black mt-1">
                </div>

                <!-- Password -->
                <div class="mb-4">
                    <label>Password</label>
                    <input type="password" name="password" required
                        class="w-full p-3 rounded-lg text-black mt-1">
                </div>

                <!-- Confirm -->
                <div class="mb-6">
                    <label>Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" required
                        class="w-full p-3 rounded-lg text-black mt-1">
                </div>

                <button class="w-full py-3 rounded-lg bg-pink-600 hover:bg-pink-700">
                    Daftar
                </button>

            </form>

            <p class="text-sm mt-6 text-center">
                Sudah punya akun?
                <a href="/login" class="underline">Masuk</a>
            </p>

        </div>

    </div>

</body>
</html>