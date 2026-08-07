<?php

/**
 * Blog post management. The public blog (BlogController) and the blog_posts
 * table both already existed; there was simply no admin screen behind them, so
 * a post could only ever be created by writing SQL by hand.
 *
 * published_at is set the moment a post first goes live and then left alone, so
 * re-saving a published post does not reorder the blog. Unpublishing back to
 * draft clears it, so a later publish dates the post from when it went live.
 */
final class BlogAdminController extends AdminController
{
    protected string $moduleKey = 'blog';

    public function index(): void
    {
        $filters = [
            'search' => $this->input('search'),
            'status' => $this->input('status'),
            'category' => $this->input('category'),
        ];
        $result = (new BlogPost())->paginateForAdmin($filters, max(1, (int) $this->input('page', '1')));

        $this->adminView('blog/index', [
            'pageTitle' => 'Blog',
            'posts' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'filters' => $filters,
            'categories' => BlogPost::CATEGORIES,
        ]);
    }

    public function create(): void
    {
        Permission::require('blog', 'create');

        $this->adminView('blog/form', [
            'pageTitle' => 'New Blog Post',
            'post' => null,
            'categories' => BlogPost::CATEGORIES,
        ]);
    }

    public function store(): void
    {
        Security::requireCsrf();
        Permission::require('blog', 'create');

        $model = new BlogPost();
        $data = $this->postFromInput($model, null);
        if ($data === null) {
            redirect('admin/blog/create');
        }

        $id = $model->create($data);
        $this->logActivity('blog_post_created', "Created blog post #$id: {$data['title']}");

        flash('success', 'Blog post created.');
        redirect('admin/blog');
    }

    public function edit(string $id): void
    {
        Permission::require('blog', 'edit');

        $post = (new BlogPost())->find((int) $id);
        if (!$post) {
            $this->abort404();
        }

        $this->adminView('blog/form', [
            'pageTitle' => 'Edit Blog Post',
            'post' => $post,
            'categories' => BlogPost::CATEGORIES,
        ]);
    }

    public function update(string $id): void
    {
        Security::requireCsrf();
        Permission::require('blog', 'edit');

        $model = new BlogPost();
        $post = $model->find((int) $id);
        if (!$post) {
            $this->abort404();
        }

        $data = $this->postFromInput($model, $post);
        if ($data === null) {
            redirect('admin/blog/' . $id . '/edit');
        }

        $model->update((int) $id, $data);
        $this->logActivity('blog_post_updated', "Updated blog post #$id: {$data['title']}");

        flash('success', 'Blog post updated.');
        redirect('admin/blog');
    }

    public function destroy(string $id): void
    {
        Security::requireCsrf();
        Permission::require('blog', 'delete');

        $model = new BlogPost();
        $post = $model->find((int) $id);
        if (!$post) {
            $this->abort404();
        }

        $model->delete((int) $id);
        Upload::delete($post['featured_image'] ?? null);
        $this->logActivity('blog_post_deleted', "Deleted blog post #$id: {$post['title']}");

        flash('success', 'Blog post deleted.');
        redirect('admin/blog');
    }

    /** Publish <-> draft from the list, so a post can be pulled without opening the editor. */
    public function toggleStatus(string $id): void
    {
        Security::requireCsrf();
        Permission::require('blog', 'edit');

        $model = new BlogPost();
        $post = $model->find((int) $id);
        if (!$post) {
            $this->abort404();
        }

        $publishing = $post['status'] !== 'published';
        $model->update((int) $id, [
            'status' => $publishing ? 'published' : 'draft',
            // Keep the original date on re-publish; clear it when pulled back to draft.
            'published_at' => $publishing ? ($post['published_at'] ?: date('Y-m-d H:i:s')) : null,
        ]);

        $this->logActivity(
            $publishing ? 'blog_post_published' : 'blog_post_unpublished',
            ($publishing ? 'Published' : 'Unpublished') . " blog post #$id: {$post['title']}"
        );

        flash('success', $publishing ? 'Post published.' : 'Post moved back to draft.');
        redirect('admin/blog');
    }

    /**
     * Validates the form and returns a row ready to write, or null after flashing
     * the problem. $existing is the post being edited, or null when creating.
     */
    private function postFromInput(BlogPost $model, ?array $existing): ?array
    {
        $title = $this->input('title');
        $content = $this->rawInput('content');
        $category = $this->input('category');
        $status = $this->input('status') === 'published' ? 'published' : 'draft';

        $validator = new Validator(['title' => $title, 'content' => $content]);
        $validator->required('title', 'Title')->required('content', 'Content');
        if ($validator->fails()) {
            flash('danger', $validator->firstError());
            $this->rememberInput($title, $content, $category, $status);
            return null;
        }
        if (!isset(BlogPost::CATEGORIES[$category])) {
            flash('danger', 'Please choose a category.');
            $this->rememberInput($title, $content, $category, $status);
            return null;
        }

        $image = Upload::handle($_FILES['featured_image'] ?? [], 'blog');
        if ($image === null && Upload::lastError() !== null) {
            flash('danger', Upload::lastError());
            $this->rememberInput($title, $content, $category, $status);
            return null;
        }

        $data = [
            'title' => $title,
            'category' => $category,
            'excerpt' => $this->input('excerpt') ?: null,
            'content' => $content,
            'status' => $status,
        ];

        // A new image replaces the old one on disk; no upload leaves it untouched.
        if ($image !== null) {
            $data['featured_image'] = $image;
            if ($existing && !empty($existing['featured_image'])) {
                Upload::delete($existing['featured_image']);
            }
        }

        if ($existing === null) {
            $data['author_id'] = (int) Auth::user()['id'];
            $data['slug'] = $this->uniqueSlug($model, $title, null);
            $data['published_at'] = $status === 'published' ? date('Y-m-d H:i:s') : null;

            return $data;
        }

        // Retitling an existing post keeps its slug: the old URL is already indexed
        // and may be linked from elsewhere.
        $data['published_at'] = $status === 'published'
            ? ($existing['published_at'] ?: date('Y-m-d H:i:s'))
            : null;

        return $data;
    }

    private function rememberInput(string $title, string $content, string $category, string $status): void
    {
        $_SESSION['_old'] = [
            'title' => $title,
            'content' => $content,
            'category' => $category,
            'status' => $status,
            'excerpt' => $this->input('excerpt'),
        ];
    }

    private function uniqueSlug(BlogPost $model, string $title, ?int $excludeId): string
    {
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-') ?: 'post';
        $slug = $base;
        $i = 2;
        while ($model->slugExists($slug, $excludeId)) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
