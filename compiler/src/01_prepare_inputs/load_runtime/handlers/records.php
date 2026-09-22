<?php
declare(strict_types=1);

/*
 * Role: Normalize verified native record fields at the package-format boundary.
 * Used by: Package_Adapter (private trait methods on this owner)
 * Call map: read_package() -> records()
 * Flow: verified package rows -> normalized declarations without canonical IDs.
 */
namespace load_runtime;

use type_model\Type_Catalog;

trait Record_Import
{
    /** Import complete scalar records with explicit value semantics and independently measured native layout.
     * @param array<string, runtime_type> $types Private adapter output, augmented before publication.
     * @return list<\type_model\record_declaration> */
    private static function records(array $rows, array &$types, Type_Catalog $catalog, array $target): array
    {
        $records = [];
        foreach ($rows as $row)
        {
            if (($row['kind'] ?? null) !== 'value_record') {
                continue;
            }
            if ((($row['storage'] ?? null) !== 'inline') || (($row['construction'] ?? null) !== 'zero')
                || (($row['copy'] ?? null) !== 'value') || (($row['cleanup'] ?? null) !== 'none')
                || (($row['validation']['complete_public_fields'] ?? null) !== true)
                || (($row['validation']['plain_value_record'] ?? null) !== true)
                || (($row['declaration']['field_offsets_exported'] ?? null) !== true)) {
                throw new \RuntimeException('Unsupported or unverified native value-record contract');
            }
            foreach (['trivially_copyable', 'trivially_destructible', 'copy_constructible'] as $trait) {
                if (($row['cpp_traits'][$trait] ?? null) !== true) {
                    throw new \RuntimeException('Native value record lacks required C++ capability');
                }
            }
            [$name, $namespace] = self::language_name($row['language_type'] ?? null);
            if (($catalog->find_type($name, $namespace) !== null) || ($catalog->find_record($name, $namespace) !== null)) {
                throw new \RuntimeException('Duplicate provided record name: ' . $name);
            }

            // Physical facts are constraints on the common generated layout, never a substitute for semantic fields.
            $runtime = $types[$row['id']];
            $fields = [];
            $offsets = [];
            $names = [];
            $end = 0;
            foreach (self::rows($row['fields'] ?? null, 'record fields') as $field)
            {
                $field_name = $field['name'] ?? null;
                $scalar = $types[$field['type'] ?? ''] ?? null;
                $definition = $scalar?->language_type;
                $offset = $field['offset_bytes'] ?? null;
                if (!is_string($field_name) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $field_name)
                    || isset($names[$field_name]) || !is_bool($field['writable'] ?? null)
                    || ($scalar?->storage->kind !== runtime_storage_kind::integer) || !($definition?->struct_field ?? false)
                    || !is_int($offset) || ($offset < $end)
                    || ($offset > ($runtime->storage->size_bytes - $scalar->storage->size_bytes))) {
                    throw new \RuntimeException('Invalid native record field contract or offset');
                }
                $names[$field_name] = true;
                $end = $offset + $scalar->storage->size_bytes;
                $fields[] = new \type_model\field_declaration($field_name, $definition, $field['writable']);
                $offsets[] = $offset;
            }
            $native = new \type_model\native_record_layout($target['triple'], $target['data_layout'],
                $runtime->storage->size_bytes, $runtime->storage->alignment_bytes, $offsets);
            $record = new \type_model\record_declaration($name, $namespace, $fields, true,
                \type_model\record_layout_policy::native_verified, $native);
            $types[$row['id']] = new runtime_type($runtime->id, $runtime->storage, null, null, null, $record);
            $records[] = $record;
        }
        return $records;
    }
}
