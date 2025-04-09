<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TagController extends Controller
{

    public function index()
    {
        $tags = Tag::all();

        return response()->json([
            'data' => $tags,
            'message' => 'Tags retrieved successfully'
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:tags',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $tag = Tag::create([
            'name' => $request->name
        ]);

        return response()->json([
            'message' => 'Tag created successfully',
            'data' => $tag,
        ], 201);
    }

    public function show($id)
    {
        $tag = Tag::find($id);

        if (!$tag) {
            return response()->json([
                'message' => 'Tag not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Tag retrieved successfully',
            'data' => $tag,
        ]);
    }


    public function update(Request $request, $id)
    {
        $tag = Tag::find($id);

        if (!$tag) {
            return response()->json([
                'message' => 'Tag not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:tags,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $tag->update([
            'name' => $request->name
        ]);

        return response()->json([
            'message' => 'Tag updated successfully',
            'data' => $tag,
        ]);
    }

    public function destroy($id)
    {
        $tag = Tag::find($id);

        if (!$tag) {
            return response()->json([
                'message' => 'Tag not found'
            ], 404);
        }

        $tag->delete();

        return response()->json([
            'message' => 'Tag deleted successfully'
        ]);
    }
}