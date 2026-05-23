<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Wisata;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // Ambil semua review milik wisata tertentu
    public function index($wisataId)
    {
        $reviews = Review::with('user:id,name')
            ->where('wisata_id', $wisataId)
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Data review berhasil diambil',
            'data' => $reviews
        ]);
    }

    // Tambah atau update review
    public function store(Request $request, $wisataId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $review = Review::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'wisata_id' => $wisataId,
            ],
            [
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]
        );

        // Update rating & review_count di tabel wisata
        $avgRating = Review::where('wisata_id', $wisataId)->avg('rating');
        $reviewCount = Review::where('wisata_id', $wisataId)->count();

        Wisata::where('id', $wisataId)->update([
            'rating' => round($avgRating, 1),
            'review_count' => $reviewCount,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Review berhasil disimpan',
            'data' => $review
        ]);
    }

    // Cek apakah user sudah review wisata ini
    public function check($wisataId)
    {
        $review = Review::where('user_id', auth()->id())
            ->where('wisata_id', $wisataId)
            ->first();

        return response()->json([
            'status' => true,
            'message' => 'Status review',
            'data' => $review
        ]);
    }

    // Hapus review
    public function destroy($wisataId)
    {
        $review = Review::where('user_id', auth()->id())
            ->where('wisata_id', $wisataId)
            ->first();

        if (!$review) {
            return response()->json([
                'status' => false,
                'message' => 'Review tidak ditemukan',
            ], 404);
        }

        $review->delete();

        // Update rating & review_count
        $avgRating = Review::where('wisata_id', $wisataId)->avg('rating');
        $reviewCount = Review::where('wisata_id', $wisataId)->count();

        Wisata::where('id', $wisataId)->update([
            'rating' => $avgRating ? round($avgRating, 1) : 0,
            'review_count' => $reviewCount,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Review berhasil dihapus',
        ]);
    }
}