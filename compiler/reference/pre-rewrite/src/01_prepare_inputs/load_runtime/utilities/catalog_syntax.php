<?php
declare(strict_types=1);

/*
 * Role: Validate catalog text and resolve its defaults.
 * Used by: Language_Types::run()
 * Call map: Catalog_Syntax::parse() -> [action] import definitions and resolve defaults
 */

namespace load_runtime;

use type_model\Type_Catalog;
use type_model\cleanup_kind;
use type_model\copy_kind;
use type_model\lifetime_contract;
use type_model\named_type_definition;

use type_model\representation_kind;

// Language definition ingestion. Facts live in the catalog, never in source-name
// branches in semantic consumers. Imports representations and lifetime contracts;
// lifetime analysis and executable actions belong to later stages.
/**
 * @compiler-api Language catalog ingestion used by compile; provides authoritative shared definitions.
 * Readers may reuse the exact previous catalog when input content matches. Runtime
 * ABI/operation imports are deferred; these APIs do not advertise their readiness.
 */
class Catalog_Syntax
{
    use Type_Definition_Loading;

    /**
     * @compiler-internal Decode/validate catalog bytes and build definitions/default bindings.
     * Production ingestion uses Language_Types; no filesystem or type-store mutation here.
     */
    public static function parse(string $content): Type_Catalog
    {
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if ((!is_array($data)) || (($data['schema_version'] ?? null) !== 1)
            || (!is_string($data['provider'] ?? null)) || ($data['provider'] === '')
            || (($data['representation_scope'] ?? null) !== 'language_values')
            || (!is_array($data['types'] ?? null)) || (!array_is_list($data['types']))) {
            throw new \InvalidArgumentException('Expected a version-1 language-value type catalog');
        }
        self::require_fields($data, ['schema_version', 'provider', 'representation_scope', 'types', 'literal_types', 'entry_return_type']);

        // Build representations from catalog facts; consumers must not infer them from type names.
        $definitions = [];
        foreach ($data['types'] as $row) {
            $definitions[] = self::definition($row);
        }

        // Resolve literal and entry defaults to the definitions just loaded.
        $literal_types = $data['literal_types'];
        if (!is_array($literal_types)) {
            throw new \InvalidArgumentException('Expected literal type bindings');
        }
        self::require_fields($literal_types, ['integer', ...(array_key_exists('boolean', $literal_types) ? ['boolean'] : [])]);
        $boolean = array_key_exists('boolean', $literal_types)
            ? self::integer_reference($definitions, $literal_types['boolean'], 'Boolean type') : null;
        $literal_definition = self::integer_reference($definitions, $literal_types['integer'], 'Integer literal type');
        $entry_definition = self::integer_reference($definitions, $data['entry_return_type'], 'Entry return type');
        return new Type_Catalog($data['provider'], hash('sha256', $content), $data['representation_scope'], $definitions,
            $literal_definition, $entry_definition, boolean_type: $boolean);
    }

    /**
     * Resolve a qualified catalog reference to an existing integer definition.
     * @param list<named_type_definition> $definitions
     */
    private static function integer_reference(array $definitions, mixed $reference, string $description): named_type_definition
    {
        if (!is_array($reference)) {
            throw new \InvalidArgumentException($description . ' requires a qualified type reference');
        }
        self::require_fields($reference, ['name', 'namespace']);
        foreach ($definitions as $definition) {
            if (($definition->name === $reference['name']) && ($definition->namespace_name === $reference['namespace'])
                && ($definition->representation->kind === representation_kind::integer)) {
                return $definition;
            }
        }
        throw new \InvalidArgumentException($description . ' must refer to a defined integer');
    }

    /** Read scalar lifetime policy; executable destruction must come from a validated runtime package. */
    private static function lifetime(mixed $data, bool $zero = false): ?lifetime_contract
    {
        if ($data === null) {
            return null;
        }
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Expected a lifetime contract or explicit null');
        }
        self::require_fields($data, ['copy', 'cleanup']);
        $copy = is_string($data['copy']) ? copy_kind::tryFrom($data['copy']) : null;
        $cleanup = is_string($data['cleanup']) ? cleanup_kind::tryFrom($data['cleanup']) : null;
        // This catalog supplies scalar facts, not executable destruction bindings.
        if (!in_array($copy, [copy_kind::value, copy_kind::unavailable], true) || ($cleanup !== cleanup_kind::none)) {
            throw new \InvalidArgumentException('Unsupported lifetime contract');
        }
        // The scalar v1 value policy includes ordinary assignment; imported object operations stay explicit.
        return new lifetime_contract($copy, $cleanup,
            construction: $zero ? \type_model\construction_kind::zero : \type_model\construction_kind::unavailable,
            assignment: $copy === copy_kind::value ? \type_model\assignment_kind::value : \type_model\assignment_kind::unavailable,
            expiring: $copy === copy_kind::value ? \type_model\expiring_construction::value : \type_model\expiring_construction::unavailable);
    }

    private static function require_fields(array $row, array $fields): void
    {
        if ((array_diff(array_keys($row), $fields) !== []) || (array_diff($fields, array_keys($row)) !== [])) {
            throw new \InvalidArgumentException('Unexpected or missing type catalog fields');
        }
    }
}
