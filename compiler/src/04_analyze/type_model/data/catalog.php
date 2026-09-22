<?php
declare(strict_types=1);
namespace type_model;

/** Shared named definitions and normalized records, before canonical materialization. */
final class Type_Catalog {
    private array $rows /** vector<Named_Definition> */ = [];
    private array $by_name /** hash<int> */ = [];
    private array $records /** vector<Record_Declaration> */ = [];
    private array $records_by_name /** hash<int> */ = [];
    public function __construct(public readonly string $provider, public readonly string $content_key,
        public readonly string $representation_scope, array $definitions /** vector<Named_Definition> */,
        public readonly Named_Definition $integer_literal_type, public readonly Named_Definition $entry_return_type,
        public readonly ?Named_Definition $boolean_type, ?array $records /** vector<Record_Declaration> */ = null) {
        if (($provider === '') || ($content_key === '') || ($representation_scope !== 'language_values')) { throw new \InvalidArgumentException('Invalid language catalog identity'); }
        foreach ($definitions as $definition) {
            $key = Type_Catalog::key($definition->name,$definition->namespace_name);
            if (isset($this->by_name[$key])) { throw new \InvalidArgumentException('Duplicate type definition'); }
            $this->by_name[$key] = q_count($this->rows); $this->rows[] = $definition;
        }
        $record_rows /** vector<Record_Declaration> */ = [];
        if (take_nullable($record_rows, $records)) {
            foreach ($record_rows as $record) {
                $key = Type_Catalog::key($record->name, $record->namespace_name);
                if (isset($this->by_name[$key]) || isset($this->records_by_name[$key])) { throw new \InvalidArgumentException('Duplicate structural definition'); }
                $this->records_by_name[$key] = q_count($this->records); $this->records[] = $record;
            }
        }
        $this->require_integer_binding($integer_literal_type);
        $this->require_integer_binding($entry_return_type);
        if ($boolean_type !== null) {
            $this->require_integer_binding($boolean_type);
            if (($boolean_type->representation->bit_width() !== 1) || ($boolean_type->signed !== false)) { throw new \InvalidArgumentException('Boolean binding requires an unsigned one-bit catalog integer'); }
        }
    }
    private static function key(string $name, string $namespace_name): string {
        return '' . string_byte_len($namespace_name) . ':' . $namespace_name . $name;
    }
    private function require_integer_binding(Named_Definition $definition): void {
        if ($this->find_type($definition->name,$definition->namespace_name) !== $definition) { throw new \InvalidArgumentException('Catalog binding must retain an exact catalog definition'); }
        if ($definition->representation->kind() !== \type_model\REPRESENTATION_INTEGER) { throw new \InvalidArgumentException('Catalog default must be an integer definition'); }
    }
    public function size(): int { return q_count($this->rows); }
    public function definition_at(int $index): Named_Definition {
        if (($index < 0) || ($index >= q_count($this->rows))) { throw new \InvalidArgumentException('Invalid catalog definition index'); }
        return $this->rows[$index];
    }
    public function record_count(): int { return q_count($this->records); }
    public function record_at(int $index): Record_Declaration {
        if (($index < 0) || ($index >= q_count($this->records))) { throw new \InvalidArgumentException('Invalid catalog record index'); }
        return $this->records[$index];
    }
    public function find_record(string $name, string $namespace_name): ?Record_Declaration {
        $key = Type_Catalog::key($name, $namespace_name);
        if (!isset($this->records_by_name[$key])) { return null; }
        return $this->records[$this->records_by_name[$key]];
    }
    public function find_type(string $name, string $namespace_name): ?Named_Definition {
        $key = Type_Catalog::key($name,$namespace_name);
        if (!isset($this->by_name[$key])) { return null; }
        return $this->rows[$this->by_name[$key]];
    }
}
