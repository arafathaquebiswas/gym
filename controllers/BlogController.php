<?php

final class BlogController extends Controller
{
    public function index(): void
    {
        $blogModel = new BlogPost();
        $page = max(1, (int) $this->input('page', '1'));
        $category = $this->input('category') ?: null;

        $result = $blogModel->paginate($page, 6, $category);

        $this->view('blog', [
            'posts' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'perPage' => $result['per_page'],
            'totalPages' => (int) ceil($result['total'] / $result['per_page']),
            'activeCategory' => $category,
        ]);
    }

    public function show(string $slug): void
    {
        $blogModel = new BlogPost();
        $post = $blogModel->findBySlug($slug);

        if (!$post) {
            $this->abort404();
        }

        $blogModel->incrementViews((int) $post['id']);

        $this->view('blog-detail', [
            'post' => $post,
            'recentPosts' => $blogModel->recent(4),
        ]);
    }
}
