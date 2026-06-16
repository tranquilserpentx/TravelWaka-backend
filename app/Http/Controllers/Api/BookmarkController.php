<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Wisata;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    // ✅ Ambil semua bookmark user
    public function index(Request $request)
    {
        $bookmarks = Bookmark::with(['wisata.category', 'wisata.coverPhoto', 'wisata.photos'])
            ->where('user_id', $request->user()->id)
            ->get();

        return response()->json([
            'status'  => true,
            'message' => 'Data bookmark berhasil diambil',
            'data'    => $bookmarks,
        ]);
    }

    // ✅ Toggle bookmark (add/remove)
    public function toggle(Request $request, $wisataId)
    {
        $userId = $request->user()->id;

        $bookmark = Bookmark::where('user_id', $userId)
            ->where('wisata_id', $wisataId)
            ->first();

        if ($bookmark) {
            $bookmark->delete();
            return response()->json([
                'status'      => true,
                'message'     => 'Bookmark dihapus',
                'is_bookmarked' => false,
            ]);
        } else {
            Bookmark::create([
                'user_id'   => $userId,
                'wisata_id' => $wisataId,
            ]);
            return response()->json([
                'status'      => true,
                'message'     => 'Bookmark ditambahkan',
                'is_bookmarked' => true,
            ]);
        }
    }

    // ✅ Cek apakah wisata sudah di-bookmark
    public function check(Request $request, $wisataId)
    {
        $isBookmarked = Bookmark::where('user_id', $request->user()->id)
            ->where('wisata_id', $wisataId)
            ->exists();

        return response()->json([
            'status'        => true,
            'message'       => 'Status bookmark',
            'is_bookmarked' => $isBookmarked,
        ]);
    }
}