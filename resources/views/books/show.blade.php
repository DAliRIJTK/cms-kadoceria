@extends('layouts.dashboard')

@section('content')

@if($errors->has('publication'))
    <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200">
        <div class="flex items-start gap-3">
            <span class="text-2xl">⚠️</span>
            <div>
                <h3 class="font-semibold text-red-900 mb-1">Publikasi Tidak Tersedia</h3>
                <p class="text-red-800 text-sm">{{ $errors->first('publication') }}</p>
            </div>
        </div>
    </div>
@endif

@if($errors->has('delete'))
    <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200">
        <div class="flex items-start gap-3">
            <span class="text-2xl">❌</span>
            <div>
                <h3 class="font-semibold text-red-900 mb-1">Gagal Menghapus Buku</h3>
                <p class="text-red-800 text-sm">{{ $errors->first('delete') }}</p>
            </div>
        </div>
    </div>
@endif

@if(session('success'))
    <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200">
        <div class="flex items-start gap-3">
            <span class="text-2xl">✅</span>
            <div>
                <p class="text-green-800 text-sm font-medium">{{ session('success') }}</p>
            </div>
        </div>
    </div>
@endif

<div class="mb-8">
    <a href="/books" class="text-blue-600 hover:text-blue-700 text-sm font-medium mb-4 inline-block">← Kembali ke Daftar Buku</a>
    
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">📖 {{ $book->title }}</h1>
            <p class="text-gray-600">Kelola halaman dan konten interaktif buku Anda</p>
        </div>
        
                <div class="text-right">
            <span class="inline-flex items-center px-4 py-2 rounded-full font-semibold {{ $book->status === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                {{ $book->status === 'published' ? '✅ Published' : '📋 Draft' }}
            </span>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm p-6 mb-8 border border-gray-200">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        
                <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Penulis</p>
            <p class="text-lg font-semibold text-gray-800">{{ $book->author ?? '-' }}</p>
        </div>

                <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Penerbit</p>
            <p class="text-lg font-semibold text-gray-800">{{ $book->publisher ?? '-' }}</p>
        </div>

                <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Total Halaman</p>
            <p class="text-lg font-semibold text-gray-800">{{ $book->pages()->count() }}</p>
        </div>

                <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Dibuat Pada</p>
            <p class="text-lg font-semibold text-gray-800">{{ $book->created_at->format('d M Y') }}</p>
        </div>
    </div>

        @if($book->description)
        <div class="pt-6 border-t border-gray-200">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Deskripsi</p>
            <p class="text-gray-700 leading-relaxed">{{ $book->description }}</p>
        </div>
    @endif

        <div class="mt-6 pt-6 border-t border-gray-200 flex gap-3 flex-wrap">
        <a href="{{ url('/books/' . $book->id . '/edit') }}" class="px-4 py-2 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors font-medium">
            ✏️ Edit Informasi
        </a>
        <a href="{{ url('/books/' . $book->id . '/pages/upload') }}" class="px-4 py-2 bg-purple-50 text-purple-600 hover:bg-purple-100 rounded-lg transition-colors font-medium">
            + Tambah Halaman
        </a>
        <a href="/pages-management?book_id={{ $book->id }}" class="px-4 py-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-lg transition-colors font-medium">
            📄 Kelola Halaman
        </a>
        <form method="POST" action="{{ url('/books/' . $book->id . '/status') }}" class="inline" id="statusForm" onsubmit="return handleStatusChange(event);">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="{{ $book->status === 'published' ? 'draft' : 'published' }}" id="statusInput">
            <input type="hidden" name="confirm_unpublish" value="no" id="confirmUnpublish">
            <button type="submit" class="px-4 py-2 {{ $book->status === 'published' ? 'bg-orange-50 text-orange-600 hover:bg-orange-100' : 'bg-green-50 text-green-600 hover:bg-green-100' }} rounded-lg transition-colors font-medium">
                {{ $book->status === 'published' ? '📋 Ubah ke Draft' : '✅ Publikasikan' }}
            </button>
        </form>
        <script>
            function handleStatusChange(e) {
                e.preventDefault();
                const form = e.target;
                const statusInput = document.getElementById('statusInput');
                const confirmUnpublish = document.getElementById('confirmUnpublish');
                const currentStatus = '{{ $book->status }}';
                
                if (currentStatus === 'published' && statusInput.value === 'draft') {
                    if (confirm('⚠️ PERINGATAN: Anda akan menarik buku ini dari peredaran.\n\nApakah Anda yakin ingin melanjutkan?')) {
                        confirmUnpublish.value = 'yes';
                        form.submit();
                    }
                    return false;
                } else {
                    return confirm('Ubah status publikasi?');
                }
            }
        </script>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
    <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-3 border-b border-gray-200">👁️ Pratinjau Flipbook</h2>
    
    @if($book->pages()->count() === 0)
        <div class="bg-gray-100 rounded-lg p-8 flex items-center justify-center min-h-96 border-2 border-dashed border-gray-300">
            <div class="text-center">
                <p class="text-gray-500 text-lg mb-2">📭 Belum Ada Halaman</p>
                <p class="text-gray-400 text-sm">Tambahkan halaman terlebih dahulu untuk melihat preview flipbook</p>
            </div>
        </div>
    @else
        <div class="bg-gray-100 rounded-lg p-4 flex items-center justify-center min-h-96 border-2 border-dashed border-gray-300">
            <div class="text-center">
                <p class="text-gray-500 text-lg mb-2">📚 Flipbook Preview ({{ $book->pages()->count() }} halaman)</p>
                <p class="text-gray-400 text-sm mb-4">Klik tombol di bawah untuk melihat pratinjau interaktif</p>
                <button onclick="openFlipbookModal()" class="inline-block px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium">
                    🔍 Buka Pratinjau Penuh
                </button>
            </div>
        </div>

                <div id="flipbookModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
            <div class="bg-white rounded-lg max-w-6xl max-h-screen overflow-auto relative w-full m-4">
                                <div class="sticky top-0 bg-white border-b border-gray-200 p-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-800">📚 {{ $book->title }} - Flipbook Preview</h3>
                    <button onclick="closeFlipbookModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
                </div>

                                <div class="p-8">
                    <div class="flex items-end justify-between mb-6">
                        <div>
                            <h4 class="text-lg font-semibold text-gray-800">Penulis: {{ $book->author ?? '-' }}</h4>
                            <p class="text-gray-600">Penerbit: {{ $book->publisher ?? '-' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-blue-600">{{ $book->pages()->count() }} Halaman</p>
                        </div>
                    </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        @foreach($book->pages as $page)
                            <div class="bg-white rounded-lg border border-gray-300 overflow-hidden hover:shadow-lg transition-shadow">
                                <div class="relative pb-full bg-gray-200">
                                    <img 
                                        src="{{ asset('storage/' . $page->image_url) }}"
                                        alt="Halaman {{ $page->page_number }}"
                                        class="w-full h-64 object-cover"
                                    >
                                    <div class="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-10 transition-all"></div>
                                </div>
                                <div class="p-4 bg-gray-50">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="font-semibold text-gray-900">Halaman {{ $page->page_number }}</p>
                                            <p class="text-xs text-gray-500">ID: {{ $page->id }}</p>
                                        </div>
                                        <div class="flex gap-1">
                                            @if($page->boundingBoxes->count() > 0)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800" title="Anotasi">
                                                    📝 {{ $page->boundingBoxes->count() }}
                                                </span>
                                            @endif
                                            @if($page->audios->count() > 0)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800" title="Audio">
                                                    🔊 {{ $page->audios->count() }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                                        <div class="flex justify-end gap-2 pt-6 border-t border-gray-200">
                        <a href="/pages-management?book_id={{ $book->id }}" class="px-4 py-2 bg-purple-50 text-purple-600 hover:bg-purple-100 rounded-lg transition-colors font-medium">
                            📄 Kelola Halaman
                        </a>
                        <button onclick="closeFlipbookModal()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg transition-colors font-medium">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function openFlipbookModal() {
                document.getElementById('flipbookModal').classList.remove('hidden');
                document.getElementById('flipbookModal').classList.add('flex');
            }

            function closeFlipbookModal() {
                document.getElementById('flipbookModal').classList.add('hidden');
                document.getElementById('flipbookModal').classList.remove('flex');
            }

            // Close modal when clicking outside
            document.getElementById('flipbookModal')?.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeFlipbookModal();
                }
            });
        </script>
    @endif
</div>

@endsection