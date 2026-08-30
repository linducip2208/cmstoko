<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'kategori' => ['nullable', 'string', 'max:140'],
            'tag' => ['nullable', 'string', 'max:100'],
        ]);

        $categories = BlogCategory::orderBy('name')->get(['id', 'name', 'slug']);
        $tags = BlogTag::orderBy('name')->get(['id', 'name', 'slug']);

        $activeCategory = null;
        $activeTag = null;

        $posts = BlogPost::published()
            ->with(['category', 'author'])
            ->when($request->filled('q'), fn ($q) => $q->where(function ($w) use ($request) {
                $term = $request->q;
                $w->where('title', 'like', "%{$term}%")
                    ->orWhere('excerpt', 'like', "%{$term}%")
                    ->orWhere('content', 'like', "%{$term}%");
            }))
            ->when($request->filled('kategori'), function ($q) use ($request, &$activeCategory) {
                $activeCategory = BlogCategory::where('slug', $request->kategori)->first();

                return $q->when($activeCategory, fn ($w) => $w->where('blog_category_id', $activeCategory->id));
            })
            ->when($request->filled('tag'), function ($q) use ($request, &$activeTag) {
                $activeTag = BlogTag::where('slug', $request->tag)->first();

                return $q->when($activeTag, fn ($w) => $w->whereHas('tags', fn ($t) => $t->whereKey($activeTag->id)));
            })
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('pages.blog', [
            'posts' => $posts,
            'categories' => $categories,
            'tags' => $tags,
            'activeCategory' => $activeCategory,
            'activeTag' => $activeTag,
            'seo' => \App\Support\Seo::meta(
                title: 'Blog',
                description: 'Cerita, panduan, dan kabar terbaru dari toko kami.',
                canonical: route('blog'),
                robots: ($request->query() && $request->query('q') !== null) ? 'noindex, follow' : null,
            ),
        ]);
    }

    public function show(string $slug): View
    {
        $post = BlogPost::published()
            ->with(['category', 'author', 'tags'])
            ->where('slug', $slug)
            ->firstOrFail();

        $schema = [
            \App\Support\Seo::breadcrumb([
                ['name' => 'Beranda', 'url' => route('home')],
                ['name' => 'Blog', 'url' => route('blog')],
                ['name' => $post->title],
            ]),
        ];

        $schema[] = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'description' => $post->excerpt,
            'image' => $post->cover ? [$post->cover] : null,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'author' => $post->author ? ['@type' => 'Person', 'name' => $post->author->name] : null,
            'publisher' => ['@type' => 'Organization', 'name' => \App\Support\Settings::get('store.name', config('shop.name', 'TokoKita'))],
            'mainEntityOfPage' => route('blog.show', $post->slug),
        ];

        return view('pages.blog-show', [
            'post' => $post,
            'related' => $post->relatedPosts(),
            'seo' => \App\Support\Seo::entityMeta(
                defaultTitle: $post->title,
                entitySeo: $post->seo,
                fallbackDescription: $post->excerpt ?: \App\Support\Seo::safeText((string) $post->content, 160),
                canonical: route('blog.show', $post->slug),
                image: $post->cover,
                schema: array_values(array_filter($schema)),
            ),
        ]);
    }
}
