<?php

declare(strict_types=1);

/**
 * Safely propagate the top PHP wrapper block from a canonical converted page
 * to other converted pages in the same theme.
 *
 * Usage:
 *   php scripts/pih_propagate_wrapper.php \
 *     --source=themes/hospital_theme/index.php \
 *     --target-dir=themes/hospital_theme \
 *     --dry-run
 *
 * Defaults:
 *   --source=themes/hospital_theme/index.php
 *   --target-dir=themes/hospital_theme
 */

$root = realpath(__DIR__ . '/..');
if ($root === false) {
    fwrite(STDERR, "Could not resolve project root.\n");
    exit(1);
}

$options = getopt('', ['source::', 'target-dir::', 'dry-run']);
$sourceRel = isset($options['source']) ? (string) $options['source'] : 'themes/hospital_theme/index.php';
$targetDirRel = isset($options['target-dir']) ? (string) $options['target-dir'] : 'themes/hospital_theme';
$dryRun = array_key_exists('dry-run', $options);

$source = realpath($root . '/' . ltrim($sourceRel, '/'));
$targetDir = realpath($root . '/' . ltrim($targetDirRel, '/'));

if ($source === false || !is_file($source)) {
    fwrite(STDERR, "Source file not found: {$sourceRel}\n");
    exit(1);
}
if ($targetDir === false || !is_dir($targetDir)) {
    fwrite(STDERR, "Target directory not found: {$targetDirRel}\n");
    exit(1);
}

$sourceText = (string) file_get_contents($source);
$wrapper = extractWrapper($sourceText, $source);

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($targetDir, FilesystemIterator::SKIP_DOTS)
);

$updated = [];
$skipped = [];

foreach ($iterator as $fileInfo) {
    if (!$fileInfo instanceof SplFileInfo) {
        continue;
    }
    if (!$fileInfo->isFile() || strtolower($fileInfo->getExtension()) !== 'php') {
        continue;
    }

    $path = $fileInfo->getPathname();

    // Skip common mirrored third-party directories.
    if (preg_match('~/(wp-content|wp-includes|wp-json)/~', str_replace('\\\\', '/', $path))) {
        continue;
    }

    $text = (string) file_get_contents($path);

    if (strpos($text, '/* pih settings bridge */') === false) {
        $skipped[] = relativePath($root, $path) . ' (missing wrapper marker)';
        continue;
    }

    try {
        $currentWrapper = extractWrapper($text, $path);
    } catch (RuntimeException $e) {
        $skipped[] = relativePath($root, $path) . ' (' . $e->getMessage() . ')';
        continue;
    }

    if ($currentWrapper === $wrapper) {
        continue;
    }

    $newText = str_replace($currentWrapper, $wrapper, $text);
    if ($newText === $text) {
        $skipped[] = relativePath($root, $path) . ' (wrapper replace noop)';
        continue;
    }

    if (!$dryRun) {
        file_put_contents($path, $newText);
    }

    $updated[] = relativePath($root, $path);
}

$mode = $dryRun ? 'DRY RUN' : 'APPLIED';
echo "{$mode}: wrapper updates=" . count($updated) . "\n";
if (!empty($updated)) {
    foreach ($updated as $item) {
        echo "  - {$item}\n";
    }
}
if (!empty($skipped)) {
    echo "SKIPPED: " . count($skipped) . "\n";
    foreach ($skipped as $item) {
        echo "  - {$item}\n";
    }
}

function extractWrapper(string $text, string $path): string
{
    $start = strpos($text, '/* pih settings bridge */');
    if ($start === false) {
        throw new RuntimeException('missing wrapper start marker');
    }

    $phpOpen = strrpos(substr($text, 0, $start), '<?php');
    if ($phpOpen === false) {
        throw new RuntimeException('missing php open tag before wrapper marker');
    }

    $end = strpos($text, '?>', $start);
    if ($end === false) {
        throw new RuntimeException('missing php close tag after wrapper marker');
    }

    $end += 2;
    return substr($text, $phpOpen, $end - $phpOpen);
}

function relativePath(string $root, string $path): string
{
    $root = rtrim(str_replace('\\\\', '/', $root), '/');
    $path = str_replace('\\\\', '/', $path);
    if (strpos($path, $root . '/') === 0) {
        return substr($path, strlen($root) + 1);
    }
    return $path;
}
