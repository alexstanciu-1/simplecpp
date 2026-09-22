<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

/** Diagnostic only: PHP launches the real tools through the production process owner. */
final class PHP_Process_Probe
{
    private static function pool(array $commands, string $launcher): array
    {
        $active = [];
        $next = 0;
        $peak = 0;
        $before = getrusage(1);
        $start = hrtime(true);
        try
        {
            while (($next < count($commands)) || ($active !== []))
            {
                foreach ($active as $index => $process)
                {
                    if (!$process->ready()) {
                        continue;
                    }
                    $process->result();
                    $process->close();
                    unset($active[$index]);
                }
                while (($next < count($commands)) && (count($active) < 20)) {
                    [$arguments, $input] = $commands[$next];
                    $active[$next++] = new \tool_process\Tool_Process($arguments, $input, $launcher);
                    $peak = max($peak, count($active));
                }
                if ($active !== []) {
                    usleep(1000);
                }
            }
        }
        finally {
            foreach ($active as $process) {
                $process->close();
            }
        }
        $seconds = (hrtime(true) - $start) / 1e9;
        $after = getrusage(1);
        $row = ['seconds' => $seconds, 'invocations' => count($commands), 'peak_active_handles' => $peak];
        foreach (['ru_utime' => 'child_user_seconds', 'ru_stime' => 'child_system_seconds'] as $field => $name) {
            $row[$name] = $after[$field . '.tv_sec'] - $before[$field . '.tv_sec']
                + ($after[$field . '.tv_usec'] - $before[$field . '.tv_usec']) / 1e6;
        }
        $row['cpu_equivalents'] = ($row['child_user_seconds'] + $row['child_system_seconds']) / $seconds;
        return $row;
    }

    private static function executable(string $name): string
    {
        foreach (explode(PATH_SEPARATOR, getenv('PATH') ?: '') as $directory) {
            $path = $directory . '/' . $name;
            if (is_file($path) && is_executable($path)) {
                return realpath($path);
            }
        }
        throw new RuntimeException('Cannot locate ' . $name);
    }

    public static function run(): void
    {
        $root = dirname(__DIR__, 2);
        $directory = $root . '/build/scalability/dispatch_probe/ir';
        $files = glob($directory . '/*.ll');
        sort($files);
        if (count($files) !== 669) {
            throw new RuntimeException('First run diagnose_dispatch.py to export the 5 MiB project LLVM, or supply its existing unmodified export');
        }
        $clang = self::executable('clang');
        $launcher = self::executable('setsid');
        $target = trim(file_get_contents($directory . '/target.txt'));
        $work = sys_get_temp_dir() . '/scpp-php-probe-' . bin2hex(random_bytes(8));
        if (!mkdir($work, 0700)) {
            throw new RuntimeException('Cannot reserve probe directory');
        }
        $report = ['method' => 'PHP only: proc_open/proc_get_status/proc_close via production Tool_Process and setsid; no Python process or per-tool wrapper. 20 active handles maximum. IR read before timing; link and exit-42 verification after object timer.',
            'php' => PHP_VERSION, 'clang' => $clang, 'trials' => []];
        try
        {
            // Closest comparison to the user's shell command: one direct proc_open, no setsid.
            for ($trial = 1; $trial <= 5; ++$trial)
            {
                $start = hrtime(true);
                $process = proc_open([$clang, '--version'], [0 => ['file', '/dev/null', 'r'],
                        1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
                if ((!is_resource($process)) || (proc_close($process) !== 0)) {
                    throw new RuntimeException('Direct Clang probe failed');
                }
                $report['trials'][] = ['operation' => 'one_version_direct', 'trial' => $trial, 'seconds' => (hrtime(true) - $start) / 1e9];
            }
            foreach (['version_only', 'compile_objects'] as $operation)
            {
                $commands = [];
                $objects = [];
                foreach ($files as $index => $file) {
                    $object = $work . '/' . $index . '.o';
                    $objects[] = $object;
                    $commands[] = $operation === 'version_only' ? [[$clang, '--version'], '']
                        : [[$clang, '--target=' . $target, '-O0', '-x', 'ir', '-c', '-o', $object, '-'], file_get_contents($file)];
                }
                for ($trial = 1; $trial <= 3; ++$trial)
                {
                    $row = ['operation' => $operation, 'trial' => $trial] + self::pool($commands, $launcher);
                    if ($operation === 'compile_objects')
                    {
                        foreach ($objects as $object) {
                            clearstatcache(true, $object);
                            if ((!is_file($object)) || (filesize($object) === 0)) {
                                throw new RuntimeException('Missing object');
                            }
                        }
                        self::pool([[[$clang, '--target=' . $target, ...$objects, '-o', $work . '/program'], '']], $launcher);
                        $process = proc_open([$work . '/program'], [0 => ['file', '/dev/null', 'r'],
                                1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
                        if ((!is_resource($process)) || (proc_close($process) !== 42)) {
                            throw new RuntimeException('Unexpected executable result');
                        }
                        $row['exit_code'] = 42;
                        foreach ($objects as $object) {
                            unlink($object);
                        }
                        unlink($work . '/program');
                    }
                    $report['trials'][] = $row;
                    echo json_encode($row, JSON_THROW_ON_ERROR), "\n";
                    flush();
                }
            }
            file_put_contents($root . '/build/scalability/php-only-processes.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n");
        }
        finally {
            foreach (glob($work . '/*') as $path) {
                unlink($path);
            }
            rmdir($work);
        }
    }
}

PHP_Process_Probe::run();
