<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Storage;


class AuthController extends Controller
{
    use AuthorizesRequests;

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|email|unique:users|max:100',
            'password' => 'required|string|confirmed|min:8',
            'bio' => 'nullable|string|max:255',
            'profile_image' => 'nullable|image|max:2048|mimes:jpeg,jpg,png,gif',
            'is_admin' => 'nullable|boolean'
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'bio' => $request->bio,
            'is_admin' => $request->boolean('is_admin')
        ];

        // معالجة صورة الملف الشخصي إذا تم تحميلها
        if ($request->hasFile('profile_image')) {
            $imagePath = $request->file('profile_image')->store('profile_images', 'public');
            $userData['profile_image'] = $imagePath;
        }

        $user = User::create($userData);

        return response()->json([
            'message' => 'Registration successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'bio' => $user->bio,
                'profile_image_url' => $user->profile_image ? asset('storage/' . $user->profile_image) : asset('storage/download.jpg'),
                'is_admin' => $user->is_admin,
                'created_at' => $user->created_at
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        // تحقق من صحة البيانات المدخلة
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8'
        ]);

        // البحث عن المستخدم بواسطة البريد الإلكتروني
        $user = User::where('email', $request->email)->first();

        // التحقق من وجود المستخدم وصحة كلمة المرور
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // إنشاء توكن جديد للمستخدم
        $token = $user->createToken('auth_token')->plainTextToken;

        // إعداد بيانات المستخدم للإرجاع
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'bio' => $user->bio,
            'profile_image_url' => $user->profile_image
                ? asset('storage/' . $user->profile_image)
                : asset('storage/download.jpg'),
            'is_admin' => $user->is_admin,
            'created_at' => $user->created_at
        ];

        return response()->json([
            'message' => 'Login successful',
            'user' => $userData,
            'token' => $token,
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logout successful'], 200);
    }
}