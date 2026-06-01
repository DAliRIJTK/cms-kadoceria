<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Buku;
use App\Models\Halaman;
use Illuminate\Support\Facades\Storage;
use Spatie\PdfToImage\Pdf;

class HalamanController extends Controller
{
    public function index($id_buku)
    {
        $halaman = Halaman::where('id_buku', $id_buku)
                          ->orderBy('nomor_halaman', 'asc')
                          ->get();

        return response()->json([
            'message' => 'Berhasil mengambil daftar halaman',
            'data' => $halaman
        ], 200);
    }

    public function uploadPdf(Request $request, $id_buku)
    {
        $request->validate([
            'file_pdf' => 'required|mimes:pdf|max:20480',
        ]);
    
        $buku = Buku::find($id_buku);
        if (!$buku) {
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        }

        try {
            
            $pdfPath = $request->file('file_pdf')->store('temp_pdf', 'local'); 
            
            $absolutePdfPath = Storage::disk('local')->path($pdfPath);

            if (!file_exists($absolutePdfPath)) {
                return response()->json([
                    'message' => 'File PDF tidak ditemukan setelah upload',
                    'debug' => [
                        'pdf_path' => $pdfPath,
                        'absolute_path' => $absolutePdfPath,
                        'file_exists' => file_exists($absolutePdfPath)
                    ]
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengupload file PDF',
                'error' => $e->getMessage()
            ], 500);
        }

        $pdf = new Pdf($absolutePdfPath);

        $jumlahHalaman = $pdf->pageCount();

        $halamanTersimpan = [];

        $storageDir = 'public/buku_' . $id_buku;
        $storageDir = 'buku_' . $id_buku;

        if (!Storage::disk('public')->exists($storageDir)) {
            Storage::disk('public')->makeDirectory($storageDir);
        }

        for ($i = 1; $i <= $jumlahHalaman; $i++) {
            $namaFileGambar = 'halaman_' . $i . '.webp';
            
            $absoluteImagePath = Storage::disk('public')->path($storageDir . '/' . $namaFileGambar);

            $pdf->selectPage($i)
                ->format(\Spatie\PdfToImage\Enums\OutputFormat::Webp)
                ->save($absoluteImagePath);

            $halamanBaru = Halaman::create([
                'id_buku' => $id_buku,
                'nomor_halaman' => $i,
            ]);

            $halamanTersimpan[] = $halamanBaru;
        }

        Storage::disk('public')->delete($pdfPath);

        return response()->json([
            'message' => 'PDF berhasil diproses menjadi ' . $jumlahHalaman . ' halaman gambar.',
            'data' => $halamanTersimpan
        ], 201);
    }

    public function update(Request $request, $id_halaman)
    {
        $halaman = Halaman::find($id_halaman);

        if (!$halaman) {
            return response()->json(['message' => 'Halaman tidak ditemukan'], 404);
        }

        $request->validate([
            'narasi_indo' => 'nullable|string|max:255',
            'narasi_sunda' => 'nullable|string|max:255',
            'nomor_halaman' => 'integer'
        ]);

        $halaman->update($request->all());

        return response()->json([
            'message' => 'Data halaman berhasil diperbarui',
            'data' => $halaman
        ], 200);
    }

    public function destroy($id_halaman)
    {
        $halaman = Halaman::find($id_halaman);

        if (!$halaman) {
            return response()->json(['message' => 'Halaman tidak ditemukan'], 404);
        }

        $filePath = 'public/buku_' . $halaman->id_buku . '/halaman_' . $halaman->nomor_halaman . '.webp';
        if (Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }

        $halaman->delete();

        return response()->json(['message' => 'Halaman berhasil dihapus'], 200);
    }
}
