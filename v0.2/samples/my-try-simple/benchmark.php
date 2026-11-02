<?php
/** Repeatable frontend timings and incremental proofs; all mutations use a disposable copy. */
declare(strict_types=1);
namespace scpp\compiler {
    /** Observe actual source reads without changing compiler code. */
    function file_get_contents(string $path): string|false
    {
        if (str_ends_with($path, '.phs')) {
            $GLOBALS['source_reads']++;
        }
        return \file_get_contents($path);
    }
}
namespace {
    require_once __DIR__ . '/../../compiler/boot.php';
    use scpp\compiler\{Module_Loader, Project_Compiler, Program_Loader, Tokenizer, Parser,
        Symbol_Publication, change_status, Program_Frontend, entry_target};

    function check(bool $condition, string $message): void
    {
        if (!$condition) { throw new RuntimeException($message); }
    }

    function summary(array $values): array
    {
        sort($values);
        return ['median_ms' => $values[intdiv(count($values), 2)], 'min_ms' => min($values), 'max_ms' => max($values)];
    }

    $GLOBALS['source_reads'] = 0;
    check(!function_exists('xdebug_info') || xdebug_info('mode') === [], 'Disable Xdebug with XDEBUG_MODE=off');
    $manifest = json_decode(file_get_contents(__DIR__ . '/module.json'), true, flags: JSON_THROW_ON_ERROR);
    $paths = array_keys($manifest['sources']);
    $times = [];
    $proofs = [];
    $root = sys_get_temp_dir() . '/scpp-simple-benchmark-' . bin2hex(random_bytes(8));
    mkdir($root);
    try {
        foreach ($paths as $path) {
            $directory = dirname($root . '/' . $path);
            if (!is_dir($directory)) { mkdir($directory, 0777, true); }
            copy(__DIR__ . '/' . $path, $root . '/' . $path);
        }
        $write_manifest = static function (array $value) use ($root): void {
            file_put_contents($root . '/module.json', json_encode($value, JSON_THROW_ON_ERROR));
        };
        $write_manifest($manifest);
        // Warm filesystem caches and initialization; reported samples exclude this preparation.
        $session = (new Module_Loader())->load($root);
        (new Program_Frontend())->prepare($session, new entry_target(31));
        unset($session);
        gc_collect_cycles();
        $compiler = new Project_Compiler();
        $target = array_values(array_filter($paths, static fn(string $p): bool => $p !== 'main.phs'))[0];
        $original = file_get_contents($root . '/' . $target);
        for ($sample = 0; $sample < 7; $sample++) {
            $write_manifest($manifest);
            file_put_contents($root . '/' . $target, $original);
            $session = (new Module_Loader())->load($root);
            $totals = ['read_snapshot' => 0.0, 'tokenize' => 0.0, 'parse_collect' => 0.0];
            $files = [];
            $token_count = 0;
            foreach ($session->entry->sources as $unit) {
                $start = hrtime(true);
                $source = Program_Loader::load($unit);
                $totals['read_snapshot'] += (hrtime(true) - $start) / 1e6;
                $start = hrtime(true);
                $tokens = (new Tokenizer($source))->tokenize();
                $totals['tokenize'] += (hrtime(true) - $start) / 1e6;
                $start = hrtime(true);
                $files[$unit->path] = (new Parser($tokens))->parse();
                $totals['parse_collect'] += (hrtime(true) - $start) / 1e6;
                $token_count += count($tokens->tokens);
            }
            $start = hrtime(true);
            $symbols = Symbol_Publication::completed($files);
            $totals['symbol_index'] = (hrtime(true) - $start) / 1e6;
            check(count($symbols->declarations) === 256, 'Expected 256 public functions');
            foreach ($totals as $stage => $ms) { $times[$stage][] = $ms; }
            unset($files, $symbols, $session, $source, $tokens);
            gc_collect_cycles();

            $previous = null;
            foreach (['full', 'unchanged', 'changed', 'added', 'removed', 'moved'] as $scenario) {
                $current_manifest = $manifest;
                if ($scenario === 'changed') {
                    file_put_contents($root . '/' . $target, str_replace("= 1;", "= 999;", $original));
                }
                if ($scenario === 'added') {
                    file_put_contents($root . '/added.phs', 'function added_function(): void { return; }');
                    $current_manifest['sources']['added.phs'] = 'php';
                    $write_manifest($current_manifest);
                }
                if ($scenario === 'removed') {
                    unlink($root . '/added.phs');
                    $write_manifest($manifest);
                }
                if ($scenario === 'moved') {
                    rename($root . '/' . $target, $root . '/moved.phs');
                    unset($current_manifest['sources'][$target]);
                    $current_manifest['sources']['moved.phs'] = 'php';
                    $write_manifest($current_manifest);
                }
                $GLOBALS['source_reads'] = 0;
                $start = hrtime(true);
                $current = $compiler->frontend($root, $previous);
                $times[$scenario][] = (hrtime(true) - $start) / 1e6;
                $counts = ['reused' => 0, 'added' => 0, 'changed' => 0, 'removed' => count($current->removed_sources), 'reads' => $GLOBALS['source_reads']];
                foreach ($current->entry->sources as $path => $unit) {
                    if ($unit->status === change_status::none) {
                        $counts['reused']++;
                        check($unit->ast === $previous->entry->sources[$path]->ast, 'AST identity lost');
                        check($unit->ast->tokens === $previous->entry->sources[$path]->ast->tokens, 'Token identity lost');
                    } else {
                        $counts[$unit->status->name]++;
                    }
                }
                $expected = match ($scenario) {
                    'full' => [0, 257, 0, 0, 257],
                    'unchanged' => [257, 0, 0, 0, 0],
                    'changed' => [256, 0, 1, 0, 1],
                    'added' => [257, 1, 0, 0, 1],
                    'removed' => [257, 0, 0, 1, 0],
                    'moved' => [256, 1, 0, 1, 1],
                };
                check(array_values($counts) === $expected, 'Unexpected counts: ' . $scenario);
                if ($scenario === 'changed' || $scenario === 'moved') {
                    check($current->symbols->resolve('func:sample_unit_000') !== $previous->symbols->resolve('func:sample_unit_000'), 'Symbol key did not redirect');
                    check($previous->symbols->resolve('func:sample_unit_000') !== null, 'Old publication lost');
                }
                check(($current->symbols->resolve('func:added_function') !== null) === ($scenario === 'added'), 'Added/removed symbol mismatch');
                $proofs[$scenario] = $counts;
                $previous = $current;
            }
            $clean = $compiler->frontend($root);
            check(array_keys($clean->symbols->declarations) === array_keys($current->symbols->declarations), 'Incremental/full index mismatch');
            rename($root . '/moved.phs', $root . '/' . $target);
            unset($clean, $current, $previous);
            gc_collect_cycles();
        }
        $result = ['php' => PHP_VERSION, 'samples' => 7, 'files' => count($paths),
            'bytes' => array_sum(array_map(static fn(string $p): int => filesize(__DIR__ . '/' . $p), $paths)),
            'tokens' => $token_count, 'raw_ms' => $times, 'timings' => array_map('summary', $times), 'proofs' => $proofs,
            'peak_php_allocated_bytes' => memory_get_peak_usage(true)];
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
    } finally {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) {
            if ($file->isDir()) { rmdir($file->getPathname()); }
            else { unlink($file->getPathname()); }
        }
        rmdir($root);
    }
}
