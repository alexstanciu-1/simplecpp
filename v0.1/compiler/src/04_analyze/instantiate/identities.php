<?php
declare(strict_types=1);
namespace instantiate;
/** Private allocation history; snapshots/candidates own separate ledgers in one type lineage. */
final class Instance_Identities {
    private array $keys /** hash<int> */ = [];
    private int $next = 1;
    public function __construct(public readonly \type_model\Type_Lineage $lineage, int $next_id = 1) {
        if (($next_id<1) || ($next_id>\collect_symbols\MAX_SYMBOL_ID+1)) { throw new \InvalidArgumentException('Invalid instance allocation watermark'); }
        $this->next=$next_id;
    }
    public function fork(): Instance_Identities {
        $out=new Instance_Identities($this->lineage,$this->next);
        foreach ($this->keys as $key=>$id) { $out->keys[$key]=$id; }
        return $out;
    }
    public function next_id(): int { return $this->next; }
    public function size(): int { return q_count($this->keys); }
    /** Coordinator only: failed materialization may mutate types; discard failed candidates. */
    public function allocate(\type_model\Type_Store $types, int $definition, array $arguments /** vector<Template_Argument> */): int {
        if ($types->lineage!==$this->lineage) { throw new \LogicException('Instance allocation requires its fixed type lineage'); }
        if (($definition<1) || ($definition>\collect_symbols\MAX_SYMBOL_ID)) { throw new \InvalidArgumentException('Invalid template definition identity'); }
        $key=$definition . ':' . q_count($arguments) . ';';
        foreach ($arguments as $argument) {
            $type=\resolve_types\Type_Cache::materialize($types,$argument->type);
            $key=$key . $type . ':';
            if ($argument->value===null) { $key=$key . 't;'; }
            else { $key=$key . 'v' . string_byte_len($argument->value) . ':' . $argument->value . ';'; }
        }
        if (isset($this->keys[$key])) { return $this->keys[$key]; }
        if ($this->next>\collect_symbols\MAX_SYMBOL_ID) { throw new \OverflowException('Template instance identity space exhausted'); }
        $id=$this->next; $this->keys[$key]=$id; $this->next=$id+1; return $id;
    }
}
