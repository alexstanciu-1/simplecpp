<?php
declare(strict_types=1);
namespace check_templates;
/** Accepted producer result, anchored to exact source/catalog/declaration/binding snapshots. */
final class Definition_Result {
    private array $dependencies /** vector<\collect_symbols\Symbol_Record> */ = [];
    private array $bindings /** vector<\resolve_symbols\Symbol_Resolution> */ = [];
    public function __construct(public readonly Definition_Task $task, public readonly \type_model\Type_Catalog $catalog,
        array $dependencies /** vector<\collect_symbols\Symbol_Record> */,
        array $bindings /** vector<\resolve_symbols\Symbol_Resolution> */, public readonly int $visited_nodes) {
        if ($visited_nodes<0) { throw new \InvalidArgumentException('Invalid symbolic visit count'); }
        $declarations /** hash<bool,int> */ = []; $resolutions /** hash<bool,int> */ = [];
        foreach ($dependencies as $dependency) {
            $id=$dependency->symbol_id;
            if (isset($declarations[$id])) { throw new \InvalidArgumentException('Duplicate symbolic declaration dependency'); }
            $declarations[$id]=true; $this->dependencies[]=$dependency;
        }
        foreach ($bindings as $binding) {
            $id=$binding->owner->symbol_id;
            if (isset($resolutions[$id])) { throw new \InvalidArgumentException('Duplicate symbolic binding dependency'); }
            $resolutions[$id]=true; $this->bindings[]=$binding;
        }
    }
    public function current(\collect_symbols\Symbol_Record $owner, \resolve_symbols\Resolution_Set $names, \type_model\Type_Catalog $catalog): bool {
        if ($this->task->owner!==$owner) { return false; }
        if ($this->task->bindings!==$names->for_symbol($owner->symbol_id)) { return false; }
        if ($this->catalog!==$catalog) { return false; }
        foreach ($this->dependencies as $dependency) {
            if ($names->declaration_for($dependency->symbol_id)!==$dependency) { return false; }
        }
        foreach ($this->bindings as $binding) {
            if ($names->for_symbol($binding->owner->symbol_id)!==$binding) { return false; }
        }
        return true;
    }
    public function dependency_count(): int { return q_count($this->dependencies); }
    public function dependency_at(int $index): \collect_symbols\Symbol_Record {
        if (($index<0) || ($index>=q_count($this->dependencies))) { throw new \InvalidArgumentException('Invalid template dependency index'); }
        return $this->dependencies[$index];
    }
}

/** Read-only accepted source permissions. Only the checking producer may create result rows. */
final class Template_Set {
    private array $definitions /** hash<Definition_Result,int> */ = [];
    public function __construct(array $definitions /** vector<Definition_Result> */, public readonly int $selected_count) {
        if ($selected_count<0) { throw new \InvalidArgumentException('Invalid template selection count'); }
        foreach ($definitions as $definition) {
            $id=$definition->task->owner->symbol_id;
            if (isset($this->definitions[$id])) { throw new \InvalidArgumentException('Duplicate template permission result'); }
            $this->definitions[$id]=$definition;
        }
    }
    public function size(): int { return q_count($this->definitions); }
    public function for_definition(int $id): ?Definition_Result {
        if (!isset($this->definitions[$id])) { return null; }
        return $this->definitions[$id];
    }
    public function require_definition(\collect_symbols\Symbol_Record $owner, \resolve_symbols\Resolution_Set $names, \type_model\Type_Catalog $catalog): void {
        if ($owner->is_template()) {
            $result=$this->for_definition($owner->symbol_id);
            if ($result===null) { throw new \LogicException('Source template requires its current definition permission result'); }
            if (!$result->current($owner,$names,$catalog)) { throw new \LogicException('Source template requires its current definition permission result'); }
        }
    }
}
