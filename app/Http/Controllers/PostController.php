<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;


class PostController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $posts = Post::with(['user', 'category', 'tags', 'comments'])
            ->get()
            ->map(function ($post) {
                $images = is_array($post->images) ? $post->images : json_decode($post->images, true) ?? [];

                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'content' => $post->content,
                    'images' => array_map(function ($image) use ($post) {
                        return [
                            'url' => $image,
                        ];
                    }, $images),
                    'user' => [
                        'id' => $post->user->id,
                        'name' => $post->user->name,
                        'email' => $post->user->email,
                        'bio' => $post->user->bio,
                        'profile_image' => $post->user->profile_image
                            ? asset('storage/' . $post->user->profile_image)
                            : asset('images/default-profile.jpg'),
                        'is_admin' => (bool)$post->user->is_admin,
                        'created_at' => $post->user->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $post->user->updated_at->format('Y-m-d H:i:s'),
                    ],
                    'category' => $post->category ? [
                        'id' => $post->category->id,
                        'name' => $post->category->name,
                        'image' => $post->category->image ? asset('storage/' . $post->category->image) : null,
                    ] : null,
                    'tags' => $post->tags->map(function ($tag) {
                        return [
                            'id' => $tag->id,
                            'name' => $tag->name
                        ];
                    }),
                    'comments' => $post->comments->map(function ($comment) {
                        return [
                            'id' => $comment->id,
                            'content' => $comment->content,
                            'user' => [
                                'id' => $comment->user->id,
                                'name' => $comment->user->name,
                                'profile_image' => $comment->user->profile_image
                                    ? asset('storage/' . $comment->user->profile_image)
                                    : null
                            ],
                            'created_at' => $comment->created_at->format('Y-m-d H:i:s')
                        ];
                    }),
                    'created_at' => $post->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $post->updated_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $posts,
            'meta' => [
                'total_posts' => count($posts),
                'total_images' => array_sum(array_map(fn($post) => count($post['images']), $posts->toArray())),
                'total_users' => count(array_unique(array_column($posts->toArray(), 'user.id')))
            ]
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
        ]);

        $uploadedImages = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('posts', 'public');
                $uploadedImages[] = asset('storage/' . $path); // إرجاع رابط كامل
            }
        }

        $post = Post::create([
            'user_id' => $validated['user_id'],
            'category_id' => $validated['category_id'],
            'title' => $validated['title'],
            'content' => $validated['content'],
            'images' => json_encode($uploadedImages), // تخزين كمصفوفة JSON
        ]);

        return response()->json([
            'message' => 'Post created successfully.',
            'data' => [
                'id' => $post->id,
                'title' => $post->title,
                'content' => $post->content,
                'images' => json_decode($post->images), // فك تشفير JSON
            ],
        ], 201);
    }

    public function show(string $id)
    {
        $post = Post::with(['user', 'category', 'tags', 'comments'])->find($id);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        }

        // معالجة الصور
        $processedPost = [
            'id' => $post->id,
            'title' => $post->title,
            'content' => $post->content,
            'images' => $post->images ? array_map(function ($image) {
                // إزالة الجزء الزائد من الرابط إذا كان موجودًا
                $cleanedPath = str_replace('http://127.0.0.1:8000/storage/', '', $image);
                return [
                    'url' => asset('storage/' . $cleanedPath),
                ];
            }, json_decode($post->images, true) ?? []) : [],
            'user' => [
                'id' => $post->user->id,
                'name' => $post->user->name,
                'profile_image' => $post->user->profile_image
                    ? asset('storage/' . $post->user->profile_image)
                    : asset('images/default-profile.jpg'),
                'email' => $post->user->email,
                'bio' => $post->user->bio,
            ],
            'category' => $post->category ? [
                'id' => $post->category->id,
                'name' => $post->category->name,
                'image' => $post->category->image
                    ? asset('storage/' . $post->category->image)
                    : null,
            ] : null,
            'tags' => $post->tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name
                ];
            }),
            'comments' => $post->comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'user' => [
                        'name' => $comment->user->name,
                        'avatar' => $comment->user->profile_image
                            ? asset('storage/' . $comment->user->profile_image)
                            : null
                    ]
                ];
            }),
            'created_at' => $post->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $post->updated_at->format('Y-m-d H:i:s')
        ];

        return response()->json([
            'success' => true,
            'data' => $processedPost,
        ], 200);
    }


    public function update(Request $request, string $id)
    {
        $post = Post::find($id);
    
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }
    
        // إنشاء قواعد التحقق الديناميكية
        $rules = [];
        if ($request->has('title')) {
            $rules['title'] = 'string|max:255';
        }
        if ($request->has('content')) {
            $rules['content'] = 'nullable|string';
        }
        if ($request->hasFile('images')) {
            $rules['images'] = 'array';
            $rules['images.*'] = 'image|mimes:jpeg,jpg,png,gif|max:2048';
        }
    
        $validated = $request->validate($rules);
    
        try {
            $updateData = [];
    
            // تحديث الحقول النصية
            foreach (['title', 'content'] as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $validated[$field] ?? null;
                }
            }
    
            // معالجة الصور
            if ($request->hasFile('images')) {
                // حذف الصور القديمة
                $oldImages = json_decode($post->images, true) ?? [];
                foreach ($oldImages as $oldImage) {
                    Storage::disk('public')->delete($oldImage);
                }
    
                // حفظ الصور الجديدة
                $images = [];
                foreach ($request->file('images') as $image) {
                    $path = $image->store('posts', 'public');
                    $images[] = $path;
                }
    
                $updateData['images'] = json_encode($images);
            }
    
            // تطبيق التحديثات إذا وجدت
            if (!empty($updateData)) {
                $post->update($updateData);
            }
    
            // تحضير الصور للاستجابة
            $updatedPost = $post->fresh();
            $imageUrls = [];
            if ($updatedPost->images) {
                $imageUrls = array_map(function($path) {
                    return asset('storage/' . $path); // تأكد من إضافة 'storage/'
                }, json_decode($updatedPost->images, true));
            }
    
            return response()->json([
                'data' => [
                    ...$updatedPost->toArray(),
                    'images' => $imageUrls
                ],
                'message' => 'Updated successfully'
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Update failed: ' . $e->getMessage() // إظهار الخطأ للتصحيح
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        }


/*         $this->authorize('delete', $post);
 */
        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully.',
        ], 200);
    }
}
