<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';

/** Link identical retained objects; all compared tools are explicit benchmark inputs. */
final class Linker_Probe
{
    public static function run(array $argv): void
    {
        if (count($argv) !== 8) {
            throw new InvalidArgumentException('Expected disposable manifest, output, clang, setsid, bfd, lld and mold paths');
        }
        [, $manifest, $output, $clang, $launcher, $bfd, $lld, $mold] = $argv;
        foreach ([$clang, $launcher, $bfd, $lld, $mold] as $tool) {
            if ((!is_file($tool)) || (!is_executable($tool))) {
                throw new RuntimeException('Missing probe executable: ' . $tool);
            }
        }
        $session = new \compile\Compiler_Session();
        $result = $session->compile($manifest, $output);
        $objects = array_map(static fn($o) => $o->path, $result->native->objects);
        $triple = $result->backend->configuration->target_triple;
        $tools = ['bfd' => $bfd, 'lld' => $lld, 'mold' => $mold];
        $rows = [];
        for ($trial = 0; $trial < 5; ++$trial)
        {
            $names = array_keys($tools);
            $names = array_merge(array_slice($names, $trial % 3), array_slice($names, 0, $trial % 3));
            foreach ($names as $name)
            {
                $path = $output . '-' . $name;
                try
                {
                    $before = getrusage(1);
                    $start = hrtime(true);
                    $process = new \tool_process\Tool_Process([$clang, '--target=' . $triple, '--ld-path=' . $tools[$name],
                            ...$objects, '-o', $path], '', $launcher);
                    try {
                        while (!$process->ready()) {
                            usleep(1000);
                        }
                        $process->result();
                    }
                    finally {
                        $process->close();
                    }
                    $elapsed = (hrtime(true) - $start) / 1e9;
                    $after = getrusage(1);
                    $cpu = ($after['ru_utime.tv_sec'] - $before['ru_utime.tv_sec'])
                        + ($after['ru_stime.tv_sec'] - $before['ru_stime.tv_sec'])
                        + ($after['ru_utime.tv_usec'] - $before['ru_utime.tv_usec']
                        + $after['ru_stime.tv_usec'] - $before['ru_stime.tv_usec']) / 1e6;
                    $process = proc_open([$path], [0 => ['file', '/dev/null', 'r'],
                            1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
                    if ((!is_resource($process)) || (proc_close($process) !== 42)) {
                        throw new RuntimeException('Invalid executable');
                    }
                    clearstatcache(true, $path);
                    $row = ['trial' => $trial + 1, 'linker' => $name, 'seconds' => $elapsed,
                        'child_cpu_seconds' => $cpu, 'executable_bytes' => filesize($path), 'exit_code' => 42];
                    $rows[] = $row;
                    echo json_encode($row, JSON_THROW_ON_ERROR), "\n";
                    flush();
                }
                finally {
                    if (is_file($path)) {
                        unlink($path);
                    }
                }
            }
        }
        $destination = dirname(__DIR__, 2) . '/build/scalability/linker-comparison.json';
        if (!is_dir(dirname($destination))) {
            mkdir(dirname($destination), 0700, true);
        }
        file_put_contents($destination, json_encode(['method' => 'Five rotating-order trials per linker on identical retained objects. Clang plus linker timer excludes object-list preparation, hashing, publication and execution. PHP only.',
                    'object_count' => count($objects), 'target' => $triple, 'clang' => $clang, 'linkers' => $tools, 'rows' => $rows], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n");
    }
}
Linker_Probe::run($argv);
