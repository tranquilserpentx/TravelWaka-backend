<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    // ✅ Register
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'user',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => 'Registrasi berhasil',
            'data'    => [
                'user'  => $user,
                'token' => $token,
            ],
        ], 201);
    }

    // ✅ Login
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'status'  => false,
                'message' => 'Email atau password salah',
            ], 401);
        }

        $user  = User::where('email', $request->email)->first();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => 'Login berhasil',
            'data'    => [
                'user'  => $user,
                'token' => $token,
            ],
        ]);
    }

    // ✅ Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Logout berhasil',
        ]);
    }

    // ✅ Me
    public function me(Request $request)
    {
        $user = $request->user();

        // Count bookmarks
        $bookmarksCount = \App\Models\Bookmark::where('user_id', $user->id)->count();

        // Count reviews
        $reviewsCount = \App\Models\Review::where('user_id', $user->id)->count();

        // Count managed wisata (if role is pengelola)
        $wisataCount = 0;
        if ($user->role === 'pengelola') {
            $wisataCount = \App\Models\Wisata::where('user_id', $user->id)->count();
        }

        // Append counts to user object
        $user->bookmarks_count = $bookmarksCount;
        $user->reviews_count = $reviewsCount;
        $user->wisata_count = $wisataCount;

        return response()->json([
            'status'  => true,
            'message' => 'Data user berhasil diambil',
            'data'    => $user,
        ]);
    }

    // ✅ Redirect ke Google
    public function redirectToGoogle()
    {
        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();

        return response()->json([
            'status'  => true,
            'message' => 'Redirect URL Google',
            'data'    => [
                'url' => $url,
            ],
        ]);
    }

    // ✅ Handle Callback dari Google
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::updateOrCreate(
                ['google_id' => $googleUser->getId()],
                [
                    'name'   => $googleUser->getName(),
                    'email'  => $googleUser->getEmail(),
                    'avatar' => $googleUser->getAvatar(),
                    'role'   => 'user',
                ]
            );

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status'  => true,
                'message' => 'Login dengan Google berhasil',
                'data'    => [
                    'user'  => $user,
                    'token' => $token,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Login Google gagal',
                'errors'  => $e->getMessage(),
            ], 500);
        }
    }

    // ✅ Login Google Android via ID Token
    public function loginGoogleAndroid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $idToken = $request->id_token;
        $clientId = config('services.google.client_id');

        if (!$clientId) {
            return response()->json([
                'status'  => false,
                'message' => 'Google Client ID belum dikonfigurasi di backend',
            ], 500);
        }

        try {
            $client = new \Google\Client(['client_id' => $clientId]);
            $payload = $client->verifyIdToken($idToken);

            if (!$payload) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Google ID Token tidak valid atau kedaluwarsa',
                ], 401);
            }

            // Data user dari payload Google ID Token
            $googleId = $payload['sub'];
            $email = $payload['email'] ?? null;
            $name = $payload['name'] ?? 'Google User';
            $avatar = $payload['picture'] ?? null;

            if (!$email) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Gagal mendapatkan email dari Google ID Token',
                ], 400);
            }

            // Cari user berdasarkan google_id, atau cari berdasarkan email dan hubungkan google_id
            $user = User::where('google_id', $googleId)->first();

            if (!$user) {
                $user = User::where('email', $email)->first();
                if ($user) {
                    $user->update([
                        'google_id' => $googleId,
                        'avatar'    => $avatar ?? $user->avatar,
                    ]);
                } else {
                    $user = User::create([
                        'name'      => $name,
                        'email'     => $email,
                        'google_id' => $googleId,
                        'avatar'    => $avatar,
                        'role'      => 'user',
                    ]);
                }
            } else {
                $user->update([
                    'name'   => $name,
                    'avatar' => $avatar ?? $user->avatar,
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status'  => true,
                'message' => 'Login Google Android berhasil',
                'data'    => [
                    'user'  => $user,
                    'token' => $token,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Terjadi kesalahan saat verifikasi Google Token',
                'errors'  => $e->getMessage(),
            ], 500);
        }
    }
}