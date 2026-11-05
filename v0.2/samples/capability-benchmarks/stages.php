<?php
/** Benchmark only: staged compilation and retained-session edit proofs against a disposable app copy. */
declare(strict_types=1);
namespace scpp\compiler {
    function file_get_contents(string $path): string|false {
        if (in_array(pathinfo($path, PATHINFO_EXTENSION), ['phs', 'scpp', 'jss'], true)) {
            $GLOBALS['source_reads']++;
        }
        return \file_get_contents($path);
    }
}
namespace {
    require __DIR__ . '/../../compiler/boot.php';
    use scpp\compiler\{Module_Loader, Program_Loader, Tokenizer, Parser, Symbol_Publication,
        Program_Preparation, Cpp_Program, entry_target, Project_Compiler, Incremental_Preparation,
        diagnostic, diagnostic_group};
    function check(bool $ok, string $message): void {
        if (!$ok) { throw new RuntimeException($message); }
    }
    function ms(int $start): float { return (hrtime(true) - $start) / 1e6; }
    $app = $argv[1]; $output = $argv[2]; $samples = (int)($argv[3] ?? 5);
    $expected = json_decode(file_get_contents($app . '/expected.json'), true, flags: JSON_THROW_ON_ERROR);
    $target = new entry_target(31);
    $results = ['stages' => [], 'incremental' => []];
    $GLOBALS['source_reads'] = 0;
    check(!function_exists('xdebug_info') || xdebug_info('mode') === [], 'Xdebug must be disabled');
    for ($sample = -1; $sample < $samples; $sample++) {
        $total = hrtime(true); $start = hrtime(true);
        $session = (new Module_Loader())->load($app);
        $row = ['module_ms' => ms($start), 'read_ms' => 0, 'tokenize_ms' => 0, 'parse_collect_ms' => 0];
        $files = []; $tokens_count = 0; $bytes = 0;
        foreach ($session->entry->sources as $unit) {
            $start = hrtime(true); $source = Program_Loader::load($unit); $row['read_ms'] += ms($start);
            $start = hrtime(true); $tokens = (new Tokenizer($source))->tokenize(); $row['tokenize_ms'] += ms($start);
            $start = hrtime(true); $unit->ast = (new Parser($tokens))->parse(); $row['parse_collect_ms'] += ms($start);
            $files[$session->source_key($unit)] = $unit->ast;
            $tokens_count += count($tokens->tokens); $bytes += strlen($source->content);
        }
        $start = hrtime(true); $symbols = Symbol_Publication::completed($files); $row['symbol_index_ms'] = ms($start);
        $start = hrtime(true);
        $program = Program_Preparation::prepare($session->entry_key(), $files, $target, $symbols, $session);
        $row['prepare_ms'] = ms($start);
        $start = hrtime(true); $emitted = Cpp_Program::emit($program); $row['lower_emit_ms'] = ms($start);
        $row['total_ms'] = ms($total);
        if ($sample >= 0) { $results['stages'][] = $row; }
        $results['source_units'] = count($files); $results['source_bytes'] = $bytes; $results['tokens'] = $tokens_count;
        $results['cpp_units'] = count($emitted->translation_units());
        if ($sample === $samples - 1) {
            $results['cpp_files'] = $emitted->translation_units();
            foreach ($emitted->files as $path => $code) {
                $dest = $output . '/' . $path;
                if (!is_dir(dirname($dest))) { mkdir(dirname($dest), 0777, true); }
                file_put_contents($dest, $code);
            }
        }
        unset($session, $files, $symbols, $program, $emitted, $unit, $tokens, $source);
        gc_collect_cycles();
    }
    $edit = $app . '/' . $expected['edit_file']; $original = file_get_contents($edit);
    try {
        for ($sample = 0; $sample < $samples; $sample++) {
            file_put_contents($edit, $original);
            $compiler = new Project_Compiler(); $session = null;
            foreach (['full', 'noop', 'body_edit', 'signature_error', 'recovery', 'noop_after'] as $scenario) {
                if ($scenario === 'body_edit') { file_put_contents($edit, str_replace($expected['body_from'], $expected['body_to'], $original)); }
                if ($scenario === 'signature_error') {
                    file_put_contents($edit, str_replace([$expected['signature_from'], $expected['body_from']],
                        [$expected['signature_to'], $expected['signature_body']], $original));
                }
                if ($scenario === 'recovery') { file_put_contents($edit, $original); }
                $prior = $session?->files() ?? []; $GLOBALS['source_reads'] = 0;
                $start = hrtime(true); $session = $compiler->frontend($app, $session); $frontend = ms($start);
                $reused = 0;
                foreach ($session->files() as $path => $file) {
                    if (isset($prior[$path]) && $prior[$path] === $file) {
                        check($prior[$path]->tokens === $file->tokens, 'Token identity lost'); $reused++;
                    }
                }
                $is_noop = str_starts_with($scenario, 'noop');
                check($GLOBALS['source_reads'] === ($scenario === 'full' ? $results['source_units'] : ($is_noop ? 0 : 1)), 'Unexpected source reads: ' . $scenario);
                check($reused === ($scenario === 'full' ? 0 : $results['source_units'] - ($is_noop ? 0 : 1)), 'AST reuse mismatch');
                $start = hrtime(true); $error = null; $report = null;
                try { $report = Incremental_Preparation::prepare($session, $target); }
                catch (diagnostic|diagnostic_group $caught) { $error = $caught->getMessage(); }
                $preparation = ms($start);
                check(($error !== null) === ($scenario === 'signature_error'), 'Unexpected preparation outcome: ' . ($error ?? $scenario));
                if ($error !== null) { check(str_contains($error, 'conversion'), 'Wrong signature error: ' . $error); }
                if ($is_noop) { check($report['prepared'] === 0, 'No-op prepared bodies'); }
                if ($scenario === 'body_edit') { check($report['prepared'] === 2, 'Body edit invalidated caller'); }
                if ($scenario === 'recovery') { check($report['prepared'] === 3, 'Signature recovery did not rebuild caller'); }
                $results['incremental'][$scenario][] = ['frontend_ms' => $frontend, 'prepare_ms' => $preparation,
                    'total_ms' => $frontend + $preparation, 'source_reads' => $GLOBALS['source_reads'],
                    'reused_asts' => $reused, 'bodies' => $report, 'diagnostic' => $error];
                // This benchmark needs only one previous generation; do not accumulate an edit history.
                $session->previous = null;
            }
            unset($session, $prior, $file); gc_collect_cycles();
        }
    } finally { file_put_contents($edit, $original); }
    $results['peak_php_bytes'] = memory_get_peak_usage(true);
    echo json_encode($results, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n";
}
