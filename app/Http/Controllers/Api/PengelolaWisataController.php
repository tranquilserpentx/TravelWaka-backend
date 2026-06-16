<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wisata;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PengelolaWisataController extends Controller
{
    // Ambil semua wisata milik pengelola ini
    public function index(Request $request)
    {
        $wisata = Wisata::with(['category', 'photos'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Daftar wisata milikmu',
            'data' => $wisata
        ]);
    }

    // Tambah wisata baru
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'price' => 'required|string',
            'opening_hours' => 'required|string',
            'category_id' => 'required|exists:categories,id',
        ]);

        $wisata = Wisata::create([
            'user_id' => $request->user()->id,
            'category_id' => $request->category_id,
            'name' => $request->name,
            'description' => $request->description,
            'location' => $request->location,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'price' => $request->price,
            'opening_hours' => $request->opening_hours,
            'rating' => 0,
            'review_count' => 0,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Wisata berhasil ditambahkan',
            'data' => $wisata
        ], 201);
    }

    // Edit wisata milik sendiri
    public function update(Request $request, $id)
    {
        $wisata = Wisata::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$wisata) {
            return response()->json([
                'status' => false,
                'message' => 'Wisata tidak ditemukan atau bukan milikmu',
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'location' => 'sometimes|string',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
            'price' => 'sometimes|string',
            'opening_hours' => 'sometimes|string',
            'category_id' => 'sometimes|exists:categories,id',
        ]);

        $wisata->update($request->only([
            'name', 'description', 'location',
            'latitude', 'longitude', 'price',
            'opening_hours', 'category_id'
        ]));

        return response()->json([
            'status' => true,
            'message' => 'Wisata berhasil diupdate',
            'data' => $wisata
        ]);
    }

    // Hapus wisata milik sendiri
    public function destroy(Request $request, $id)
    {
        $wisata = Wisata::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$wisata) {
            return response()->json([
                'status' => false,
                'message' => 'Wisata tidak ditemukan atau bukan milikmu',
            ], 404);
        }

        // Hapus semua foto wisata
        $photos = Photo::where('wisata_id', $id)->get();
        foreach ($photos as $photo) {
            $filename = str_replace('/storage/', '', $photo->getRawOriginal('photo_url'));
            Storage::disk('public')->delete($filename);
            $photo->delete();
        }

        $wisata->delete();

        return response()->json([
            'status' => true,
            'message' => 'Wisata berhasil dihapus',
        ]);
    }

    // Upload foto wisata
    public function uploadPhoto(Request $request, $wisataId)
    {
        $wisata = Wisata::where('id', $wisataId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$wisata) {
            return response()->json([
                'status' => false,
                'message' => 'Wisata tidak ditemukan atau bukan milikmu',
            ], 404);
        }

        $request->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'is_cover' => 'boolean',
        ]);

        // Cek apakah ini foto pertama dari wisata tersebut
        $isFirstPhoto = !Photo::where('wisata_id', $wisataId)->exists();
        $isCover = $request->is_cover || $isFirstPhoto ? 1 : 0;

        // Jika is_cover true, reset cover foto lain
        if ($isCover) {
            Photo::where('wisata_id', $wisataId)
                ->update(['is_cover' => 0]);
        }

        $path = $request->file('photo')->store('wisata', 'public');

        $photo = Photo::create([
            'wisata_id' => $wisataId,
            'photo_url' => Storage::url($path),
            'is_cover' => $isCover,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Foto berhasil diupload',
            'data' => $photo
        ]);
    }

    // Hapus foto wisata
    public function deletePhoto(Request $request, $wisataId, $photoId)
    {
        $wisata = Wisata::where('id', $wisataId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$wisata) {
            return response()->json([
                'status' => false,
                'message' => 'Wisata tidak ditemukan atau bukan milikmu',
            ], 404);
        }

        $photo = Photo::where('id', $photoId)
            ->where('wisata_id', $wisataId)
            ->first();

        if (!$photo) {
            return response()->json([
                'status' => false,
                'message' => 'Foto tidak ditemukan',
            ], 404);
        }

        $filename = str_replace('/storage/', '', $photo->getRawOriginal('photo_url'));
        Storage::disk('public')->delete($filename);
        $photo->delete();

        return response()->json([
            'status' => true,
            'message' => 'Foto berhasil dihapus',
        ]);
    }
}