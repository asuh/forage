<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

$root = dirname(__DIR__);
$views = $root . '/resources/views';
$output = sys_get_temp_dir() . '/forage-i18n-' . bin2hex(random_bytes(8));
$filesystem = new Filesystem();
$compiler = new BladeCompiler($filesystem, $output);
register_shutdown_function(static fn () => $filesystem->deleteDirectory($output));

foreach ($filesystem->allFiles($views) as $file) {
    $target = $output . '/' . str_replace('.blade.php', '.php', $file->getRelativePathname());
    $filesystem->ensureDirectoryExists(dirname($target));
    $filesystem->put($target, $compiler->compileString($file->getContents()));
    exec(sprintf('%s -l %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($target)), $lint, $lintStatus);

    if (0 !== $lintStatus) {
        fwrite(STDERR, $file->getRelativePathname() . " compiles to invalid PHP:\n" . implode("\n", $lint) . "\n");
        exit(1);
    }

    $lint = [];
}

$command = sprintf(
    '%s %s --standard=WordPress --sniffs=WordPress.WP.I18n --runtime-set text_domain forage --basepath=%s --report=emacs %s',
    escapeshellarg(PHP_BINARY),
    escapeshellarg($root . '/vendor/bin/phpcs'),
    escapeshellarg($output),
    escapeshellarg($output),
);
exec($command, $lines, $status);

if (0 !== $status) {
    fwrite(STDERR, "Blade translation calls need fixing (paths and lines refer to compiled views from resources/views):\n" . implode("\n", $lines) . "\n");
    exit(1);
}

echo "Blade translation checks passed.\n";
