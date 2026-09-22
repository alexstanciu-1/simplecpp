<?php
declare(strict_types=1);
namespace load_runtime;

/** Private normalization result: copied membership, shared immutable accepted contracts. */
final class Record_Import_Batch {
    private array $types /** hash<Runtime_Type> */ = [];
    private array $records /** vector<\type_model\Record_Declaration> */ = [];
    public function __construct(array $types /** hash<Runtime_Type> */, array $records /** vector<\type_model\Record_Declaration> */) {
        foreach ($types as $id => $type) { $this->types[$id] = $type; }
        foreach ($records as $record) { $this->records[] = $record; }
    }
    public function type_map(): array /** hash<Runtime_Type> */ {
        $out /** hash<Runtime_Type> */ = [];
        foreach ($this->types as $id => $type) { $out[$id] = $type; }
        return $out;
    }
    public function record_count(): int { return q_count($this->records); }
    public function record_at(int $index): \type_model\Record_Declaration {
        if (($index < 0) || ($index >= q_count($this->records))) { throw new \InvalidArgumentException('Invalid imported record index'); }
        return $this->records[$index];
    }
}

/** Measured scalar fields constrain generated layout; they do not replace semantic fields. */
final class Record_Import {
    private static function text(\scpp\Json_View $row, string $key, string $expected): void {
        if ($row->member($key)->text() !== $expected) { throw new \RuntimeException('Unsupported native record ' . $key); }
    }
    private static function truth(\scpp\Json_View $row, string $key): void {
        if (!$row->member($key)->boolean()) { throw new \RuntimeException('Unverified native record ' . $key); }
    }
    private static function field_name(string $name): void {
        if (string_byte_len($name) === 0) { throw new \RuntimeException('Invalid native field name'); }
        for ($index = 0; $index < string_byte_len($name); $index++) {
            $byte = string_byte_at($name, $index);
            $letter = (($byte >= 65) && ($byte < 91)) || (($byte >= 97) && ($byte < 123)) || ($byte === 95);
            if (!$letter) {
                if (($index === 0) || ($byte < 48) || ($byte > 57)) { throw new \RuntimeException('Invalid native field name'); }
            }
        }
    }
    public static function records(array $rows /** vector<\scpp\Json_View> */, array $types /** hash<Runtime_Type> */,
        \type_model\Type_Catalog $catalog, \scpp\Json_View $target): Record_Import_Batch {
        $updated /** hash<Runtime_Type> */ = [];
        foreach ($types as $id => $type) { $updated[$id] = $type; }
        $records /** vector<\type_model\Record_Declaration> */ = [];
        foreach ($rows as $row) {
            if (!$row->has('kind')) { continue; }
            $kind = $row->member('kind');
            if ($kind->kind() !== 'string') { continue; }
            if ($kind->text() !== 'value_record') { continue; }
            Record_Import::text($row, 'storage', 'inline');
            Record_Import::text($row, 'construction', 'zero');
            Record_Import::text($row, 'copy', 'value');
            Record_Import::text($row, 'cleanup', 'none');
            Record_Import::truth($row->member('validation'), 'complete_public_fields');
            Record_Import::truth($row->member('validation'), 'plain_value_record');
            Record_Import::truth($row->member('declaration'), 'field_offsets_exported');
            $traits = $row->member('cpp_traits');
            Record_Import::truth($traits, 'trivially_copyable');
            Record_Import::truth($traits, 'trivially_destructible');
            Record_Import::truth($traits, 'copy_constructible');
            $name = Package_Syntax::language_name($row->member('language_type'));
            if (($catalog->find_type($name->name(), $name->namespace_name()) !== null) || ($catalog->find_record($name->name(), $name->namespace_name()) !== null)) { throw new \RuntimeException('Duplicate provided record name'); }
            $id = Package_Syntax::identifier($row->member('id'));
            if (!isset($updated[$id])) { throw new \RuntimeException('Unknown runtime record type'); }
            $runtime = $updated[$id];
            $fields /** vector<\type_model\Field_Declaration> */ = [];
            $offsets /** vector<int> */ = [];
            $names /** hash<bool> */ = [];
            $end = 0;
            $raw_fields = Package_Syntax::rows($row->member('fields'), 'record field');
            foreach ($raw_fields as $field) {
                $field_name = $field->member('name')->text(); Record_Import::field_name($field_name);
                if (isset($names[$field_name])) { throw new \RuntimeException('Duplicate native record field'); }
                $scalar_id = Package_Syntax::identifier($field->member('type'));
                if (!isset($updated[$scalar_id])) { throw new \RuntimeException('Unknown native record field type'); }
                $scalar = $updated[$scalar_id];
                $definition = $scalar->language_type;
                if ($definition === null) { throw new \RuntimeException('Native field requires an exposed scalar'); }
                if (($scalar->storage->kind !== \load_runtime\RUNTIME_STORAGE_INTEGER) || (!$definition->struct_field)) { throw new \RuntimeException('Native field requires an eligible scalar'); }
                $writable = $field->member('writable')->boolean();
                $offset = $field->member('offset_bytes')->integer();
                if (($offset < $end) || ($offset > $runtime->storage->size_bytes - $scalar->storage->size_bytes)) { throw new \RuntimeException('Invalid native field offset or extent'); }
                $names[$field_name] = true;
                $end = $offset + $scalar->storage->size_bytes;
                $fields[] = new \type_model\Field_Declaration($field_name, \type_model\Field_Type::named($definition), $writable);
                $offsets[] = $offset;
            }
            $native = new \type_model\Native_Record_Layout($target->member('triple')->text(), $target->member('data_layout')->text(), $runtime->storage->size_bytes, $runtime->storage->alignment_bytes, $offsets);
            $record = new \type_model\Record_Declaration($name->name(), $name->namespace_name(), $fields, true, \type_model\RECORD_LAYOUT_NATIVE_VERIFIED, $native, 0, 0, 0, 0);
            $updated[$id] = new Runtime_Type($runtime->id, $runtime->storage, null, null, null, $record);
            $records[] = $record;
        }
        return new Record_Import_Batch($updated, $records);
    }
}
