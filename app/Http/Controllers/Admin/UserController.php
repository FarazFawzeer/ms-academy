<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public const TYPES = ['Super Admin', 'Admin', 'Front Desk'];

    public function index()
    {
        $users = User::select('id', 'name', 'email', 'type', 'updated_at', 'image_path')
            ->whereIn('type', self::TYPES)
            ->latest()
            ->paginate(10);

        return view('admin.users', compact('users'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255',
            'email'      => 'required|string|email|max:255|unique:users',
            'password'   => 'required|string|min:6|confirmed',
            'type'       => ['required', Rule::in(self::TYPES)],
            'image_path' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:8192',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $request->only(['name', 'email', 'type']);
        $data['password'] = Hash::make($request->password);

        // Profile image: re-saved as a small JPEG with a random name (never the uploaded file name/extension)
        if ($request->hasFile('image_path')) {
            try {
                $data['image_path'] = ImageUpload::square($request->file('image_path'), 'uploads/users');
            } catch (\RuntimeException $e) {
                return response()->json(['success' => false, 'errors' => ['image_path' => [$e->getMessage()]]], 422);
            }
        }

        $user = User::create($data);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully!',
            'user'    => $user,
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        // was: 'superadmin' - never matched the stored value 'Super Admin'
        if ($user->type === 'Super Admin') {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete a super admin.',
            ]);
        }

        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ]);
        }

        ImageUpload::delete($user->image_path);
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully!',
        ]);
    }
}
