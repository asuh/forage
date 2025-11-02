<?php

namespace Vilare\Blade;

use Vilare\Blade\Directives;
use Illuminate\Container\Container;
use Illuminate\Contracts\View\View;
use Illuminate\Events\Dispatcher;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\CompilerEngine;

class Provider
{
    private ?Factory $factory = null;

    public function __construct()
    {
        add_action('after_setup_theme', fn() => $this->init());
        add_filter('comments_template', [$this, 'filterCommentsTemplate']);
        add_filter('get_search_form', [$this, 'filterSearchForm']);
    }

    public function render(string $template, array $data = []): void
    {
        echo $this->generate($template, $data); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public function generate(string $template, array $data = []): string
    {
        return $this->view($template, $data)->render();
    }

    public function view(string $template, array $data = []): View
    {
        return vilare()->filesystem()->exists($template)
            ? $this->factory->file($template, $data)
            : $this->factory->make($template, $data);
    }

    public function templateExists(string $template): bool
    {
        // Convert the dot notation (e.g., 'partials.comments') to a file path
        $templatePath = vilare()->config()->get('views.path') . '/' . str_replace('.', '/', $template) . '.blade.php';

        // Check if the file exists
        return file_exists($templatePath);
    }

    private function init(): void
    {
        $compiler = new BladeCompiler(vilare()->filesystem(), vilare()->config()->get('cache.path'));
        $resolver = new EngineResolver();
        $finder = new FileViewFinder(vilare()->filesystem(), [vilare()->config()->get('views.path')]);
        $dispatcher = new Dispatcher();
        $directives = new Directives();

        $directives->register($compiler);
        $resolver->register('blade', fn() => new CompilerEngine($compiler));

        $finder->addNamespace('blocks', vilare()->config()->get('blocks.path'));
        $finder->addNamespace('components', vilare()->config()->get('components.path'));
        $finder->addNamespace('templates', vilare()->config()->get('templates.path'));

        $this->factory = new Factory($resolver, $finder, $dispatcher);

        do_action('vilare_templating_provider_init', $compiler, $finder);

        Container::getInstance()->bind(
            'Illuminate\Contracts\View\Factory',
            function () {
                return $this->factory;
            }
        );
    }

    public function filterCommentsTemplate($file)
    {
        // Path to the Blade comments template
        $bladeTemplate = 'partials.comments';

        // Check if the Blade template exists
        if ($this->templateExists($bladeTemplate)) {
            // Render the Blade template
            $this->render(
                $bladeTemplate,
                [
                    'post' => get_post(),
                    'comments_open' => comments_open(),
                    'comments' => get_comments(['post_id' => get_the_ID()]),
                    'comment_pages' => paginate_comments_links(['echo' => false]),
                    'previous_page_url' => get_previous_comments_link(),
                    'next_page_url' => get_next_comments_link(),
                ]
            );

            // Return a blank file to prevent WordPress from rendering the default template
            return VILARE_PATH . '/resources/index.php';
        }

        // If the Blade template doesn't exist, fall back to the default behavior
        return $file;
    }

    public function filterSearchForm($view)
    {
        // Path to the Blade search form template
        $bladeTemplate = 'forms.search';

        // Check if the Blade template exists
        if ($this->templateExists($bladeTemplate)) {
            // Generate and return the rendered search form
            return $this->generate($bladeTemplate, []);
        }

        // If the Blade template doesn't exist, fall back to the default behavior
        return $view;
    }
}
