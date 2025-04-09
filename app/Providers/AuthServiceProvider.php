<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\CommentPolicy;
use App\Policies\PostPolicy;
use App\Policies\TagPolicy;
use App\Policies\UserPolicy;
use Dom\Comment;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    protected $policies = [
        Post::class => PostPolicy::class,
        Comment::class => CommentPolicy::class,
        Category::class => CategoryPolicy::class,
        Tag::class => TagPolicy::class,
        User::class => UserPolicy::class,
        ];
    public function boot(): void
    {
        $this->registerPolicies ();
    }
}