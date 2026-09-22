<?php
declare(strict_types=1);

namespace runtime_preparation;

require_once dirname(__DIR__, 2) . '/bootstrap.php';

/** Measure an isolated module with source imports; never publish it as a runtime package. */
final class Source_Operations_Probe
{
    /** Reuse the actual bridge generator and metadata extractor on fixed fixture definitions. */
    public static function run(string $workspace): void
    {
        $base = dirname(__DIR__, 2);
        $config = Files::json($base . '/config.json');
        $config['include_directories'] = [...array_map(static fn($path) => realpath(Files::path($base, $path)),
            $config['include_directories']), __DIR__];
        $definitions = new Definitions($workspace . '/definitions');
        $bridge = new Bridge($definitions, 'source_operations_probe');
        $toolchain = new Clang_Toolchain($config, $workspace);
        $source = "#define PROBE_LAYOUT_FACTS\n" . $bridge->source;
        $llvm = $toolchain->llvm($source);
        $types = [];
        foreach ($bridge->types as $id => $type) {
            $ast = ($type['kind'] === 'runtime_value')
                ? $toolchain->declarations($source, $type['cpp_name']) : null;
            $types[$id] = Metadata::type($type, $llvm, $ast);
        }
        $operations = [];
        foreach ($bridge->contracts as $id => $contract) {
            $operations[$id] = Metadata::operation($contract, $types, $llvm);
        }
        $layout = [];
        foreach (['size', 'alignment', 'first', 'second', 'tag'] as $fact) {
            $layout[$fact] = Metadata::constant($llvm, 'source_' . $fact);
        }
        Files::write($workspace . '/native.cpp', $source);
        Files::write($workspace . '/native.ll', $llvm);
        Files::write_json($workspace . '/measured.json', ['types' => $types, 'operations' => $operations,
            'layout' => $layout, 'target' => Metadata::target($llvm), 'config' => $config,
            'payload_boundary' => Metadata::signature($llvm, 'native_payload_roundtrip'),
            'clang' => $toolchain->executable, 'clang_version' => $toolchain->version]);
    }
}

Source_Operations_Probe::run($argv[1]);
