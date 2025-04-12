<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function index($postId)
    {
        $comments = Comment::with('user')
            ->where('post_id', $postId)
            ->latest()
            ->get();

        return response()->json([
            'data' => $comments,
        ]);
    }

    public function store(Request $request, $postId)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000'
        ]);

        $comment = Comment::create([
            'user_id' => Auth::id(), // المستخدم الحالي
            'post_id' => (int)$postId,
            'content' => $validated['content']
        ]);

        return response()->json([
            'data' => $comment->load('user'), // تحميل بيانات المستخدم مع التعليق
        ], 201);
    }

    // تحديث تعليق
    public function update(Request $request, $commentId)
    {
        $comment = Comment::findOrFail($commentId);

        // التأكد من أن المستخدم هو صاحب التعليق
        if ($comment->user_id !== Auth::id()) {
            return response()->json(['message' => 'You are not authorized to perform this action.'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:1000'
        ]);

        $comment->update($validated);

        return response()->json([
            'data' => $comment->fresh()->load('user'),
            'message' =>'Comment updated successfully'
        ]);
    }

    // حذف تعليق
    public function destroy($commentId)
    {
        $comment = Comment::findOrFail($commentId);

        // التأكد من أن المستخدم هو صاحب التعليق أو صاحب المنشور
        if ($comment->user_id !== Auth::id() && $comment->post->user_id !== Auth::id()) {
            return response()->json(['message' =>'You are not authorized to perform this action.'], 403);
        }

        $comment->delete();

        return response()->json([
            'message' =>'Comment deleted successfully' 
        ]);
    }
}
