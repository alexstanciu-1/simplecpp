<?php
declare(strict_types=1);

namespace runtime_preparation;

/** Isolated request acceptance: verify artifacts and rejoin measured facts with the fixed request. */
final class Prepared_Request
{
    private mixed $lock;
    public readonly array $types;
    public readonly array $operations;
    public readonly array $manifest;
    public readonly string $package;

    /** Hold the package read lock for this consumer's lifetime, including native linking. */
    public function __construct(string $output, Definitions $definitions, string $provider, string $input_key)
    {
        $this->lock = fopen($output . '/.prepare.lock', 'r');
        if (($this->lock === false) || !flock($this->lock, LOCK_SH)) {
            throw new \RuntimeException('Cannot lock prepared request');
        }
        $pointer = Files::json($output . '/current.json');
        if (($pointer['input_key'] !== $input_key) || ($pointer['manifest'] !== 'package/manifest.json')) {
            throw new \RuntimeException('Prepared request publication mismatch');
        }
        $this->package = $output . '/package';
        $accepted = self::validate($this->package, $definitions, $provider, $input_key, $pointer['manifest_sha256']);
        $this->types = $accepted['types'];
        $this->operations = $accepted['operations'];
        $this->manifest = $accepted['manifest'];
    }

    /** Validate a private or leased directory without acquiring another package lock.
     * @return array{types: array, operations: array, manifest: array} */
    public static function validate(string $package, Definitions $definitions, string $provider,
        string $input_key, string $manifest_hash, ?project\module_contract $project = null, ?Clang_Toolchain $toolchain = null): array
    {
        $bytes = Files::read($package . '/manifest.json');
        if (hash('sha256', $bytes) !== $manifest_hash) {
            throw new \RuntimeException('Prepared request manifest integrity mismatch');
        }
        $manifest = Files::object($bytes, 'request manifest');
        if (($manifest['provider'] !== $provider) || ($manifest['input_key'] !== $input_key) || ($manifest['schema_version'] !== 1)) {
            throw new \RuntimeException('Prepared request context mismatch');
        }
        foreach (['metadata.json', 'runtime.ll', 'abi.hpp', 'runtime.bc', 'runtime.lto.bc', 'runtime.thin.bc'] as $required) {
            if (!isset($manifest['artifacts'][$required])) {
                throw new \RuntimeException('Missing authenticated package artifact: ' . $required);
            }
        }
        foreach ($manifest['modules'] as $module) {
            if (!isset($manifest['artifacts'][$module['path']])) {
                throw new \RuntimeException('Unauthenticated module in prepared request');
            }
        }
        foreach ($manifest['artifacts'] as $name => $hash) {
            if ((basename($name) !== $name) || (hash('sha256', Files::read($package . '/' . $name)) !== $hash)) {
                throw new \RuntimeException('Prepared request artifact integrity mismatch');
            }
        }
        if ($project === null) {
            if (($manifest['module_kind'] ?? 'runtime') !== 'runtime' || (($manifest['validation']['native_link_no_undefined'] ?? false) !== true)) {
                throw new \RuntimeException('Ordinary runtime acceptance requires a self-contained package');
            }
        }
        else {
            project\Module::validate($project, $package, $manifest, $toolchain
                ?? throw new \LogicException('Project module acceptance requires its selected toolchain'));
        }
        $metadata = Files::json($package . '/metadata.json');
        $llvm = Files::read($package . '/runtime.ll');
        if (($metadata['provider'] !== $provider) || ($metadata['schema_version'] !== 1)
            || ($metadata['target'] !== $manifest['target']) || (Metadata::target($llvm) !== $metadata['target'])) {
            throw new \RuntimeException('Prepared request target mismatch');
        }

        // Validate the exact requested definitions and each measured result, not merely compatible sizes.
        $expected = new Bridge($definitions, $provider, $project);
        $types = self::index($metadata['types'], $expected->types);
        foreach ($expected->types as $id => $type)
        {
            $ast = null;
            if (isset($types[$id]['declaration_artifact']))
            {
                $artifact = $types[$id]['declaration_artifact'];
                if (!isset($manifest['artifacts'][$artifact])) {
                    throw new \RuntimeException('Missing authenticated declaration artifact');
                }
                $type['declaration_artifact'] = $artifact;
                $ast = Files::read($package . '/' . $artifact);
            }
            if (Metadata::type($type, $llvm, $ast) !== $types[$id]) {
                throw new \RuntimeException('Prepared source/type contract mismatch: ' . $id);
            }
        }
        $operations = self::index($metadata['operations'], $expected->contracts);
        foreach ($expected->contracts as $id => $contract) {
            if (Metadata::operation($contract, $types, $llvm) !== $operations[$id]) {
                throw new \RuntimeException('Prepared operation contract mismatch: ' . $id);
            }
        }
        return ['types' => $types, 'operations' => $operations, 'manifest' => $manifest];
    }

    /** Reject duplicate, missing or unsolicited identities before indexing result rows. */
    private static function index(array $rows, array $expected): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            if (!isset($expected[$row['id']]) || isset($indexed[$row['id']])) {
                throw new \RuntimeException('Unexpected or duplicate prepared identity');
            }
            $indexed[$row['id']] = $row;
        }
        if (count($indexed) !== count($expected)) {
            throw new \RuntimeException('Incomplete prepared request result');
        }
        return $indexed;
    }

    public function __destruct()
    {
        if (is_resource($this->lock)) {
            flock($this->lock, LOCK_UN);
            fclose($this->lock);
        }
    }
}
