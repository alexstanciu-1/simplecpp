<?php
declare(strict_types=1);
namespace type_model;
/** Coordinator-owned candidate; published rows and shapes are immutable shared objects. */
final class Type_Store {
    private array $types /** vector<Type_Record> */ = [];
    private array $representations /** vector<Representation> */ = [];
    private array $members /** vector<Type_Member> */ = [];
    private array $by_name /** hash<int> */ = [];
    private array $by_representation /** hash<int> */ = [];
    /** Internal constructor: use fresh() or fork() to establish lineage correctly. */
    public function __construct(public readonly Type_Context $context, public readonly Type_Lineage $lineage) {}
    public static function fresh(Type_Context $context): Type_Store { return new Type_Store($context,new Type_Lineage()); }
    /** Explicit container copies share immutable rows, never the mutable owner or an ancestor chain. */
    public function fork(): Type_Store {
        $out = new Type_Store($this->context,$this->lineage);
        $out->types = $this->types; $out->representations = $this->representations; $out->members = $this->members;
        $out->by_name = $this->by_name; $out->by_representation = $this->by_representation; return $out;
    }
    public function type_count(): int { return q_count($this->types); }
    public function representation_count(): int { return q_count($this->representations); }
    public function member_count(): int { return q_count($this->members); }
    private static function name_key(string $name, string $namespace_name): string { return '' . string_byte_len($namespace_name) . ':' . $namespace_name . $name; }
    public function find_type(string $name, string $namespace_name): int {
        $key = Type_Store::name_key($name,$namespace_name); if (!isset($this->by_name[$key])) { return 0; } return $this->by_name[$key];
    }
    public function reference_type(string $name, string $namespace_name): int {
        $id = $this->find_type($name,$namespace_name); if ($id !== 0) { return $id; }
        if ($name === '') { throw new \InvalidArgumentException('Empty type name'); }
        $id = q_count($this->types)+1; $this->types[] = new Type_Record($name,$namespace_name,0,false,null);
        $key = Type_Store::name_key($name,$namespace_name); $this->by_name[$key] = $id; return $id;
    }
    public function type_by_id(int $id): Type_Record {
        if (($id < 1) || ($id > q_count($this->types))) { throw new \InvalidArgumentException('Unknown type ID'); }
        return $this->types[$id-1];
    }
    public function is_declared(int $id): bool { return $this->type_by_id($id)->declared; }
    public function needs_representation(int $id): bool { return $this->type_by_id($id)->representation_id === 0; }
    public function declare_type(string $name, string $namespace_name): int {
        $id = $this->reference_type($name,$namespace_name); $old = $this->type_by_id($id);
        if ($old->declared) { throw new \InvalidArgumentException('Duplicate type declaration'); }
        $this->types[$id-1] = new Type_Record($name,$namespace_name,0,true,null); return $id;
    }
    public function invalidate_definition(int $id): void {
        $old = $this->type_by_id($id);
        if (($old->representation_id === 0) && ($old->definition === null)) { return; }
        $this->types[$id-1] = new Type_Record($old->name,$old->namespace_name,0,$old->declared,null);
    }
    public function set_representation(int $id, int $representation_id): void {
        $old = $this->type_by_id($id);
        if (!$old->declared) { throw new \LogicException('Cannot attach representation to undeclared type'); }
        $representation = $this->representation_by_id($representation_id);
        if ($old->definition !== null) { if (!$old->definition->representation->same($representation)) { throw new \LogicException('Representation conflicts with bound definition'); } }
        if ($old->representation_id === $representation_id) { return; }
        $this->types[$id-1] = new Type_Record($old->name,$old->namespace_name,$representation_id,true,$old->definition);
    }
    public function bind_definition(int $id, Named_Definition $definition): void {
        $old = $this->type_by_id($id);
        if (!$old->declared) { throw new \LogicException('Type definition requires a declared type'); }
        if (($old->name !== $definition->name) || ($old->namespace_name !== $definition->namespace_name)) { throw new \LogicException('Type definition does not match declared identity'); }
        if ($old->representation_id !== 0) { if (!$this->representation_by_id($old->representation_id)->same($definition->representation)) { throw new \LogicException('Type definition conflicts with representation'); } }
        if ($old->definition === $definition) { return; }
        if ($old->definition !== null) { throw new \LogicException('Invalidate previous definition before replacement'); }
        $this->types[$id-1] = new Type_Record($old->name,$old->namespace_name,$old->representation_id,true,$definition);
    }
    public function representation_by_id(int $id): Representation {
        if (($id < 1) || ($id > q_count($this->representations))) { throw new \InvalidArgumentException('Unknown representation ID'); }
        return $this->representations[$id-1];
    }
    public function representation_for_type(int $id): Representation {
        $type = $this->type_by_id($id); if ($type->representation_id === 0) { throw new \LogicException('Unresolved type representation'); }
        return $this->representation_by_id($type->representation_id);
    }
    public function definition_for_type(int $id): Named_Definition {
        $type = $this->type_by_id($id); if ($type->definition === null) { throw new \LogicException('Unresolved type definition'); } return $type->definition;
    }
    public function member_at(int $index): Type_Member {
        if (($index < 0) || ($index >= q_count($this->members))) { throw new \InvalidArgumentException('Unknown type member index'); }
        return $this->members[$index];
    }
    private function known(string $key): int { if (!isset($this->by_representation[$key])) { return 0; } return $this->by_representation[$key]; }
    private function intern(string $key, Representation $shape): int {
        $known = $this->known($key); if ($known !== 0) { return $known; }
        $id = q_count($this->representations)+1; $this->representations[] = $shape; $this->by_representation[$key] = $id; return $id;
    }
    public function intern_void(): int {
        $known = $this->known('void'); if ($known !== 0) { return $known; }
        return $this->intern('void',Representation::void_type());
    }
    public function intern_integer(int $width): int {
        $key = 'integer:' . $width; $known = $this->known($key); if ($known !== 0) { return $known; }
        return $this->intern($key,Representation::integer($width));
    }
    public function intern_float(string $format): int {
        $key = 'float:' . $format; $known = $this->known($key); if ($known !== 0) { return $known; }
        return $this->intern($key,Representation::floating($format));
    }
    public function intern_pointer(int $element, int $address_space): int {
        $key = 'pointer:' . $element . ':' . $address_space; $known = $this->known($key); if ($known !== 0) { return $known; }
        $this->type_by_id($element); return $this->intern($key,Representation::pointer($element,$address_space));
    }
    public function intern_array(int $element, int $count): int {
        $key = 'array:' . $element . ':' . $count; $known = $this->known($key); if ($known !== 0) { return $known; }
        $this->type_by_id($element); return $this->intern($key,Representation::fixed_array($element,$count));
    }
    public function intern_byte_span(): int {
        $known = $this->known('byte_span'); if ($known !== 0) { return $known; }
        return $this->intern('byte_span',Representation::byte_span());
    }
    public function intern_opaque(int $size, int $alignment): int {
        $key = 'opaque:' . $size . ':' . $alignment; $known = $this->known($key); if ($known !== 0) { return $known; }
        return $this->intern($key,Representation::opaque($size,$alignment));
    }
    public function intern_structure(array $fields /** vector<Type_Member> */): int {
        $names /** hash<bool> */ = []; $key = 'structure:';
        foreach ($fields as $field) {
            $name = $field->name;
            if (($name === '') || isset($names[$name])) { throw new \InvalidArgumentException('Invalid or duplicate structure field'); }
            $this->type_by_id($field->type_id); $names[$name] = true;
            $key = $key . $field->type_id . ':' . string_byte_len($name) . ':' . $name . ':' . ($field->writable ? '1' : '0') . ';';
        }
        $known = $this->known($key); if ($known !== 0) { return $known; }
        $shape = Representation::structure(q_count($this->members),q_count($fields));
        foreach ($fields as $field) { $this->members[] = $field; }
        return $this->intern($key,$shape);
    }
    public function intern_signature(int $return_type, array $parameters /** vector<int> */, array $passing /** vector<int> */): int {
        $this->type_by_id($return_type);
        foreach ($parameters as $id) { $this->type_by_id($id); }
        $production = Result_Contracts::production($this->representation_for_type($return_type)->kind());
        $shape = Representation::signature($return_type,q_count($this->members),q_count($parameters),$passing,$production);
        $key = 'signature:' . $return_type . ':' . $production . ':';
        foreach ($parameters as $index => $id) { $key = $key . $id . ':' . $shape->parameter_passing($index) . ';'; }
        $known = $this->known($key); if ($known !== 0) { return $known; }
        foreach ($parameters as $id) { $this->members[] = new Type_Member($id,'',true); }
        return $this->intern($key,$shape);
    }
    public function field_index(int $type, string $name): int {
        $shape = $this->representation_for_type($type); if ($shape->kind() !== \type_model\REPRESENTATION_STRUCTURE) { return -1; }
        for ($i = 0; $i < $shape->member_count(); $i++) { if ($this->member_at($shape->member_first()+$i)->name === $name) { return $i; } }
        return -1;
    }
    public function field_for(int $type, int $index): Type_Member {
        $shape = $this->representation_for_type($type);
        if ($shape->kind() !== \type_model\REPRESENTATION_STRUCTURE) { throw new \LogicException('Field projection requires structure'); }
        if (($index < 0) || ($index >= $shape->member_count())) { throw new \LogicException('Invalid field ordinal'); }
        return $this->member_at($shape->member_first()+$index);
    }
    /** Complete source-owned operations in type/role order, without expanding field plans. */
    public function lifecycle_operations(): array /** vector<Lifecycle_Operation> */ {
        $out /** vector<Lifecycle_Operation> */ = [];
        $roles /** vector<int> */ = [\type_model\LIFECYCLE_DEFAULT,\type_model\LIFECYCLE_COPY,\type_model\LIFECYCLE_ASSIGN,\type_model\LIFECYCLE_MOVE,\type_model\LIFECYCLE_DESTROY];
        foreach ($this->types as $type) {
            if ($type->definition === null) { continue; }
            $life = $type->definition->lifetime; if ($life === null) { continue; }
            foreach ($roles as $role) { if ($life->has_operation($role)) { $operation = $life->operation($role); if (!$operation->imported) { $out[] = $operation; } } }
        }
        return $out;
    }
}
