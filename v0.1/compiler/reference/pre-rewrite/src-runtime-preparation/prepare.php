<?php
declare(strict_types=1);

namespace runtime_preparation;

/** Coordinate input selection, private generation, validation and package publication. */
final class Runtime_Preparation
{
    /** Ordinary packages use the same staged work and publication gate as family requests. */
    public function run(string $configuration_path): array
    {
        $candidate = $this->stage($configuration_path);
        try {
            return $candidate->publish();
        }
        finally {
            $candidate->release();
        }
    }

    /**
     * Reuse or replace one provider package under its output lock; publish only validated, unchanged inputs.
     * @return Package_Candidate Unpublished candidate or locked compatible current package.
     */
    public function stage(string $configuration_path, ?Package_Reservation $reservation = null,
        ?string $request_contract = null, ?project\module_contract $project = null): Package_Candidate
    {
        // Capture configuration and provider contracts before selecting reuse or generation.
        $configuration_path = realpath($configuration_path) ?: throw new \RuntimeException('Missing configuration');
        $base = dirname($configuration_path);
        $configuration_source = Files::read($configuration_path);
        $config = Files::object($configuration_source, $configuration_path);
        $configuration_hash = hash('sha256', $configuration_source);
        $this->validate_configuration($config);
        $definitions = new Definitions(Files::path($base, $config['definitions_directory']));
        $bridge = new Bridge($definitions, $config['provider'], $project);
        $toolchain = new Clang_Toolchain($config, $base);

        // Serialize recovery, reuse and replacement with readers of this output directory.
        $output = Files::path($base, $config['output_directory']);
        Files::directory($output);
        $output = realpath($output);
        if ($reservation === null) {
            $reservation = new Package_Reservation($output);
            $reservation->acquire();
        }
        $lock = $reservation->take($output);
        $private = null;
        try
        {
            // Recover publication first, then compare current input and artifact fingerprints.
            Publication::recover_publication($output);
            $inputs = $this->inputs($configuration_path, $configuration_hash, $definitions, $bridge, $toolchain, $request_contract);
            $key = hash('sha256', json_encode($inputs, JSON_THROW_ON_ERROR));
            $current = $this->reusable($output, $key);
            if ($current !== null) {
                Publication::cleanup_publication($output);
                $candidate = new Package_Candidate($output, $output . '/package', false, $lock, $configuration_path,
                    $configuration_hash, $definitions, $bridge, $toolchain, $inputs, $key, $request_contract, $project);
                $lock = null;
                return $candidate;
            }

            // Build privately; a concurrent input edit must not become an accepted package.
            $private = $output . '/.preparing-' . bin2hex(random_bytes(8));
            Files::directory($private);
            $manifest = $this->build($private, $bridge, $toolchain, $config, $inputs, $key, $project);
            if ($this->inputs($configuration_path, $configuration_hash, $definitions, $bridge, $toolchain, $request_contract) !== $inputs) {
                throw new \RuntimeException('Preparation inputs changed during generation; package not published');
            }

            // Seal the artifact manifest before replacing the stable package and pointer together.
            if ($request_contract !== null) {
                Files::write($private . '/request.json', $request_contract);
            }
            Files::write_json($private . '/commands.json', $toolchain->commands);
            $manifest['artifacts'] = $this->artifacts($private);
            Files::write_json($private . '/manifest.json', $manifest);
            $candidate = new Package_Candidate($output, $private, true, $lock, $configuration_path,
                $configuration_hash, $definitions, $bridge, $toolchain, $inputs, $key, $request_contract, $project);
            $private = null;
            $lock = null;
            return $candidate;
        }
        finally
        {
            try {
                if ($private !== null) {
                    Files::remove_tree($private);
                }
            }
            finally {
                if (is_resource($lock)) {
                    flock($lock, LOCK_UN);
                    fclose($lock);
                }
            }
        }
    }

    /**
     * Reject unsupported configuration fields and malformed paths/tool options before external work.
     * @param array<string, mixed> $config
     */
    private function validate_configuration(array $config): void
    {
        Definitions::fields($config, ['schema_version', 'provider', 'definitions_directory', 'output_directory',
            'include_directories', 'clang', 'target', 'standard'], 'configuration');
        if ($config['schema_version'] !== 1) {
            throw new \RuntimeException('Unsupported configuration schema');
        }
        Definitions::identifier($config['provider'], '/^[A-Za-z][A-Za-z0-9_.]*$/D', 'provider id');
        foreach (['definitions_directory', 'output_directory', 'clang'] as $name) {
            if ((!is_string($config[$name])) || ($config[$name] === '')) {
                throw new \RuntimeException('Expected nonempty configuration ' . $name);
            }
        }
        if ((!is_array($config['include_directories'])) || (!array_is_list($config['include_directories']))) {
            throw new \RuntimeException('Expected include_directories list');
        }
        foreach ($config['include_directories'] as $directory) {
            if ((!is_string($directory)) || ($directory === '')) {
                throw new \RuntimeException('Expected include directory path');
            }
        }
        if (($config['target'] !== null) && ((!is_string($config['target'])) || ($config['target'] === ''))) {
            throw new \RuntimeException('Expected target triple or null');
        }
        if (!in_array($config['standard'], ['c++20', 'c++23'], true)) {
            throw new \RuntimeException('Supported C++ standards: c++20, c++23');
        }
    }

    /**
     * Collect exact dependency paths and content fingerprints; reject changes to already-read definitions.
     * @return array<string, mixed>
     */
    public function inputs(string $configuration, string $configuration_hash, Definitions $definitions,
        Bridge $bridge, Clang_Toolchain $toolchain, ?string $request_contract = null): array
    {
        $sources = [...glob(__DIR__ . '/*.php'), ...glob(__DIR__ . '/families/*.php'), ...glob(__DIR__ . '/project/*.php'),
            ...glob(__DIR__ . '/../src/04_analyze/type_model/data/*.php'),
            __DIR__ . '/../src/04_analyze/type_model/family_contracts.php'];
        $files = glob(dirname($definitions->files[0]) . '/*.json');
        sort($files);
        $definition_hashes = Files::hashes($files);
        if (($files !== $definitions->files) || ($definition_hashes !== $definitions->source_hashes)
            || (hash('sha256', Files::read($configuration)) !== $configuration_hash)) {
            throw new \RuntimeException('Definition/configuration inputs changed after reading');
        }
        $dependencies = $toolchain->dependencies($bridge->source);
        return ['project_contract' => $bridge->project, 'request_contract' => $request_contract, 'configuration' => [$configuration => $configuration_hash], 'definitions' => $definition_hashes,
            'implementation' => Files::hashes([...$sources, __DIR__ . '/../tool_process/process.php']),
            'clang' => Files::hashes([$toolchain->executable]), 'clang_version' => $toolchain->version,
            'headers' => Files::hashes($dependencies), 'bridge_sha256' => hash('sha256', $bridge->source)];
    }

    /** Reuse only a package whose fixed input key, manifest and complete artifact hashes still agree. */
    private function reusable(string $output, string $key): ?string
    {
        if (!is_file($output . '/current.json')) {
            return null;
        }
        try
        {
            $pointer = Files::json($output . '/current.json');
            if (($pointer['input_key'] ?? '') !== $key) {
                return null;
            }
            $relative = $pointer['manifest'] ?? '';
            if ($relative !== 'package/manifest.json') {
                return null;
            }
            $manifest_path = $output . '/' . $relative;
            if (hash('sha256', Files::read($manifest_path)) !== $pointer['manifest_sha256']) {
                return null;
            }
            $manifest = Files::json($manifest_path);
            if (($manifest['input_key'] !== $key) || ($manifest['schema_version'] !== 1) || ($manifest['artifacts'] === [])) {
                return null;
            }
            foreach ($manifest['artifacts'] as $name => $hash) {
                if ((!is_string($name)) || (basename($name) !== $name)
                    || (hash('sha256', Files::read(dirname($manifest_path) . '/' . $name)) !== $hash)) {
                    return null;
                }
            }
            return $manifest_path;
        }
        catch (\Throwable) {
            return null;
        }
    }

    /**
     * Generate and validate metadata plus LLVM variants in a private directory, returning an unpublished manifest.
     * @param array<string, mixed> $config
     * @param array<string, mixed> $inputs
     * @return array<string, mixed>
     */
    private function build(string $directory, Bridge $bridge, Clang_Toolchain $toolchain,
        array $config, array $inputs, string $key, ?project\module_contract $project): array
    {
        // The generated bridge is the common source of layout facts and callable ABI signatures.
        Files::write($directory . '/runtime.cpp', $bridge->source);
        Files::write($directory . '/abi.hpp', $bridge->header);
        $llvm = $toolchain->llvm($bridge->source);
        Files::write($directory . '/runtime.ll', $llvm);
        $target = Metadata::target($llvm);
        $types = [];
        foreach ($bridge->types as $id => $type)
        {
            $ast = null;
            if (in_array($type['kind'], ['runtime_value', 'value_record'], true)) {
                $ast = $toolchain->declarations($bridge->source, $type['cpp_name']);
                // Diagnostic file slots are package-local, not type identities.
                // Full escaped link names must not become filesystem name limits.
                $type['declaration_artifact'] = 'type_' . (count($types) + 1) . '.ast.jsonstream';
                Files::write($directory . '/' . $type['declaration_artifact'], $ast);
            }
            $types[$id] = Metadata::type($type, $llvm, $ast);
        }
        $operations = [];
        foreach ($bridge->contracts as $id => $contract) {
            $operations[$id] = Metadata::operation($contract, $types, $llvm);
        }

        // Each link variant must preserve the same target and exported operation contracts.
        $toolchain->bitcode($llvm, $directory . '/runtime.bc');
        $toolchain->lto($bridge->source, 'full', $directory . '/runtime.lto.bc');
        $toolchain->lto($bridge->source, 'thin', $directory . '/runtime.thin.bc');
        $imports = $project === null ? [] : ['runtime.ll' => project\Module::imports($project, $llvm)];
        foreach (['runtime.bc', 'runtime.lto.bc', 'runtime.thin.bc'] as $artifact)
        {
            $module = $toolchain->inspect_bitcode($directory . '/' . $artifact);
            if (Metadata::target($module) !== $target) {
                throw new \RuntimeException('Target mismatch in ' . $artifact);
            }
            if ($project !== null) {
                $imports[$artifact] = project\Module::imports($project, $module);
            }
            foreach ($operations as $operation) {
                Metadata::operation($operation, $types, $module);
            }
        }
        if ($project === null) {
            $toolchain->verify_link($directory . '/runtime.bc', $target['triple'], $directory . '/link-check.so');
            unlink($directory . '/link-check.so');
        }
        else {
            Files::write_json($directory . '/project.json', ['schema_version' => 1, 'contract' => $project, 'required_imports' => $imports]);
        }

        // Publication and complete artifact fingerprints belong to run(), after input revalidation.
        $metadata = ['schema_version' => 1, 'provider' => $config['provider'], 'target' => $target,
            'types' => array_values($types), 'operations' => array_values($operations)];
        Files::write_json($directory . '/metadata.json', $metadata);
        $context = Native_Types::context($config, dirname(array_key_first($inputs['configuration'])));
        Files::write_json($directory . '/native_types.json', Native_Types::export($bridge->types, $config['provider'], $context, $project?->sources ?? []));
        return ['schema_version' => 1, 'provider' => $config['provider'], 'input_key' => $key,
            'target' => $target, 'inputs' => $inputs, 'metadata' => 'metadata.json', 'native_types' => 'native_types.json', 'abi_header' => 'abi.hpp',
            'module_kind' => $project === null ? 'runtime' : 'project',
            'validation' => ['defined_abi_signatures' => true, 'native_link_no_undefined' => $project === null,
                'source_imports_validated' => $project !== null],
            'link_driver' => ['executable' => $toolchain->executable, 'arguments' => ['--driver-mode=g++', '--target=' . $target['triple']]],
            'modules' => [['path' => 'runtime.ll', 'format' => 'llvm_text', 'optimization' => 'O0'],
                ['path' => 'runtime.bc', 'format' => 'llvm_bitcode', 'optimization' => 'O0'],
                ['path' => 'runtime.lto.bc', 'format' => 'full_lto_bitcode', 'optimization' => 'O2'],
                ['path' => 'runtime.thin.bc', 'format' => 'thin_lto_bitcode', 'optimization' => 'O2']]];
    }

    /**
     * Fingerprint the completed private artifacts by filename for later integrity checks.
     * @return array<string, string>
     */
    private function artifacts(string $directory): array
    {
        $artifacts = [];
        foreach (new \FilesystemIterator($directory) as $file) {
            if ($file->isFile()) {
                $artifacts[$file->getFilename()] = hash('sha256', Files::read($file->getPathname()));
            }
        }
        ksort($artifacts);
        return $artifacts;
    }

}
