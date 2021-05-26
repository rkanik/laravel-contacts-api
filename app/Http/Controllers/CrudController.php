<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'request' => $request->all(),
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
        return response()->json([
            'id' => $request->id,
            'request' => $request->all(),
            'message' => 'Create new user',
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
}
