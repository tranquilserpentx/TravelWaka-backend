<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wisata;
use App\Models\Category;
use Illuminate\Http\Request;

class WisataController extends Controller
{
    // ✅ Ambil semua wisata
    public function index()
    {
        $wisata = Wisata::with(['category', 'coverPhoto', 'photos'])
            ->orderBy('rating', 'desc')
            ->get();

        return response()->json([
            'status'  => true,
            'message' => 'Data wisata berhasil diambil',
            'data'    => $wisata,
        ]);
    }

    // ✅ Ambil detail wisata
    public function show($id)
    {
        $wisata = Wisata::with(['category', 'photos', 'user'])
            ->find($id);

        if (!$wisata) {
            return response()->json([
                'status'  => false,
                'message' => 'Wisata tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Detail wisata berhasil diambil',
            'data'    => $wisata,
        ]);
    }

    // ✅ Ambil semua kategori
    public function categories()
    {
        $categories = Category::all();

        return response()->json([
            'status'  => true,
            'message' => 'Data kategori berhasil diambil',
            'data'    => $categories,
        ]);
    }

    // ✅ Filter wisata by kategori
    public function byCategory($categoryId)
    {
        $wisata = Wisata::with(['category', 'coverPhoto', 'photos'])
            ->where('category_id', $categoryId)
            ->orderBy('rating', 'desc')
            ->get();

        return response()->json([
            'status'  => true,
            'message' => 'Data wisata berhasil diambil',
            'data'    => $wisata,
        ]);
    }
}