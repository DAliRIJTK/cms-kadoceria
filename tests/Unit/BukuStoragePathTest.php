<?php

namespace Tests\Unit;

use App\Models\Buku;
use App\Models\Halaman;
use PHPUnit\Framework\TestCase;

class BukuStoragePathTest extends TestCase
{
    public function test_page_asset_paths_are_stable_when_page_numbers_change(): void
    {
        $buku = new Buku();
        $buku->judul_idn = 'My Sample Book';

        $halaman = new Halaman();
        $halaman->id_halaman = 42;
        $halaman->nomor_halaman = 8;

        $firstPath = $buku->buildPageAssetPath($halaman, 'halaman', 'jpg');

        $halaman->nomor_halaman = 12;

        $this->assertSame($firstPath, $buku->buildPageAssetPath($halaman, 'halaman', 'jpg'));
        $this->assertSame('buku/my_sample_book/halaman/page-42.jpg', $firstPath);
    }
}
