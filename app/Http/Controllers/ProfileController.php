<?php

namespace App\Http\Controllers;

use App\Support\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user(); // logged-in user
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password'   => 'nullable|string|min:6|confirmed',
            'image_path' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:8192',
        ]);

        // Profile image: re-saved as a small JPEG with a random name (never the uploaded file name/extension)
        if ($request->hasFile('image_path')) {
            try {
                $new = ImageUpload::square($request->file('image_path'), 'uploads/users');
            } catch (\RuntimeException $e) {
                return back()->withErrors(['image_path' => $e->getMessage()])->withInput();
            }

            ImageUpload::delete($user->image_path);
            $user->image_path = $new;
        }

        $user->name  = $validated['name'];
        $user->email = $validated['email'];

        // Update password if provided
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('admin.profile.edit')->with('success', 'Profile updated successfully!');
    }
}
