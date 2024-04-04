<?php

namespace App\Http\Controllers\Boards;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Comment;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::all();
        return response()->json($posts);
    }

    public function selectPost(Request $request)
    {   
        //
    }

    
    public function create()
    {
        //
    }

    
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'title' => 'required',
            'content' => 'required',
            'category' => 'required',
            // 미구현
            // 'img_urls' => 'sometimes|array',
            // 'img_urls.*' => 'string',
        ]);

        $post = Post::create($request->all());

        return response()->json($post, 201);
    }

    
    public function show(Post $post)
    {   
        $post = Post::with('comments')->findOrFail($post->id);
        if (!$post) {
            return response()->json(['message' => '해당 게시글을 찾을 수 없습니다.'], 404);
        }
        return response()->json($post);
    }

    
    public function edit(Post $post)
    {
        //
    }

    
    public function update(Request $request, Post $post)
    {
        $post = Post::find($post->id);
        if (!$post) {
            return response()->json(['message' => '해당 게시글을 찾을 수 없습니다.'], 404);
        }

        $request->validate([
            'user_id' => 'required',
            'title' => 'required',
            'content' => 'required',
            // 미구현
            // 'category' => 'required',
            // 'img_urls' => 'sometimes|array',
            // 'img_urls.*' => 'string',
        ]);

        $post->update($request->all());

        return response()->json($post);
    }

    
    public function destroy(Post $post)
    {
        $post = Post::find($post->id);
        if (!$post) {
            return response()->json(['message' => '해당 게시글을 찾을 수 없습니다.'], 404);
        }

        $post->delete();

        return response()->json(['message' => '게시글이 삭제되었습니다.']);
    }

    public function search(Request $request)
    {
        $search = $request->query('search');

        if (empty($search)) {
            return response()->json(['message' => '검색어를 입력해주세요.'], 400);
        }

        $posts = Post::where('title', 'LIKE', "%{$search}%")
                    ->orWhere('content', 'LIKE', "%{$search}%")
                    ->get();

        return response()->json($posts);
    }


}
