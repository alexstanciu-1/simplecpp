<?php
declare(strict_types=1);

/*
 * Role: Own version-1 prepared-package format, validation and translation.
 * Used by: Compiler_Session before symbol collection
 * Call map: open() -> read_package() -> Project_Import::validate() [explicit project]; types(); records(); callables()
 * Output: Runtime_Lease with a fixed Runtime_Package; no compiler stage reads JSON fields.
 */

namespace load_runtime;

use type_model\Type_Catalog;

final class Package_Adapter
{
    use Record_Import;
    use Package_Types;
    use Lifecycle_Import;
    use Callable_Import;
    use Resource_Import;
    use Storage_Import;
    use Binding_Import;
    use Package_Syntax;

    /** @compiler-api Lock and validate a prepared package; never run preparation or execute provider code. */
    public static function open(string $path, Type_Catalog $catalog, ?Runtime_Package $previous = null, ?package_bindings $bindings = null, ?project_binding $project = null): Runtime_Lease
    {
        $directory = realpath($path);
        if (($directory === false) || !is_dir($directory)) {
            throw new \RuntimeException('Missing prepared runtime directory: ' . $path);
        }

        // Hold a reader reservation until the session finishes using package artifacts.
        $lock = @fopen($directory . '/.prepare.lock', 'r');
        if (($lock === false) || !flock($lock, LOCK_SH | LOCK_NB)) {
            if ($lock !== false) {
                fclose($lock);
            }
            throw new \RuntimeException('Runtime package is being prepared; cannot acquire reader reservation');
        }

        // Transfer the reservation to the lease only after validation succeeds.
        try {
            $package = self::read_package($directory, $catalog, $previous, $bindings, $project);
            return new Runtime_Lease($lock, $package);
        }
        catch (\Throwable $error) {
            flock($lock, LOCK_UN);
            fclose($lock);
            throw $error;
        }
    }

    /** Verify package contents and target facts before reusing or composing normalized compiler contracts. */
    private static function read_package(string $directory, Type_Catalog $catalog, ?Runtime_Package $previous, ?package_bindings $bindings, ?project_binding $project): Runtime_Package
    {
        // Establish the published manifest and the preparation checks it records.
        $pointer = self::json(self::read($directory . '/current.json'));
        if ((($pointer['schema_version'] ?? null) !== 1) || (($pointer['manifest'] ?? null) !== 'package/manifest.json')) {
            throw new \RuntimeException('Unsupported runtime package pointer format');
        }
        $root = $directory . '/package';
        $manifest_source = self::read($root . '/manifest.json');
        self::verify($manifest_source, $pointer['manifest_sha256'] ?? null, 'runtime manifest');
        $manifest = self::json($manifest_source);
        if ((($manifest['schema_version'] ?? null) !== 1) || !is_string($manifest['provider'] ?? null)
            || ($manifest['provider'] === '') || (($manifest['input_key'] ?? null) !== ($pointer['input_key'] ?? null))
            || (($manifest['validation']['defined_abi_signatures'] ?? false) !== true)
            || (($manifest['validation']['native_link_no_undefined'] ?? null) !== ($project === null))
            || (($manifest['module_kind'] ?? 'runtime') !== ($project === null ? 'runtime' : 'project'))
            || (($project !== null) && (($manifest['validation']['source_imports_validated'] ?? false) !== true))) {
            throw new \RuntimeException('Unsupported or unvalidated runtime manifest');
        }

        // Verify every listed artifact before accepting metadata or implementation paths.
        $artifacts = $manifest['artifacts'] ?? null;
        if (!is_array($artifacts) || ($artifacts === [])) {
            throw new \RuntimeException('Missing runtime artifact list');
        }
        $paths = [$directory . '/.prepare.lock', $directory . '/current.json', $root . '/manifest.json'];
        foreach ($artifacts as $name => $hash) {
            $path = self::artifact_path($root, $name);
            self::verify(self::read($path), $hash, $name);
            $paths[] = $path;
        }

        // Metadata must describe the same provider and target as its verified manifest.
        $metadata_name = $manifest['metadata'] ?? null;
        if (!is_string($metadata_name) || !isset($artifacts[$metadata_name])) {
            throw new \RuntimeException('Unlisted runtime metadata artifact');
        }
        $metadata = self::json(self::read(self::artifact_path($root, $metadata_name)));
        if ((($metadata['schema_version'] ?? null) !== 1) || (($metadata['provider'] ?? null) !== $manifest['provider'])
            || (($metadata['target'] ?? null) !== ($manifest['target'] ?? null))) {
            throw new \RuntimeException('Runtime metadata and manifest disagree');
        }
        $target = $manifest['target'];
        foreach (['triple', 'data_layout'] as $key) {
            if (!is_string($target[$key] ?? null) || ($target[$key] === '')) {
                throw new \RuntimeException('Missing runtime target ' . $key);
            }
        }
        $source_imports = [];
        if ($project !== null) {
            if (!isset($artifacts['project.json'])) {
                throw new \RuntimeException('Missing project source receipt');
            }
            $source_imports = Project_Import::validate($project, self::read($root . '/project.json'), $target);
        }
        foreach ($bindings?->sources ?? [] as $export) {
            if (($project?->exports[$export->task->identity->key] ?? null) !== $export) {
                throw new \RuntimeException('Source payload binding requires its current project export');
            }
        }
        foreach ($bindings?->imports ?? [] as $import) {
            if (!($import instanceof runtime_type_import) || ($import->target_triple !== $target['triple'])
                || ($import->data_layout !== $target['data_layout'])) {
                throw new \RuntimeException('Native type import target mismatch');
            }
        }

        // Preserve the exact toolchain that produced the wrappers for later native linking.
        $driver = $manifest['link_driver']['executable'] ?? null;
        $arguments = $manifest['link_driver']['arguments'] ?? null;
        $driver_path = is_string($driver) ? realpath($driver) : false;
        if (($driver_path === false) || !is_executable($driver)
            || ($arguments !== ['--driver-mode=g++', '--target=' . $target['triple']])) {
            throw new \RuntimeException('Unsupported or missing runtime link driver');
        }
        self::verify(self::read($driver_path), $manifest['inputs']['clang'][$driver_path] ?? null, 'runtime toolchain');
        $paths[] = $driver_path;

        // Keep each bitcode variant distinct; compiler consumption requires an ordinary module.
        $modules = [];
        foreach (self::rows($manifest['modules'] ?? null, 'modules') as $module)
        {
            $kind = match ($module['format'] ?? null) {
                'llvm_bitcode' => runtime_module_kind::ordinary,
                'full_lto_bitcode' => runtime_module_kind::full_lto,
                'thin_lto_bitcode' => runtime_module_kind::thin_lto,
                'llvm_text' => null,
                default => throw new \RuntimeException('Unsupported runtime module format'),
            };
            if (!isset($artifacts[$module['path'] ?? ''])) {
                throw new \RuntimeException('Unlisted runtime module artifact');
            }
            if ($kind !== null) {
                if (isset($modules[$kind->value])) {
                    throw new \RuntimeException('Duplicate runtime module variant');
                }
                $modules[$kind->value] = self::artifact_path($root, $module['path']);
            }
        }
        if (!isset($modules[runtime_module_kind::ordinary->value])) {
            throw new \RuntimeException('Missing ordinary runtime implementation');
        }

        // Reuse shared contracts only after rechecking files, even for an unchanged manifest.
        if ($previous?->matches($directory, $manifest_source, $catalog, $bindings, $project)) {
            return $previous;
        }

        // Compose exposed value definitions and normalized record inputs with the base catalog before binding callables.
        $operations = self::rows($metadata['operations'] ?? null, 'operations');
        $rows = self::rows($metadata['types'] ?? null, 'types');
        $types = self::types($rows, $catalog, $operations, $manifest['provider'], $bindings);

        // Equivalent contracts retain identity across implementation/coverage changes in the same package.
        if (($previous !== null) && ($bindings !== null) && ($previous->directory === $directory)
            && ($previous->base_catalog === $catalog) && ($previous->provider === $manifest['provider'])
            && ($previous->target_triple === $target['triple']) && ($previous->data_layout === $target['data_layout'])) {
            $types = self::retain_bound_types($types, $bindings, $previous);
        }
        $records = self::records($rows, $types, $catalog, $target);
        $families = self::storage_families($rows, $types, $operations, $manifest['provider']);
        $family_ids = array_fill_keys(array_map(static fn($family) => $family->id, $families), true);
        $definitions = $catalog->definitions();
        foreach ($types as $type) {
            if (in_array($type->storage->kind, [runtime_storage_kind::opaque_inline, runtime_storage_kind::byte_span], true) && ($type->language_type !== null) && !isset($family_ids[$type->id])) {
                $definitions[] = $type->language_type;
            }
        }
        $composed = (count($definitions) === count($catalog->definitions())) && ($records === []) && ($families === []) ? $catalog
            : new Type_Catalog($catalog->provider, hash('sha256', $catalog->content_key . $manifest_source),
                $catalog->representation_scope, $definitions, $catalog->integer_literal_type, $catalog->entry_return_type, [...$catalog->records(), ...$records], boolean_type: $catalog->boolean_type);
        $callables = self::callables($operations, $types, $manifest['provider'], $bindings);

        // Added coverage does not replace unchanged callable contracts referenced by retained signatures.
        $old_calls = [];
        foreach ($previous?->callables() ?? [] as $callable) {
            $old_calls[$callable->id] = $callable;
        }
        foreach ($callables as $index => $callable) {
            if (($old_calls[$callable->id] ?? null) == $callable) {
                $callables[$index] = $old_calls[$callable->id];
            }
        }
        return new Runtime_Package($manifest['provider'], $directory, $target['triple'], $target['data_layout'],
            $driver, $arguments, $types, $callables, $modules, $paths, $manifest_source, $catalog, $composed, $families, $bindings, $project, $source_imports);
    }

    private static function artifact_path(string $root, mixed $name): string
    {
        if (!is_string($name) || ($name === '') || ($name === '.') || ($name === '..') || (basename($name) !== $name)
            || is_link($root . '/' . $name)) {
            throw new \RuntimeException('Invalid runtime artifact path');
        }
        return $root . '/' . $name;
    }

    private static function read(string $path): string
    {
        $bytes = @file_get_contents($path);
        if ($bytes === false) {
            throw new \RuntimeException('Cannot read prepared runtime input: ' . $path);
        }
        return $bytes;
    }

    private static function json(string $bytes): array
    {
        $data = json_decode($bytes, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data) || array_is_list($data)) {
            throw new \RuntimeException('Expected runtime JSON object');
        }
        return $data;
    }

    private static function verify(string $bytes, mixed $expected, string $description): void
    {
        if (!is_string($expected) || (hash('sha256', $bytes) !== $expected)) {
            throw new \RuntimeException('Runtime content mismatch: ' . $description);
        }
    }
}
