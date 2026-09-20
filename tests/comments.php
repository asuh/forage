<?php

require dirname(__DIR__) . '/vendor/autoload.php';
define('FORAGE_PATH', dirname(__DIR__));

$post = (object) ['ID' => 1, 'password_required' => false];
$open = true;
$pages = 1;
$paginate = true;
$previous = null;
$next = null;
$linkCalls = 0;
function get_post(): object { return $GLOBALS['post']; }
function comments_open(): bool { return $GLOBALS['open']; }
function get_comments(...$args): never { throw new RuntimeException('Do not repeat WordPress’s comments query.'); }
function paginate_comments_links(...$args): never { throw new RuntimeException('Do not generate unused numbered pagination.'); }
function get_previous_comments_link($label = ''): ?string { $GLOBALS['linkCalls']++; return $GLOBALS['previous']; }
function get_next_comments_link($label = ''): ?string { $GLOBALS['linkCalls']++; return $GLOBALS['next']; }
function get_comment_pages_count(): int { return $GLOBALS['pages']; }
function get_option($key): bool { return $GLOBALS['paginate']; }
function have_comments(): bool { return false; }
function comment_form(): void { echo '<form id="commentform"></form>'; }
function __($text, $domain = ''): string { return $text; }
function check($condition, $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

$provider = new class extends Forage\Blade\Provider {
    public bool $exists = true;
    public ?array $data = null;
    public function __construct() {}
    public function templateExists(string $template): bool { return $this->exists; }
    public function render(string $template, array $data = []): void { $this->data = $data; }
};
check($provider->filterCommentsTemplate('/fallback.php') === FORAGE_PATH . '/index.php', 'Blade must replace the default comments template.');
check($provider->data === ['post' => $post, 'comments_open' => true], 'Only used view data should be prepared.');
check($linkCalls === 0, 'Pagination must not be prepared before the template needs it.');
$provider->exists = false;
$provider->data = null;
check($provider->filterCommentsTemplate('/fallback.php') === '/fallback.php', 'Missing Blade templates must preserve the WordPress fallback.');
check($provider->data === null, 'Fallback rendering must not prepare Blade data.');

$filesystem = new Illuminate\Filesystem\Filesystem();
$compiler = new Illuminate\View\Compilers\BladeCompiler($filesystem, sys_get_temp_dir());
$compiled = tempnam(sys_get_temp_dir(), 'forage-comments-');
register_shutdown_function(static fn () => unlink($compiled));
file_put_contents($compiled, $compiler->compileString(file_get_contents(FORAGE_PATH . '/resources/views/partials/comments.blade.php')));
$render = static function () use ($compiled): string {
    $post = $GLOBALS['post'];
    $comments_open = $GLOBALS['open'];
    ob_start();
    include $compiled;
    return ob_get_clean();
};

$html = $render();
check($linkCalls === 0 && ! str_contains($html, 'class="pager"'), 'Single-page comments must not generate pagination.');
check(str_contains($html, 'id="commentform"'), 'The comment form must still render.');
$pages = 3;
$next = '<a href="/post/comment-page-2/#comments">Newer comments →</a>';
$html = $render();
check(! str_contains($html, 'class="previous"'), 'The first page must omit the unavailable previous link.');
check(str_contains($html, $next), 'WordPress pagination links must render as complete anchors.');
check(! str_contains($html, '&lt;a'), 'Pagination anchors must not be escaped inside href attributes.');
$previous = '<a href="/post/comment-page-1/#comments">← Older comments</a>';
$html = $render();
check(str_contains($html, $previous) && str_contains($html, $next), 'Middle pages must render both links.');
$next = null;
$html = $render();
check(! str_contains($html, 'class="next"'), 'The last page must omit the unavailable next link.');
$paginate = false;
$linkCalls = 0;
$open = false;
$html = $render();
check($linkCalls === 0 && ! str_contains($html, 'class="pager"'), 'Disabled pagination must skip link generation.');
check(str_contains($html, 'Comments are closed.'), 'Closed comments must retain their notice.');
echo "Comments regression checks passed.\n";
