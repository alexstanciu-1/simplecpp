<?php
declare(strict_types=1);

namespace runtime_preparation;

/** Verify the complete plain native-record contract before advertising direct fields or value operations. */
final class Record_Exposure
{
    /** Validate explicit language permissions and references; native capabilities are checked separately by Clang. */
    public static function validate(array $type, array $types): void
    {
        if (($type['storage'] !== 'inline') || ($type['construction'] !== 'zero')
            || ($type['copy'] !== 'value') || ($type['cleanup'] !== 'none')
            || !isset($type['language_type']) || !is_array($type['fields'])
            || !array_is_list($type['fields']) || ($type['fields'] === [])) {
            throw new \RuntimeException('Unsupported value-record construction, copy or storage contract');
        }
        $names = [];
        $members = [];
        foreach ($type['fields'] as $field)
        {
            if (!is_array($field)) {
                throw new \RuntimeException('Expected record field definition');
            }
            Definitions::fields($field, ['name', 'member', 'type', 'writable'], 'record field');
            foreach (['name', 'member'] as $key) {
                Definitions::identifier($field[$key], '/^[A-Za-z_][A-Za-z0-9_]*$/D', 'record field ' . $key);
            }
            Definitions::identifier($field['type'], '/^[a-z][a-z0-9_.]*$/D', 'field type');
            if (isset($names[$field['name']]) || isset($members[$field['member']]) || !is_bool($field['writable'])
                || (($types[$field['type']]['kind'] ?? null) !== 'integer')
                || !isset($types[$field['type']]['language_type'])) {
                throw new \RuntimeException('Duplicate field or unsupported record field type');
            }
            $names[$field['name']] = true;
            $members[$field['member']] = true;
        }
    }

    /** Emit exact native type/offset checks; no host execution, guessed padding or name-based special cases. */
    public static function source(array $type, array $types): string
    {
        $cpp = $type['cpp_name'];
        $source = '';
        foreach (['is_aggregate', 'is_standard_layout', 'is_trivially_copyable', 'is_trivially_destructible',
                'is_trivially_default_constructible', 'is_copy_assignable'] as $trait) {
            $source .= 'static_assert(std::' . $trait . '_v<' . $cpp . '>, "unsupported value-record capability");' . "\n";
        }
        foreach ($type['fields'] as $field)
        {
            // decltype preserves cv/ref qualifiers: const/reference and mismatched scalar members are unsupported.
            $source .= 'static_assert(std::is_same_v<decltype(std::declval<' . $cpp . '>().' . $field['member']
                . '), ' . $types[$field['type']]['cpp_name'] . '>, "record field type mismatch");' . "\n";
            $symbol = Symbols::append(Symbols::append($type['fact_prefix'], 'field'), $field['name']);
            $source .= 'extern "C" const std::uint64_t ' . $symbol . ' = offsetof(' . $cpp . ', ' . $field['member'] . ');' . "\n";
        }
        return $source;
    }

    /** Require a complete public field list before attaching the Clang-measured offsets. */
    public static function measure(array $record, string $llvm): array
    {
        $declaration = $record['declaration'];
        $fields = array_values(array_filter($declaration['members'], static fn($member) => $member['kind'] === 'FieldDecl'));
        if (!in_array($declaration['kind'], ['struct', 'class'], true) || ($declaration['has_bases']) || (count($fields) !== count($record['fields']))) {
            throw new \RuntimeException('Value records require all native fields and no bases');
        }
        foreach ($record['fields'] as $index => $field)
        {
            $actual = $fields[$index];
            if (($actual['name'] !== $field['member']) || ($actual['access'] !== 'public')
                || ($actual['bit_field']) || ($actual['initializer'])) {
                throw new \RuntimeException('Value-record fields must match public native declaration order without defaults or bit fields');
            }
            $symbol = Symbols::append(Symbols::append($record['fact_prefix'], 'field'), $field['name']);
            $record['fields'][$index]['offset_bytes'] = Metadata::constant($llvm, $symbol);
        }
        $record['declaration']['field_offsets_exported'] = true;
        $record['validation'] = ['complete_public_fields' => true, 'plain_value_record' => true];
        return $record;
    }
}
