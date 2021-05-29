<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with(['contacts'])->paginate($request->per_page);

        return response()->json([
            'users' => $users,
        ], Response::HTTP_OK);
    }

    public function insert(Request $request)
    {
        return response()
            ->json([
                'request' => $request->all(),
                'message' => 'Create new user',
            ], Response::HTTP_CREATED);
    }

    public function select(Request $request)
    {

        $user = $request->id
        ? User::find($request->id)
        : Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'User not found!',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'user' => $user,
        ], Response::HTTP_OK);
    }

    public function replace(Request $request)
    {
        return response()
            ->json([
                'id' => $request->id,
                'request' => $request->all(),
                'message' => 'User data replaced',
            ], Response::HTTP_OK);
    }

    public function update(Request $request)
    {
        return response()->json([
            'id' => $request->id,
            'request' => $request->all(),
            'message' => 'User data updated',
        ], Response::HTTP_OK);
    }

    public function destroy(Request $request)
    {
        return response()->json([
            'id' => $request->id,
            'request' => $request->all(),
            'message' => 'User deleted',
        ], Response::HTTP_OK);
    }

    public function updateAvatar(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'avatar' => 'required|mimes:png,jpg,jpeg|max:3076',
        ]);
        if ($validation->fails()) {
            return response()->json([
                'errors' => $validation->errors(),
                'message' => 'Please provide a valid file.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = Auth::user();
        if ($user->avatar != null) {
            if (File::exists(public_path('storage/' . $user->avatar))) {
                File::delete(public_path('storage/' . $user->avatar));
            }
        }
        if ($request->file()) {
            $file = $request->file('avatar');
            $name = uniqid() . '.' . strtolower($file->getClientOriginalExtension());
            $filePath = $file->storeAs('avatars', $name, 'public');

            $user->avatar = $filePath;
            $user->save();
        }

        return response()->json([
            'user' => Auth::user(),
            'message' => 'Avatar updated.',
        ], Response::HTTP_OK);
    }
}
