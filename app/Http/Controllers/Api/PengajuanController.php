<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Http\Request;

class PengajuanController extends Controller
{
    // User mengajukan jadi pengelola
    public function store(Request $request)
    {
        $request->validate([
            'nama_usaha' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'alasan' => 'required|string',
        ]);

        // Cek apakah sudah ada pengajuan pending
        $existing = Pengajuan::where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json([
                'status' => false,
                'message' => 'Kamu sudah memiliki pengajuan yang sedang diproses',
            ], 422);
        }

        $pengajuan = Pengajuan::create([
            'user_id' => $request->user()->id,
            'nama_usaha' => $request->nama_usaha,
            'deskripsi' => $request->deskripsi,
            'alasan' => $request->alasan,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Pengajuan berhasil dikirim, tunggu persetujuan admin',
            'data' => $pengajuan
        ]);
    }

    // User cek status pengajuannya
    public function myStatus(Request $request)
    {
        $pengajuan = Pengajuan::where('user_id', $request->user()->id)
            ->latest()
            ->first();

        return response()->json([
            'status' => true,
            'message' => 'Status pengajuan',
            'data' => $pengajuan
        ]);
    }

    // Super admin — ambil semua pengajuan pending
    public function index()
    {
        $pengajuan = Pengajuan::with('user:id,name,email')
            ->where('status', 'pending')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Daftar pengajuan',
            'data' => $pengajuan
        ]);
    }

    // Super admin — approve pengajuan
    public function approve(Request $request, $id)
    {
        $pengajuan = Pengajuan::findOrFail($id);
        $pengajuan->update([
            'status' => 'approved',
            'catatan_admin' => $request->catatan_admin,
        ]);

        // Ubah role user jadi pengelola
        User::where('id', $pengajuan->user_id)
            ->update(['role' => 'pengelola']);

        return response()->json([
            'status' => true,
            'message' => 'Pengajuan disetujui, user sekarang menjadi pengelola',
        ]);
    }

    // Super admin — reject pengajuan
    public function reject(Request $request, $id)
    {
        $request->validate([
            'catatan_admin' => 'required|string',
        ]);

        $pengajuan = Pengajuan::findOrFail($id);
        $pengajuan->update([
            'status' => 'rejected',
            'catatan_admin' => $request->catatan_admin,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Pengajuan ditolak',
        ]);
    }
}