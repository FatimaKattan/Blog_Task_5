<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }


    public function profile(Request $request)
    {
        $user = $request->user();

/*         $this->authorize('viewProfile', $user);
 */
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'bio' => $user->bio,
            'profile_image_url' => $user->profile_image
                ? asset('storage/' . $user->profile_image)
                : asset('storage/download.jpg'),
            'is_admin' => $user->is_admin,
            'created_at' => $user->created_at
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        /*         $this->authorize('updateProfile', $user);
 */
        $request->validate([
            'name' => 'nullable|string|max:50',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'bio' => 'nullable|string|max:255',
            'profile_image' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
            'password' => 'nullable|string|confirmed|min:8'
        ]);

        $updateData = [
            'name' => $request->name ?? $user->name,
            'email' => $request->email ?? $user->email,
            'bio' => $request->bio ?? $user->bio
        ];

        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('profile_image')) {
            // حذف الصورة القديمة إذا كانت موجودة
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }

            $imagePath = $request->file('profile_image')->store('profile_images', 'public');
            $updateData['profile_image'] = $imagePath;
        }

        $user->update($updateData);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'bio' => $user->bio,
                'profile_image_url' => $user->profile_image
                    ? asset('storage/' . $user->profile_image)
                    : asset('storage/download.jpg'),
                'is_admin' => $user->is_admin,
                'updated_at' => $user->updated_at
            ]
        ], 200);
    }

    public function destroy(User $user)
    {
        if (!Auth::user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action'
            ], 403);
        }

        // Delete user's image if it's not the default one
        if ($user->profile_image !== 'download.jpg') {
            Storage::delete('public/images/users/' . $user->profile_image);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}
