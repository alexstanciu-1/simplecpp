<?php
declare(strict_types=1);
namespace instantiate;
/** Internal normalized storage. Public consumers receive only Instance_View queries. */
final class Instance_State {
    public array $contexts /** hash<Instance_Context,int> */ = [];
    public array $uses /** hash<int> */ = [];
    public array $concrete_types /** hash<\type_model\Named_Definition,int> */ = [];
    public array $type_contexts /** hash<int> */ = [];
    public array $constants /** hash<Template_Argument,int> */ = [];
    public array $constant_owners /** hash<\collect_symbols\Symbol_Record,int> */ = [];
    public array $source_bindings /** hash<\resolve_symbols\Symbol_Resolution,int> */ = [];
    public function __construct(public readonly Instance_Identities $identities) {}
    /** Copy containers and the mutable allocation ledger; share immutable semantic objects. */
    public function fork(): Instance_State {
        $out = new Instance_State($this->identities->fork());
        $out->contexts = $this->contexts; $out->uses = $this->uses; $out->concrete_types = $this->concrete_types;
        $out->type_contexts = $this->type_contexts; $out->constants = $this->constants;
        $out->constant_owners = $this->constant_owners; $out->source_bindings = $this->source_bindings;
        return $out;
    }
}
