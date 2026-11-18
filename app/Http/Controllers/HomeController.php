<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Menampilkan halaman katalog buku dengan fitur pencarian, filter, sorting, dan paginasi.
     */
    public function index(Request $request)
    {
        $search   = $request->input('search');
        $penerbit = $request->input('penerbit');
        $status   = $request->input('status'); // tersedia | habis

        $buku = Buku::query()
            // 1. Pencarian judul, penulis, atau penerbit
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('judul', 'like', "%{$search}%")
                      ->orWhere('penulis', 'like', "%{$search}%")
                      ->orWhere('penerbit', 'like', "%{$search}%");
                });
            })
            // 2. Filter penerbit dan ketersediaan stok
            ->when($penerbit, fn ($query, $penerbit) => $query->where('penerbit', $penerbit))
            ->when($status === 'tersedia', fn ($query) => $query->where('stok', '>', 0))
            ->when($status === 'habis', fn ($query) => $query->where('stok', '<=', 0));

        // 3. Pengurutan (nilai sama dengan dropdown di home.blade.php)
        switch ($request->input('sort')) {
            case 'judul_asc':
                $buku->orderBy('judul', 'asc');
                break;
            case 'judul_desc':
                $buku->orderBy('judul', 'desc');
                break;
            case 'tahun_desc':
                $buku->orderBy('tahun_terbit', 'desc');
                break;
            case 'tahun_asc':
                $buku->orderBy('tahun_terbit', 'asc');
                break;
            case 'stok_desc':
                $buku->orderBy('stok', 'desc');
                break;
            default:
                $buku->latest();
                break;
        }

        // 4. Paginasi 8 data, pertahankan parameter URL
        $buku = $buku->paginate(8)->withQueryString();

        // 5. List penerbit unik untuk dropdown filter
        $listPenerbit = Buku::select('penerbit')
            ->whereNotNull('penerbit')
            ->distinct()
            ->orderBy('penerbit')
            ->pluck('penerbit');

        // 6. Return view dengan data buku dan list penerbit
        return view('home', compact('buku', 'listPenerbit'));
    }

    /**
     * Menampilkan halaman detail informasi sebuah buku untuk publik/mahasiswa.
     */
    public function show(Buku $buku)
    {
        return view('home_show', compact('buku'));
    }
}