<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';

/** PHP-only resident probe: no forced collection and no GC policy changes. Disposable input only. */
final class Resident_Updates
{
    public static function run(array $argv): void
    {
        if (count($argv) !== 4) {
            throw new InvalidArgumentException('Expected disposable manifest, output and fixture edit path');
        }
        if (!array_key_exists('collector_time', gc_status())) {
            throw new RuntimeException('Resident GC probe requires PHP 8.3+');
        }
        [, $manifest, $output, $edit] = $argv;
        $session = new \compile\Compiler_Session();
        $original = file_get_contents($edit);
        $mtime = filemtime($edit);
        $needle = '    return $value;';
        $at = strrpos($original, $needle);
        if ($at === false) {
            throw new RuntimeException('Missing fixture return');
        }
        $report = ['method' => 'One resident PHP process; cold, eight edits each followed by unchanged, invalid edit and repair. No gc_collect_cycles/gc_disable calls. Compile timer includes production work; release of previous result readers is reported separately. Executable verification and source editing are outside timers.', 'rows' => []];

        // Exercise repeated edits and no-op requests in one session, then failure and repair.
        $phases = [['cold', 42]];
        for ($value = 43; $value <= 50; ++$value) {
            $phases[] = ['body_edit', $value];
            $phases[] = ['unchanged', $value];
        }
        $phases[] = ['invalid', null];
        $phases[] = ['repair', 51];
        foreach ($phases as [$phase, $value])
        {
            if (in_array($phase, ['body_edit', 'repair', 'invalid'], true)) {
                $source = $phase === 'invalid' ? 'invalid probe source' : substr_replace($original, '    $value = ' . $value . ';' . "\n" . $needle, $at, strlen($needle));
                file_put_contents($edit, $source);
                touch($edit, $mtime += 2);
                clearstatcache(true, $edit);
            }

            // Keep source editing outside the request timer and retain the accepted baseline for comparison.
            $previous = $session->published;
            $gc = gc_status();
            $start = hrtime(true);
            $failed = false;
            $result = null;
            try {
                $result = $session->compile($manifest, $output);
            }
            catch (Throwable $error) {
                if ($phase !== 'invalid') {
                    throw $error;
                }
                $failed = true;
            }
            $seconds = (hrtime(true) - $start) / 1e9;
            $after = gc_status();
            if ($phase === 'invalid') {
                if ((!$failed) || ($session->published !== $previous)) {
                    throw new RuntimeException('Failure altered publication');
                }
            }
            else
            {
                if ((!$result->completed) || ($result->warnings !== [])) {
                    throw new RuntimeException('Incomplete compile');
                }
                $replaced = 0;
                foreach ($result->native->objects as $object) {
                    if ($previous?->native?->object_for($object->module->source_file_id) !== $object) {
                        ++$replaced;
                    }
                }
                $expected = $phase === 'cold' ? 669 : ($phase === 'unchanged' ? 0 : 1);
                if ($replaced !== $expected) {
                    throw new RuntimeException('Unexpected native selection');
                }
                $process = proc_open([$output], [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
                if ((!is_resource($process)) || (proc_close($process) !== $value)) {
                    throw new RuntimeException('Invalid executable');
                }
            }
            $release = hrtime(true);
            unset($previous, $result, $object);
            $release_seconds = (hrtime(true) - $release) / 1e9;
            $end = gc_status();
            $row = ['phase' => $phase, 'value' => $value, 'seconds' => $seconds, 'release_seconds' => $release_seconds,
                'gc_seconds' => $after['collector_time'] - $gc['collector_time'], 'gc_runs' => $after['runs'] - $gc['runs'],
                'gc_collected' => $after['collected'] - $gc['collected'], 'outside_compile_gc_seconds' => $end['collector_time'] - $after['collector_time'],
                'retained_bytes' => memory_get_usage(false), 'full_rebuild' => $session->published->inputs->context->full_rebuild];
            $report['rows'][] = $row;
            echo json_encode($row, JSON_THROW_ON_ERROR), "\n";
            flush();
        }
        $destination = dirname(__DIR__, 2) . '/build/scalability/resident-updates.json';
        if (!is_dir(dirname($destination))) {
            mkdir(dirname($destination), 0700, true);
        }
        file_put_contents($destination, json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n");
    }
}
Resident_Updates::run($argv);
