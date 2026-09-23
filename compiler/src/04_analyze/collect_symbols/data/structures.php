<?php
declare(strict_types=1);
namespace collect_symbols;

const SYMBOL_FUNCTION = 1;
const SYMBOL_FILE_ENTRY = 2;
const SYMBOL_STRUCT = 3;
const SYMBOL_TEMPLATE_STRUCT = 4;
const SYMBOL_TEMPLATE_FUNCTION = 5;
const SYMBOL_CONSTANT = 6;
const MAX_SYMBOL_ID = 4294967295;
const CHANGE_ADDED = 1;
const CHANGE_REMOVED = 3;
const CHANGE_UNCOMPARED = 4;

/** File-local syntax facts. Names remain in the retained source until indexing. */
/** @scpp-struct */
final class Declaration_Fact {
    public int $kind /** uint32 */ = 0;
    public int $name_node_id /** uint32 */ = 0;
    public int $declaration_node_id /** uint32 */ = 0;
    public int $body_node_id /** uint32 */ = 0;
    public int $owner_declaration_node_id /** uint32 */ = 0;
    public int $template_parameters_node_id /** uint32 */ = 0;
    public bool $receiver_const = false;
}

/** Source syntax belongs only to a source origin, never to a provider declaration. */
final class Source_Declaration {
    public function __construct(public readonly \parse\Parse_Result $frontend, public readonly Declaration_Fact $fact) {}
}

/** Stable semantic identity with exactly one authoritative origin. Published records are immutable. */
final class Symbol_Record {
    public function __construct(
        public readonly int $symbol_id, public readonly int $owner_symbol_id,
        public readonly string $name, public readonly string $namespace_name,
        private readonly ?Source_Declaration $source_value,
        private readonly ?Provider_Declaration $provider_value) {
        if (($source_value === null) === ($provider_value === null)) { throw new \InvalidArgumentException('Symbol requires exactly one origin'); }
        if ($provider_value !== null) {
            if (($name !== $provider_value->name()) || ($namespace_name !== $provider_value->namespace_name())) { throw new \InvalidArgumentException('Provider symbol spelling differs from its declaration'); }
        }
    }
    public static function from_source(int $id, int $owner, string $name, \parse\Parse_Result $frontend, Declaration_Fact $fact): Symbol_Record {
        return new Symbol_Record($id,$owner,$name,'',new Source_Declaration($frontend,$fact),null);
    }
    public static function from_provider(int $id, int $owner, Provider_Declaration $provider): Symbol_Record {
        return new Symbol_Record($id,$owner,$provider->name(),$provider->namespace_name(),null,$provider);
    }
    public function is_source(): bool { return $this->source_value !== null; }
    public function source(): Source_Declaration {
        $value = $this->source_value;
        if ($value === null) { throw new \LogicException('Provider symbol has no source syntax'); }
        return $value;
    }
    public function source_frontend(): \parse\Parse_Result { return $this->source()->frontend; }
    public function source_fact(): Declaration_Fact { return $this->source()->fact; }
    public function provider(): Provider_Declaration {
        $value = $this->provider_value;
        if ($value === null) { throw new \LogicException('Source symbol has no provider declaration'); }
        return $value;
    }
    public function kind(): int {
        if ($this->is_source()) { return (int)$this->source_fact()->kind; }
        $provider = $this->provider();
        return Symbol_Record::provider_kind($provider);
    }
    public static function provider_kind(Provider_Declaration $provider): int {
        $kind = $provider->kind();
        if (($kind === \collect_symbols\PROVIDER_STORAGE_FAMILY) || ($kind === \collect_symbols\PROVIDER_FAMILY)) { return \collect_symbols\SYMBOL_TEMPLATE_STRUCT; }
        if (($kind === \collect_symbols\PROVIDER_STORAGE_FUNCTION) || ($kind === \collect_symbols\PROVIDER_METHOD)) { return \collect_symbols\SYMBOL_TEMPLATE_FUNCTION; }
        return \collect_symbols\SYMBOL_FUNCTION;
    }
    public function is_template(): bool {
        return ($this->kind() === \collect_symbols\SYMBOL_TEMPLATE_STRUCT) || ($this->kind() === \collect_symbols\SYMBOL_TEMPLATE_FUNCTION);
    }
    public function has_executable_body(): bool {
        if (!$this->is_source()) { return false; }
        if ($this->is_template()) { return false; }
        return (int)$this->source_fact()->body_node_id !== 0;
    }
    public function receiver_const(): bool {
        if ($this->is_source()) { return $this->source_fact()->receiver_const; }
        return $this->provider()->receiver_const();
    }
}

/** Status selects presence in the refresh's previous/current stores; no comparison yet. */
/** @scpp-struct */
final class Symbol_Change {
    public int $symbol_id /** uint32 */ = 0;
    public int $status /** uint32 */ = 0;
}
