<?php
declare(strict_types=1);
namespace runtime_preparation;

/** Native argument contract shared by catalog types and accepted package descriptions.
 * Definition rows are projected only at the existing concrete generator boundary.
 * Declarations are prerequisite-ordered aliases; contract/context govern reuse, not identity. */
final class native_type
{
    public function __construct(public readonly \type_model\provider_type_reference $identity,
        public readonly array $definition, public readonly bool $baseline,
        public readonly array $headers = [], public readonly array $declarations = [],
        public readonly string $contract = '', public readonly array $context = [], public readonly array $sources = [])
    {
    }
}

/** Native descriptions belong to preparation, never the compiler's semantic type model. */
final class Native_Types
{
    /** Export owned opaque types; imported rows and compiler-tracked storage retain their existing owners. */
    public static function export(array $types, string $provider, array $context, array $sources = []): array
    {
        $result = [];
        foreach ($types as $id => $type)
        {
            if (($type['kind'] !== 'runtime_value') || isset($type['native_import'])
                || isset($type['source_payload']) || isset($type['resource']) || isset($type['storage_family'])) {
                continue;
            }
            // Family aliases retain their original context, excluding their private generated include directory.
            $preparation = $type['native_preparation'] ?? ['headers' => [$type['header']], 'declarations' => [],
                'contract' => json_encode([$provider, $type, $context], JSON_THROW_ON_ERROR), 'context' => $context];
            $identity = new \type_model\provider_type_reference($provider, $id);
            $definition = array_intersect_key($type, array_flip(['id', 'cpp_name', 'header', 'kind', 'storage']));
            $definition['lifecycle'] = [];
            $definition['native_import'] = ['provider' => $provider, 'id' => $id];
            $baseline = isset($type['lifecycle']['copy_construct'], $type['lifecycle']['copy_assign'])
                && (isset($type['lifecycle']['destroy']) || (($type['lifecycle']['cleanup'] ?? null) === 'none'));
            $result[$id] = new native_type($identity, $definition, $baseline, $preparation['headers'],
                $preparation['declarations'], $preparation['contract'], $preparation['context'], $sources);
        }
        return $result;
    }

    /** Normalize invocation paths once; equivalent ordinary and family contexts compare exactly. */
    public static function context(array $config, string $base): array
    {
        if (!is_string($config['clang'] ?? null) || (($config['clang'] ?? '') === '')
            || !in_array($config['standard'] ?? null, ['c++20', 'c++23'], true)
            || !array_key_exists('target', $config)
            || (($config['target'] !== null) && (!is_string($config['target']) || ($config['target'] === '')))
            || !is_array($config['include_directories'] ?? null) || !array_is_list($config['include_directories'])) {
            throw new \RuntimeException('Invalid native preparation context');
        }
        $directories = [];
        foreach ($config['include_directories'] as $directory)
        {
            if (!is_string($directory) || ($directory === '')) {
                throw new \RuntimeException('Native context requires include directory paths');
            }
            $path = realpath(Files::path($base, $directory));
            if (($path === false) || !is_dir($path)) {
                throw new \RuntimeException('Native context requires existing include directories');
            }
            $directories[] = $path;
        }
        return ['clang' => Files::executable($base, $config['clang']), 'target' => $config['target'],
            'standard' => $config['standard'], 'include_directories' => $directories];
    }

    /** Validate generated prerequisite declarations separately from semantic lifecycle facts. */
    public static function preparation(array $row): void
    {
        Definitions::fields($row, ['headers', 'declarations', 'contract', 'context'], 'native preparation');
        if (!is_array($row['headers']) || !array_is_list($row['headers']) || !is_array($row['declarations'])
            || !is_string($row['contract']) || ($row['contract'] === '') || !is_array($row['context'])) {
            throw new \RuntimeException('Invalid native preparation description');
        }
        foreach ($row['headers'] as $header)
        {
            Definitions::identifier($header, '/^[A-Za-z0-9_][A-Za-z0-9_\/.+-]*$/D', 'native header');
            if (in_array('..', explode('/', $header), true)) {
                throw new \RuntimeException('Native header must be include-root-relative');
            }
        }
        foreach ($row['declarations'] as $name => $text) {
            Definitions::identifier($name, '/^[A-Za-z_][A-Za-z0-9_]*(?:::[A-Za-z_][A-Za-z0-9_]*)*$/D', 'native alias');
            if (!is_string($text) || ($text === '')) {
                throw new \RuntimeException('Invalid native declaration');
            }
        }
        Definitions::fields($row['context'], ['clang', 'target', 'standard', 'include_directories'], 'native context');
        if (self::context($row['context'], '/') !== $row['context']) {
            throw new \RuntimeException('Native context must use resolved paths');
        }
    }

    /** Read from an already authenticated package while its reader reservation is held. */
    public static function from_package(string $directory, string $provider, string $id): native_type
    {
        $manifest = Files::json($directory . '/package/manifest.json');
        $artifact = $manifest['native_types'] ?? null;
        if (($artifact !== 'native_types.json') || !isset($manifest['artifacts'][$artifact])) {
            throw new \RuntimeException('Native type descriptions unavailable; prepare the runtime package again');
        }
        $rows = Files::json($directory . '/package/' . $artifact);
        $type = self::read($rows[$id] ?? []);
        if (($type->identity->provider !== $provider) || ($type->identity->id !== $id)) {
            throw new \RuntimeException('Native argument description changed type identity');
        }
        return $type;
    }

    /** Read a source-dependent native recipe with its explicitly accepted project obligations. */
    public static function from_project_package(string $directory, string $provider, string $id, array $sources): native_type
    {
        $manifest = Files::json($directory . '/package/manifest.json');
        if (($manifest['module_kind'] ?? null) !== 'project'
            || (($manifest['native_types'] ?? null) !== 'native_types.json') || !isset($manifest['artifacts']['native_types.json'])) {
            throw new \RuntimeException('Missing project native type descriptions');
        }
        $rows = Files::json($directory . '/package/native_types.json');
        $row = $rows[$id] ?? [];
        $expected = json_decode(json_encode($sources, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
        if (($sources === []) || (($row['sources'] ?? null) !== $expected)) {
            throw new \RuntimeException('Native recipe lost its current project source dependencies');
        }
        $row['sources'] = [];
        $type = self::read($row);
        if (($type->identity->provider !== $provider) || ($type->identity->id !== $id)) {
            throw new \RuntimeException('Project native recipe changed type identity');
        }
        return new native_type($type->identity, $type->definition, $type->baseline, $type->headers,
            $type->declarations, $type->contract, $type->context, $sources);
    }

    /** Decode serialized preparation descriptions into the same record used by catalog arguments. */
    public static function read(array $row): native_type
    {
        Definitions::fields($row, ['identity', 'definition', 'baseline', 'headers', 'declarations', 'contract', 'context'], 'native argument', ['sources']);
        if (($row['sources'] ?? []) !== []) {
            throw new \RuntimeException('Ordinary native descriptions cannot import project sources');
        }
        Definitions::fields($row['identity'], ['provider', 'id'], 'native argument identity');
        Definitions::validate_type($row['definition']);
        if ((($row['definition']['native_import'] ?? null) !== $row['identity']) || !is_bool($row['baseline'])) {
            throw new \RuntimeException('Invalid prepared native argument');
        }
        self::preparation(array_intersect_key($row, array_flip(['headers', 'declarations', 'contract', 'context'])));
        return new native_type(new \type_model\provider_type_reference($row['identity']['provider'], $row['identity']['id']),
            $row['definition'], $row['baseline'], $row['headers'], $row['declarations'], $row['contract'], $row['context']);
    }
}
