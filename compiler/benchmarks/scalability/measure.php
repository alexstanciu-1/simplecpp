<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

// Benchmark transport only: production stages and their selection are unchanged.
class Scalability_Run
{
    private static function require(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    private static function measure(\compile\Compiler_Session $session, string $manifest,
        string $output, string $phase, int $expected): array
    {
        // Reset measurement state before timing only the compiler request.
        $previous = $session->published;
        gc_collect_cycles();
        memory_reset_peak_usage();
        $gc_before = gc_status();
        $start = hrtime(true);
        $result = $session->compile($manifest, $output);
        $seconds = (hrtime(true) - $start) / 1e9;
        $gc_after = gc_status();

        // Capture compiler memory before counters, executable verification or JSON output.
        $peak = memory_get_peak_usage(false);
        $allocated_peak = memory_get_peak_usage(true);
        $retained = memory_get_usage(false);
        self::require(($result->completed) && ($result->native !== null) && ($result->warnings === []), 'Native compilation incomplete');
        self::require($result->inputs->context->full_rebuild === ($phase === 'cold'), 'Unexpected rebuild selection');
        clearstatcache(true, $output);
        $rows = ['phase' => $phase, 'seconds' => $seconds, 'php_peak_bytes' => $peak,
            'gc_runs' => $gc_after['runs'] - $gc_before['runs'],
            'gc_collected' => $gc_after['collected'] - $gc_before['collected'],
            'gc_seconds' => isset($gc_after['collector_time'])
            ? $gc_after['collector_time'] - $gc_before['collector_time'] : null,
            'php_peak_allocated_bytes' => $allocated_peak, 'php_retained_bytes' => $retained,
            'full_rebuild' => $result->inputs->context->full_rebuild, 'tokens' => 0, 'ast_nodes' => 0,
            'checked_statements' => 0, 'lowered_instructions' => 0, 'lowered_bodies' => 0,
            'replaced_frontends' => 0, 'replaced_bodies' => 0, 'replaced_lowered' => 0,
            'artifact_reused' => ($previous !== null) && ($result->native === $previous->native),
            'emitted_modules' => count($result->llvm->modules), 'replaced_objects' => 0,
            'llvm_bytes' => $result->llvm->byte_count(), 'executable_bytes' => filesize($output)];

        // Measure reuse by snapshot identity after the compile timer has stopped.
        foreach ($result->native->objects as $object) {
            if ($previous?->native?->object_for($object->module->source_file_id) !== $object) {
                ++$rows['replaced_objects'];
            }
        }
        foreach ($result->inputs->sources->files as $file)
        {
            $frontend = $result->inputs->frontends->for_file($file->id);
            $rows['tokens'] += count($frontend->tokens->rows);
            $rows['ast_nodes'] += count($frontend->syntax->nodes);
            if (($previous === null) || ($previous->inputs->frontends->for_file($file->id) !== $frontend)) {
                ++$rows['replaced_frontends'];
            }
        }
        foreach ($result->bodies->bodies() as $body) {
            $rows['checked_statements'] += count($body->statements);
            if (($previous === null) || ($previous->bodies->for_symbol($body->owner->symbol_id) !== $body)) {
                ++$rows['replaced_bodies'];
            }
        }
        foreach ($result->lowered->bodies() as $body) {
            ++$rows['lowered_bodies'];
            $rows['lowered_instructions'] += count($body->instructions);
            if (($previous === null) || ($previous->lowered->for_symbol($body->binding->symbol_id) !== $body)) {
                ++$rows['replaced_lowered'];
            }
        }
        self::require(($phase !== 'unchanged') || (($rows['artifact_reused']) && ($rows['replaced_frontends'] === 0)
                && ($rows['replaced_bodies'] === 0) && ($rows['replaced_lowered'] === 0) && ($rows['replaced_objects'] === 0)), 'Unchanged work was recomputed');
        self::require(($phase !== 'body_edit') || ((!$rows['artifact_reused']) && ($rows['replaced_frontends'] === 1) && ($rows['replaced_objects'] === 1)), 'Body edit did not select exactly one file');

        // Verify the native result separately so execution time does not inflate compile time.
        $process = proc_open([$output], [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'],
                2 => ['file', '/dev/null', 'w']], $pipes);
        self::require(is_resource($process), 'Cannot execute compiled sample');
        $rows['exit_code'] = proc_close($process);
        self::require($rows['exit_code'] === $expected, 'Unexpected executable result: ' . $rows['exit_code']);
        return $rows;
    }

    public static function run(array $arguments): void
    {
        if ((count($arguments) !== 4) && (count($arguments) !== 6)) {
            throw new InvalidArgumentException('Expected manifest, output, edited source path and optional toolchain config/log');
        }
        [, $manifest, $output, $edit_path] = $arguments;
        $session = new \compile\Compiler_Session(backend_toolchain_path: $arguments[4] ?? null);
        $tool_count = 0;
        foreach (['cold', 'unchanged', 'body_edit'] as $phase)
        {
            if ($phase === 'body_edit')
            {
                // Only a disposable fixture copy is edited. Make both size and mtime differ.
                $source = file_get_contents($edit_path);
                $mtime = filemtime($edit_path);
                $needle = '    return $value;';
                $position = strrpos($source, $needle);
                self::require($position !== false, 'Missing generated function body');
                $edited = substr_replace($source, '    $value = 43;' . "\n" . $needle, $position, strlen($needle));
                self::require((file_put_contents($edit_path, $edited) === strlen($edited)) && touch($edit_path, $mtime + 2), 'Cannot prepare body edit');
                clearstatcache(true, $edit_path);
            }
            $row = self::measure($session, $manifest, $output, $phase, $phase === 'body_edit' ? 43 : 42);
            if (isset($arguments[5])) {
                $calls = array_map(static fn(string $line): array => json_decode($line, true, 512, JSON_THROW_ON_ERROR),
                    file($arguments[5], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
                $row['tool_calls'] = array_slice($calls, $tool_count);
                $row['tool_seconds'] = array_sum(array_column($row['tool_calls'], 'seconds'));
                $tool_count = count($calls);
            }
            echo json_encode($row, JSON_THROW_ON_ERROR), "\n";
            flush();
        }
    }
}

try {
    Scalability_Run::run($argv);
}
catch (Throwable $error) {
    fwrite(STDERR, (string)$error . "\n");
    exit(1);
}
