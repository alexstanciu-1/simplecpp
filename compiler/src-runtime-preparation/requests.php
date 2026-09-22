<?php
declare(strict_types=1);

namespace runtime_preparation;

/** Translate fixed resolved-type demands into ordinary preparation inputs, without compiler stages. */
final class Specialization_Request
{
    /** Build a private export from scalar mappings, provider family contracts and source record descriptions. */
    public static function export(array $request, array $catalog, string $provider): array
    {
        Definitions::fields($request, ['schema_version', 'scope', 'source_types', 'specializations'], 'specialization request');
        Definitions::fields($catalog, ['schema_version', 'types', 'families'], 'family catalog');
        if (($request['schema_version'] !== 1) || ($catalog['schema_version'] !== 1)) {
            throw new \RuntimeException('Unsupported specialization request/catalog schema');
        }
        if (!is_string($request['scope']) || ($request['scope'] === '')) {
            throw new \RuntimeException('Source export requires an exact caller-owned identity scope');
        }
        $provider = Symbols::name(['project', $provider, $request['scope']]);
        $types = self::index($catalog['types']);
        $families = self::index($catalog['families']);
        $sources = self::index($request['source_types']);
        $demands = self::index($request['specializations']);
        $headers = [];
        foreach ($types as $type) {
            if (!in_array($type['kind'] ?? '', ['integer', 'void'], true)) {
                throw new \RuntimeException('Request catalog currently supports scalar mappings only');
            }
            $headers[] = self::header($type['header']);
        }

        $declarations = self::source_declarations($sources, $types, $provider);
        [$native, $operations, $bindings, $family_headers] = self::specializations($demands, $families, $types, $provider);
        $declarations .= $native;
        $headers = [...$headers, ...$family_headers];
        $header = "#pragma once\n";
        $headers = array_values(array_unique($headers));
        sort($headers);
        foreach ($headers as $include) {
            $header .= '#include <' . $include . ">\n";
        }
        return ['provider' => $provider, 'header' => $header . $declarations,
            'definitions' => ['schema_version' => 1, 'types' => array_values($types), 'operations' => array_values($operations)],
            'bindings' => $bindings, 'request' => $request];
    }

    /** Export the supported source representation from semantic field types; offsets come later from Clang. */
    private static function source_declarations(array $sources, array &$types, string $provider): string
    {
        // Generate each concrete source declaration once. Native integer fields implement
        // the prototype's zero-construction/value-copy contract; no S2S class identity is claimed.
        $declarations = '';
        foreach ($sources as $source)
        {
            Definitions::fields($source, ['id', 'name', 'fields'], 'source record');
            $cpp = Symbols::name(['source', $provider, $source['id']]);
            $fields = [];
            $declarations .= 'struct ' . $cpp . " {\n";
            foreach (self::rows($source['fields']) as $field)
            {
                Definitions::fields($field, ['name', 'type', 'writable'], 'source field');
                $type = $types[$field['type']] ?? null;
                if (($type['kind'] ?? '') !== 'integer') {
                    throw new \RuntimeException('Source export requires declared integer fields in this slice');
                }
                $field['member'] = Symbols::name(['field', $field['name']]);
                $fields[] = $field;
                $declarations .= '    ' . $type['cpp_name'] . ' ' . $field['member'] . ";\n";
            }
            $declarations .= "};\n";
            self::add($types, ['id' => $source['id'], 'cpp_name' => $cpp, 'header' => 'source_types.hpp',
                'kind' => 'value_record', 'storage' => 'inline', 'construction' => 'zero', 'copy' => 'value',
                'cleanup' => 'none', 'language_type' => ['name' => $source['name'], 'namespace' => ''], 'fields' => $fields]);
        }

        return $declarations;
    }

    /** Bind each demanded family once and expand its declared operations into existing preparation contracts. */
    private static function specializations(array $demands, array $families, array &$types, string $provider): array
    {
        $catalog = families\Catalog::parse(['schema_version' => 1, 'types' => array_values($types),
            'families' => array_values($families)], $provider);
        $headers = [];
        $declarations = '';
        $operations = [];
        $bindings = [];
        $instances = [];
        foreach ($demands as $demand)
        {
            Definitions::fields($demand, ['id', 'family', 'arguments', 'operations'], 'specialization');
            $family = $catalog->families[$demand['family']] ?? throw new \RuntimeException('Unknown provider family');
            $arguments = families\Requests::arguments($catalog, $family, self::rows($demand['arguments']));
            $selected = self::rows($demand['operations']);
            foreach ($family->semantic->lifecycle as $operation) {
                if (!in_array($operation, $selected, true)) {
                    throw new \RuntimeException('Demand must include declared lifecycle operations');
                }
            }
            $key = json_encode([$family->semantic->key(), $demand['arguments']], JSON_THROW_ON_ERROR);
            if (isset($instances[$key])) {
                throw new \RuntimeException('Duplicate concrete family specialization under different identities');
            }
            $instances[$key] = true;
            $expanded = families\Requests::export($family, $arguments, families\Requests::coverage($family, $selected), $provider, $demand['id']);
            $declarations .= $expanded['declaration'];
            $headers[] = $family->header;
            self::add($types, $expanded['type']);
            foreach ($expanded['operations'] as $operation) {
                self::add($operations, $operation);
            }
            $bindings[$demand['id']] = $expanded['bindings'];
        }
        return [$declarations, $operations, $bindings, $headers];
    }

    /** Index caller-owned identities within one request/catalog; never derive identity from a digest. */
    private static function index(mixed $rows): array
    {
        $result = [];
        foreach (self::rows($rows) as $row) {
            Definitions::identifier($row['id'] ?? null, '/^[a-z][a-z0-9_]*$/D', 'request-local identity');
            self::add($result, $row);
        }
        return $result;
    }

    private static function rows(mixed $rows): array
    {
        if (!is_array($rows) || !array_is_list($rows)) {
            throw new \RuntimeException('Expected ordered request list');
        }
        return $rows;
    }

    private static function add(array &$rows, array $row): void
    {
        if (isset($rows[$row['id']])) {
            throw new \RuntimeException('Duplicate concrete identity: ' . $row['id']);
        }
        $rows[$row['id']] = $row;
    }

    private static function header(mixed $header): string
    {
        Definitions::identifier($header, '/^[A-Za-z0-9_][A-Za-z0-9_\/.+-]*$/D', 'family header');
        if (in_array('..', explode('/', $header), true)) {
            throw new \RuntimeException('Header must be include-root relative');
        }
        return $header;
    }
}
