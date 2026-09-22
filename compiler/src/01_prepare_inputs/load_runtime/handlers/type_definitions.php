<?php
declare(strict_types=1);

/*
 * Role: Load one catalog representation.
 * Used by: Catalog_Syntax (private trait methods on this owner)
 * Call map:
 *   definition()
 *     -> void_definition() / integer_definition() / floating_definition()
 */

namespace load_runtime;

use type_model\integer_addition;
use type_model\named_type_definition;

use type_model\floating_format;
use type_model\floating_representation;
use type_model\integer_representation;
use type_model\representation_kind;
use type_model\representation_record;

/**
 * @compiler-internal Private definition handlers composed by Catalog_Syntax.
 * Consume one decoded row and return a new definition, or throw a catalog error.
 * No I/O or catalog mutation; the syntax owner owns iteration and default resolution.
 * Shared field/lifetime validation stays with the syntax owner.
 */
trait Type_Definition_Loading
{
    /** Validate the common named row and delegate representation-specific facts. */
    private static function definition(mixed $row): named_type_definition
    {
        if ((!is_array($row)) || (!is_string($row['name'] ?? null)) || ($row['name'] === '')
            || (!is_string($row['namespace'] ?? null))) {
            throw new \InvalidArgumentException('Invalid named type definition');
        }
        switch ($row['kind'] ?? null)
        {
            case 'void':
                return self::void_definition($row);
            case 'integer':
                return self::integer_definition($row);
            case 'floating_point':
                return self::floating_definition($row);
            default:
                throw new \InvalidArgumentException('Unsupported named type representation');
        }
    }

    private static function void_definition(array $row): named_type_definition
    {
        self::require_fields($row, ['name', 'namespace', 'kind', 'lifetime']);
        $shape = new representation_record(representation_kind::void_type, null);
        return self::finish_definition($row, $shape);
    }

    /** Validate numeric operations and structural-field eligibility before creating the shared definition. */
    private static function integer_definition(array $row): named_type_definition
    {
        $fields = ['name', 'namespace', 'kind', 'bit_width', 'signed', 'lifetime'];
        foreach (['integer_family', 'addition', 'comparison', 'struct_field'] as $optional) {
            if (array_key_exists($optional, $row)) {
                $fields[] = $optional;
            }
        }
        self::require_fields($row, $fields);
        if (array_key_exists('addition', $row) && ($row['addition'] !== integer_addition::wrapping->value)) {
            throw new \InvalidArgumentException('Unsupported integer addition contract');
        }
        if (array_key_exists('comparison', $row) && ($row['comparison'] !== \type_model\integer_comparison::ordered->value)) {
            throw new \InvalidArgumentException('Unsupported integer comparison contract');
        }
        if (array_key_exists('integer_family', $row) && ((!is_string($row['integer_family'])) || ($row['integer_family'] === ''))) {
            throw new \InvalidArgumentException('Integer conversion family requires a nonempty string');
        }
        if ((!is_int($row['bit_width'] ?? null)) || (!is_bool($row['signed'] ?? null))) {
            throw new \InvalidArgumentException('Integer definition requires width and signedness');
        }
        if (array_key_exists('struct_field', $row) && !is_bool($row['struct_field'])) {
            throw new \InvalidArgumentException('Invalid struct field capability');
        }
        $shape = new representation_record(representation_kind::integer, new integer_representation($row['bit_width']));
        return self::finish_definition($row, $shape, $row['signed']);
    }

    /** Validate the declared floating format and build its semantic definition. */
    private static function floating_definition(array $row): named_type_definition
    {
        self::require_fields($row, ['name', 'namespace', 'kind', 'format', 'lifetime']);
        $format = is_string($row['format'] ?? null) ? floating_format::tryFrom($row['format']) : null;
        if ($format === null) {
            throw new \InvalidArgumentException('Unsupported floating-point format');
        }
        $shape = new representation_record(representation_kind::floating_point, new floating_representation($format));
        return self::finish_definition($row, $shape);
    }

    private static function finish_definition(array $row, representation_record $shape, ?bool $signed = null): named_type_definition
    {
        return new named_type_definition($row['name'], $row['namespace'], $shape, self::lifetime($row['lifetime'], $shape->kind === \type_model\representation_kind::integer), $signed, $row['integer_family'] ?? null,
            isset($row['addition']) ? integer_addition::from($row['addition']) : null, $row['struct_field'] ?? false, comparison: isset($row['comparison']) ? \type_model\integer_comparison::from($row['comparison']) : null);
    }
}
