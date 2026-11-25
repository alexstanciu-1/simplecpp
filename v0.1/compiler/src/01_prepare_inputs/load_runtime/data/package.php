<?php
declare(strict_types=1);
namespace load_runtime;

/** Fixed normalized package data. Only the adapter establishes artifact/receipt acceptance. */
final class Runtime_Package {
    public function __construct(public readonly string $provider, public readonly string $directory,
        public readonly string $target_triple, public readonly string $data_layout,
        public readonly string $link_driver, public readonly array $link_arguments /** vector<string> */,
        private readonly array $type_rows /** hash<Runtime_Type> */,
        private readonly array $callable_rows /** vector<\type_model\Runtime_Callable> */,
        private readonly array $modules /** hash<string> */,
        private readonly array $protected_files /** vector<string> */,
        private readonly string $manifest_source, public readonly \type_model\Type_Catalog $base_catalog,
        public readonly \type_model\Type_Catalog $catalog,
        public readonly array $storage_families /** hash<\type_model\Storage_Family> */,
        public readonly ?Package_Bindings $bindings, public readonly ?Project_Binding $project,
        public readonly array $source_imports /** hash<\prepare_backend\Source_Operation_Export> */) {}

    public function types(): array /** hash<Runtime_Type> */ { return $this->type_rows; }
    public function type_for(string $id): Runtime_Type {
        if (!isset($this->type_rows[$id])) { throw new \OutOfBoundsException('Unknown runtime type: ' . $id); }
        return $this->type_rows[$id];
    }
    public function storage_for(string $id): Runtime_Storage { return $this->type_for($id)->storage; }
    public function callables(): array /** vector<\type_model\Runtime_Callable> */ { return $this->callable_rows; }
    private function externally_owned(string $id): bool {
        $bindings = $this->bindings;
        if ($bindings === null) { return false; }
        return isset($bindings->imports[$id]) || isset($bindings->sources[$id]);
    }
    /** Stable type insertion order, then destroy/copy/move/assign/default; no imported-owner duplication. */
    public function lifecycle_operations(): array /** vector<\type_model\Lifecycle_Operation> */ {
        $operations /** vector<\type_model\Lifecycle_Operation> */ = [];
        $roles /** vector<int> */ = [2,3,4,5,1];
        foreach ($this->type_rows as $type) {
            if ($this->externally_owned($type->id)) { continue; }
            $definition = $type->language_type;
            if ($definition === null) { continue; }
            $lifetime = $definition->lifetime;
            if ($lifetime === null) { continue; }
            foreach ($roles as $role) {
                if ($lifetime->has_operation($role)) { $operations[] = $lifetime->operation($role); }
            }
        }
        return $operations;
    }
    public function module_for(int $kind): string {
        $name = Runtime_Modes::module_name($kind);
        if (!isset($this->modules[$name])) { throw new \RuntimeException('Runtime module variant is unavailable: ' . $name); }
        return $this->modules[$name];
    }
    public function protected_paths(): array /** vector<string> */ { return $this->protected_files; }
    public function manifest_text(): string { return $this->manifest_source; }
    /** Cache eligibility only, after current artifact/receipt validation; never acceptance by itself. */
    public function matches(Package_Context $current): bool {
        if (($this->directory !== $current->directory) || ($this->manifest_source !== $current->manifest_source)
            || ($this->base_catalog !== $current->catalog)) { return false; }
        $a = $this->bindings; $b = $current->bindings;
        if ($a === null) { if ($b !== null) { return false; } }
        else {
            if ($b === null) { return false; }
            if (!Runtime_Package::same_bindings($a,$b)) { return false; }
        }
        $left = $this->project; $right = $current->project;
        if ($left === null) { return $right === null; }
        if ($right === null) { return false; }
        return ($left->receipt === $right->receipt) && Runtime_Package::same_exports($left->exports,$right->exports);
    }
    private static function same_references(array $a /** hash<\type_model\Type_Reference> */, array $b /** hash<\type_model\Type_Reference> */): bool {
        if (q_count($a) !== q_count($b)) { return false; }
        foreach ($a as $key => $reference) {
            if (!isset($b[$key])) { return false; }
            if (!\type_model\Callable_Contracts::reference($reference,$b[$key])) { return false; }
        }
        return true;
    }
    private static function same_exports(array $a /** hash<\prepare_backend\Source_Type_Export> */, array $b /** hash<\prepare_backend\Source_Type_Export> */): bool {
        if (q_count($a) !== q_count($b)) { return false; }
        foreach ($a as $key => $source_export) {
            if (!isset($b[$key])) { return false; }
            if ($source_export !== $b[$key]) { return false; }
        }
        return true;
    }
    private static function same_bindings(Package_Bindings $a, Package_Bindings $b): bool {
        if (!Runtime_Package::same_references($a->types,$b->types)) { return false; }
        if (!Runtime_Package::same_references($a->callables,$b->callables)) { return false; }
        if (q_count($a->imports) !== q_count($b->imports)) { return false; }
        foreach ($a->imports as $key => $owner) {
            if (!isset($b->imports[$key])) { return false; }
            $other = $b->imports[$key];
            // Accepted nominal owner identity is stronger than matching physical/semantic fields.
            if (($owner->provider !== $other->provider) || ($owner->type_id !== $other->type_id)
                || ($owner->type !== $other->type) || ($owner->target_triple !== $other->target_triple)
                || ($owner->data_layout !== $other->data_layout)) { return false; }
        }
        return Runtime_Package::same_exports($a->sources,$b->sources);
    }

}
