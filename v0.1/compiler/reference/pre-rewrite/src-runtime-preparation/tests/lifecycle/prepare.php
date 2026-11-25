<?php
declare(strict_types=1);

namespace runtime_preparation;

require_once dirname(__DIR__, 2) . '/bootstrap.php';

/** Isolated open-module probe: reuses preparation primitives without publishing a runtime package. */
final class Lifecycle_Probe
{
    /** Measure real bridge ABI/layout and retain the expected self-contained-package rejection. */
    public static function run(string $workspace): void
    {
        $base = dirname(__DIR__, 2);
        $config = Files::json($base . '/config.json');
        $config['provider'] = 'lifecycle_probe';
        $config['definitions_directory'] = $workspace . '/definitions';
        $config['output_directory'] = $workspace . '/rejected-package';
        $config['include_directories'] = [...array_map(static fn($path) => realpath(Files::path($base, $path)),
            $config['include_directories']), __DIR__];
        Files::write_json($workspace . '/config.json', $config);

        // The normal publisher must keep rejecting unresolved application imports.
        try {
            (new Runtime_Preparation())->run($workspace . '/config.json');
            throw new \LogicException('Open module was incorrectly accepted as a runtime package');
        }
        catch (\RuntimeException $error) {
            if (!str_contains($error->getMessage(), 'undefined reference')
                || !str_contains($error->getMessage(), 'proof_constructor_body')) {
                throw $error;
            }
            Files::write($workspace . '/package-rejection.log', $error->getMessage());
        }
        if (file_exists($config['output_directory'] . '/current.json')) {
            throw new \RuntimeException('Rejected module was published');
        }

        // These are raw experiment artifacts, deliberately not an accepted package.
        $definitions = new Definitions($config['definitions_directory']);
        $bridge = new Bridge($definitions, $config['provider']);
        $toolchain = new Clang_Toolchain($config, $workspace);
        $llvm = $toolchain->llvm($bridge->source);
        $types = [];
        foreach ($bridge->types as $id => $type) {
            $ast = ($type['kind'] === 'runtime_value')
                ? $toolchain->declarations($bridge->source, $type['cpp_name']) : null;
            $types[$id] = Metadata::type($type, $llvm, $ast);
        }
        $operations = [];
        foreach ($bridge->contracts as $id => $contract) {
            $operations[$id] = Metadata::operation($contract, $types, $llvm);
        }
        Files::write($workspace . '/shell.cpp', $bridge->source);
        Files::write($workspace . '/shell.ll', $llvm);
        Files::write($workspace . '/abi.hpp', $bridge->header);
        Files::write_json($workspace . '/measured.json', ['types' => $types, 'operations' => $operations,
            'target' => Metadata::target($llvm), 'config' => $config, 'clang' => $toolchain->executable,
            'clang_version' => $toolchain->version,
            'offsets' => ['marker' => Metadata::constant($llvm, 'proof_marker_offset'),
                'first' => Metadata::constant($llvm, 'proof_first_offset')]]);
    }
}

Lifecycle_Probe::run($argv[1]);
