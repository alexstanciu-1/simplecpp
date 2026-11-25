<?php
declare(strict_types=1);

/** PHP-only diagnostic: instrument a private compiler copy, never production sources. */
final class Group_Profile
{
    private static function copy_tree(string $source, string $destination): void
    {
        if (!mkdir($destination, 0700, true)) {
            throw new RuntimeException('Cannot create diagnostic directory');
        }
        foreach (new DirectoryIterator($source) as $entry)
        {
            if ($entry->isDot()) {
                continue;
            }
            $target = $destination . '/' . $entry->getFilename();
            if ($entry->isDir()) {
                self::copy_tree($entry->getPathname(), $target);
            }
            elseif (!copy($entry->getPathname(), $target)) {
                throw new RuntimeException('Cannot copy diagnostic input');
            }
        }
    }

    private static function replace(string $path, string $old, string $new): void
    {
        $source = file_get_contents($path);
        if (substr_count($source, $old) !== 1) {
            throw new RuntimeException('Review stale probe location: ' . $old);
        }
        file_put_contents($path, str_replace($old, $new, $source));
    }

    private static function instrument(string $path, string $prefix, string $label, ?string $last = null): void
    {
        $source = file_get_contents($path);
        if (substr_count($source, $prefix) !== 1) {
            throw new RuntimeException('Review stale stage probe: ' . $prefix);
        }
        $start = strpos($source, $prefix);
        $last_start = $last === null ? $start : strpos($source, $last, $start);
        if ($last_start === false) {
            throw new RuntimeException('Review stale stage end: ' . $last);
        }
        $end = strpos($source, ';', $last_start) + 1;
        $statement = substr($source, $start, $end - $start);
        $replacement = '$group_probe_' . $label . ' = \\Stage_Probe::start();' . "\n"
            . $statement . "\n" . '\\Stage_Probe::record(' . var_export($label, true) . ', $group_probe_' . $label . ');';
        file_put_contents($path, substr_replace($source, $replacement, $start, $end - $start));
    }

    public static function run(): void
    {
        $root = dirname(__DIR__, 2);
        $work = sys_get_temp_dir() . '/scpp-groups-' . bin2hex(random_bytes(8));
        if (!mkdir($work, 0700)) {
            throw new RuntimeException('Cannot reserve profile workspace');
        }
        try
        {
            foreach (['src', 'tool_process', 'language', 'tools'] as $part) {
                self::copy_tree($root . '/' . $part, $work . '/' . $part);
            }
            copy($root . '/bootstrap.php', $work . '/bootstrap.php');
            $bench = $work . '/benchmarks/scalability';
            mkdir($bench, 0700, true);
            copy(__DIR__ . '/measure.php', $bench . '/measure.php');
            $configuration = json_decode(file_get_contents($work . '/tools/backend.json'), true, 512, JSON_THROW_ON_ERROR);
            $configuration['compile_jobs'] = 20;
            file_put_contents($work . '/tools/backend.json', json_encode($configuration, JSON_THROW_ON_ERROR));
            $probe = <<<'PROBE'

class Stage_Probe {
    public static array $rows = [];
    public static function start(): array { return [hrtime(true), gc_status()]; }
    public static function record(string $name, array $start): void {
        $elapsed = (hrtime(true) - $start[0]) / 1e9; $gc = gc_status();
        $row = self::$rows[$name] ?? ['seconds' => 0.0, 'gc_seconds' => 0.0, 'calls' => 0];
        $row['seconds'] += $elapsed;
        $row['gc_seconds'] += ($gc['collector_time'] ?? 0) - ($start[1]['collector_time'] ?? 0);
        ++$row['calls']; self::$rows[$name] = $row;
    }
}
PROBE;
            file_put_contents($work . '/bootstrap.php', $probe, FILE_APPEND);
            self::replace($bench . '/measure.php', '$start = hrtime(true);', '\\Stage_Probe::$rows = []; $start = hrtime(true);');
            self::replace($bench . '/measure.php', "\$rows = ['phase' => \$phase,", "\$rows = ['stage_timings' => \\Stage_Probe::\$rows, 'phase' => \$phase,");
            $stages = [
                'manifest' => ['$step = new Manifest_Reader(', '$manifest = $step->result();'],
                'catalog' => ['$step = new \\load_runtime\\Language_Types(', '$catalog = $step->result();'],
                'discovery' => ['$step = new \\read_sources\\Source_Discovery(', '$sources = $step->result();'],
                'tokenize' => '$lexical = Phases::run_tokenization(',
                'parse' => '$inputs->frontends = Phases::run_parsing(',
                'collect' => ['$collection = new Declaration_Collector(', '$symbols = $collection->result();'],
                'entry' => ['$entry_step = new \\resolve_types\\Entry_Resolver(', '$entry = $entry_step->result();'],
                'resolve' => '$resolutions = Phases::run_symbols(',
                'compare' => ['$comparison = new \\collect_symbols\\Symbol_Comparer(', '$symbols = $comparison->result();'],
                'type_prepare' => '$type_store = \\resolve_types\\Type_Cache::prepare(',
                'types' => ['$type_resolver = new \\resolve_types\\Type_Resolver(', '$types = $type_resolver->result();'],
                'bodies' => '$bodies = Phases::run_bodies(',
                'lifetimes' => '$lifetimes = Phases::run_lifetimes(',
                'backend' => ['$backend_step = new \\prepare_backend\\LLVM_Backend(', '$backend = $backend_step->result();'],
                'lower' => '$lowered = Phases::run_lowering(',
                'entry_adapter' => ['$entry_step = new \\lower\\Native_Entry(', '$entry_plan = $entry_step->result();'],
                'emit' => '$llvm = Phases::run_emission(',
                'native' => ['$native_step = new \\build_native\\Native_Builder(', '$candidate = $native_step->result();'],
                'publish' => '$this->publish($result, $candidate);',
            ];
            foreach ($stages as $label => $boundary) {
                [$prefix, $last] = is_array($boundary) ? $boundary : [$boundary, null];
                self::instrument($work . '/src/compile/compile.php', $prefix, $label, $last);
            }
            self::instrument($work . '/src/06_build_output/build_native/main_build_native.php', '$this->toolchain->link_objects(', 'link');
            foreach ([
                    'native_select' => '$this->tasks = self::select(',
                    'native_objects' => '$results = Native_Compiler::compile_batch(',
                    'native_join' => '$objects = (new Native_Join(',
                    'native_paths' => '$object_paths = array_map(',
                    'native_hash' => '$key = hash_file(',
                ] as $label => $prefix) {
                self::instrument($work . '/src/06_build_output/build_native/main_build_native.php', $prefix, $label);
            }
            self::instrument($work . '/src/compile/compile.php', '$result->warnings = $candidate->publish();', 'native_publication');
            $fixture = null;
            foreach (json_decode(file_get_contents($root . '/examples/scalability/inventory.json'), true) as $row) {
                if ($row['project'] === '5mb') {
                    $fixture = $row;
                }
            }
            if ($fixture === null) {
                throw new RuntimeException('Missing fixture');
            }
            $groups = [
                'Inputs, scan and tokenization' => ['manifest', 'catalog', 'discovery', 'tokenize'],
                'Parsing and symbols' => ['parse', 'collect', 'entry', 'resolve', 'compare'],
                'Types, body checks and lifetimes' => ['type_prepare', 'types', 'bodies', 'lifetimes'],
                'Backend preparation and lowering' => ['backend', 'lower'],
                'LLVM emission and entry adapter' => ['entry_adapter', 'emit'],
            ];
            $report = ['method' => 'Three fresh PHP processes; temporary compiler copy with guarded hrtime/gc_status stage timers. No Python or per-Clang wrappers. 20 jobs. Seven disjoint groups; native excludes nested link, final group includes link/publication and untimed coordination residual. GC stays in the executing stage. Table uses arithmetic means so groups sum to mean request time.',
                'fixture' => $fixture, 'backend' => $configuration, 'groups' => $groups, 'trials' => []];
            for ($trial = 1; $trial <= 3; ++$trial)
            {
                $folder = $work . '/trial-' . $trial;
                self::copy_tree($root . '/examples/scalability/5mb', $folder . '/project');
                $stdout = fopen($folder . '/stdout.jsonl', 'w');
                $stderr = fopen($folder . '/stderr.txt', 'w');
                $process = proc_open([PHP_BINARY, '-d', 'memory_limit=2048M', '-d', 'opcache.enable_cli=0', $bench . '/measure.php',
                        $folder . '/project/project.json', $folder . '/program', $folder . '/project/' . $fixture['edit_file']],
                    [0 => ['file', '/dev/null', 'r'], 1 => $stdout, 2 => $stderr], $pipes);
                if (!is_resource($process)) {
                    throw new RuntimeException('Cannot start PHP trial');
                }
                $code = proc_close($process);
                fclose($stdout);
                fclose($stderr);
                if ($code !== 0) {
                    throw new RuntimeException(file_get_contents($folder . '/stderr.txt'));
                }
                $rows = array_map(static fn($line) => json_decode($line, true, 512, JSON_THROW_ON_ERROR), file($folder . '/stdout.jsonl', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
                if (count($rows) !== 3) {
                    throw new RuntimeException('Incomplete measurements');
                }
                foreach ($rows as &$row)
                {
                    $row['groups'] = [];
                    foreach ($groups as $name => $labels) {
                        $row['groups'][$name] = array_sum(array_map(static fn($label) => $row['stage_timings'][$label]['seconds'] ?? 0, $labels));
                    }
                    $row['groups']['Native cache checks and object compilation'] = ($row['stage_timings']['native']['seconds'] ?? 0) - ($row['stage_timings']['link']['seconds'] ?? 0);
                    $row['groups']['Linking, publication and coordination'] = $row['seconds'] - array_sum($row['groups']);
                    if (min($row['groups']) < 0) {
                        throw new RuntimeException('Overlapping timing groups');
                    }
                    echo 'Trial ', $trial, ' ', $row['phase'], ': ', round($row['seconds'], 3), " s\n";
                    flush();
                }
                unset($row);
                $report['trials'][] = ['trial' => $trial, 'measurements' => $rows];
            }
            $report['means'] = [];
            foreach (['cold', 'unchanged', 'body_edit'] as $phase)
            {
                $rows = [];
                foreach ($report['trials'] as $trial) {
                    foreach ($trial['measurements'] as $row) {
                        if ($row['phase'] === $phase) {
                            $rows[] = $row;
                        }
                    }
                }
                $mean = ['seconds' => array_sum(array_column($rows, 'seconds')) / count($rows), 'gc_seconds' => array_sum(array_column($rows, 'gc_seconds')) / count($rows), 'groups' => []];
                $mean['native_parts'] = [];
                foreach (['native_select', 'native_objects', 'native_join', 'native_paths', 'link', 'native_hash', 'native_publication', 'publish'] as $label) {
                    $mean['native_parts'][$label] = array_sum(array_map(static fn($r) => $r['stage_timings'][$label]['seconds'] ?? 0, $rows)) / count($rows);
                }
                foreach (array_keys($rows[0]['groups']) as $name) {
                    $mean['groups'][$name] = array_sum(array_map(static fn($r) => $r['groups'][$name], $rows)) / count($rows);
                }
                $report['means'][$phase] = $mean;
            }
            $destination = $root . '/build/scalability/group-profile.json';
            if (!is_dir(dirname($destination))) {
                mkdir(dirname($destination), 0700, true);
            }
            file_put_contents($destination, json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n");
            echo json_encode($report['means'], JSON_PRETTY_PRINT), "\n";
        }
        finally
        {
            $entries = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($work, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($entries as $entry)
            {
                if ($entry->isDir() && (!$entry->isLink())) {
                    rmdir($entry->getPathname());
                }
                else {
                    unlink($entry->getPathname());
                }
            }
            rmdir($work);
        }
    }
}
Group_Profile::run();
