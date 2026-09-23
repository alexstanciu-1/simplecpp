<?php
declare(strict_types=1);
namespace resolve_types;

/** Local IDs are one-based positions in the exact retained binding owner. */
final class Local_Types {
    public readonly int $callable_id;
    private array $type_ids /** vector<int> */ = [];
    public function __construct(public readonly \resolve_symbols\Symbol_Resolution $names,
        array $type_ids /** vector<int> */,
        public readonly ?\instantiate\Instance_Context $instance = null) {
        if ($instance !== null) {
            if ($instance->definition !== $names->owner) { throw new \LogicException('Local types require their exact instance declaration'); }
        }
        $this->callable_id = $instance === null ? $names->owner->symbol_id : $instance->context_id;
        if (q_count($type_ids) !== $names->locals_count()) { throw new \LogicException('Incomplete local type associations'); }
        foreach ($type_ids as $type_id) {
            if ($type_id < 1) { throw new \LogicException('Invalid local type ID'); }
            $this->type_ids[] = $type_id;
        }
    }
    public function size(): int { return q_count($this->type_ids); }
    public function type_for(int $local_id): int {
        if (($local_id < 1) || ($local_id > q_count($this->type_ids))) { throw new \OutOfBoundsException('Missing resolved local type'); }
        return $this->type_ids[$local_id - 1];
    }
}

/** Accepted association; representation IDs belong to the containing type snapshot.
 * Source facts come from input.owner rather than a second independently writable
 * copy of syntax/declaration/body identity. The join owns currentness and readiness.
 */
final class Callable_Signature {
    public readonly int $callable_id;
    public function __construct(public readonly Callable_Input $input,
        public readonly int $return_annotation_id, public readonly int $representation_id,
        public readonly ?\type_model\Runtime_Callable $external = null,
        public readonly ?\type_model\Storage_Function $storage = null,
        public readonly ?int $receiver_index = null) {
        $this->callable_id = $input->callable_id;
        if (($this->callable_id < 1) || ($representation_id < 1) || ($return_annotation_id < 0)) {
            throw new \InvalidArgumentException('Invalid callable signature identity');
        }
        if ($receiver_index !== null) {
            if ($receiver_index < 0) { throw new \InvalidArgumentException('Invalid callable receiver position'); }
        }
        $owner = $input->owner;
        if ($owner->is_source()) {
            if (($external !== null) || ($storage !== null)) { throw new \InvalidArgumentException('Source signature cannot have a provider origin'); }
        } else {
            if (($external === null) === ($storage === null)) { throw new \InvalidArgumentException('Provider signature requires exactly one callable origin'); }
            if ($return_annotation_id !== 0) { throw new \InvalidArgumentException('Provider signature cannot have a source annotation'); }
            $provider = $owner->provider();
            if ($storage !== null) {
                if ($input->instance === null) { throw new \InvalidArgumentException('Storage signature requires a concrete instance'); }
                if ($provider->kind() !== \collect_symbols\PROVIDER_STORAGE_FUNCTION) { throw new \InvalidArgumentException('Storage signature requires a storage declaration'); }
                if ($provider->storage_function() !== $storage) { throw new \InvalidArgumentException('Storage signature differs from its declaration'); }
            } else {
                if ($provider->kind() === \collect_symbols\PROVIDER_CALLABLE) {
                    if ($provider->callable() !== $external) { throw new \InvalidArgumentException('External signature differs from its declaration'); }
                } elseif ($provider->kind() === \collect_symbols\PROVIDER_METHOD) {
                    if ($input->instance === null) { throw new \InvalidArgumentException('Prepared method requires a concrete instance'); }
                } else { throw new \InvalidArgumentException('Declaration has no external callable contract'); }
            }
        }
    }
}
