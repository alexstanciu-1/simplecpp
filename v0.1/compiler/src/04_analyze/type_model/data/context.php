<?php
declare(strict_types=1);
namespace type_model;

/** Canonical IDs belong to a shared lineage; equal context keys alone do not share IDs. */
final class Type_Lineage {}

/** Complete producer-supplied version/content keys; paths or timestamps are not defaults. */
final class Type_Context {
    public function __construct(public readonly string $configuration_key,
        public readonly string $provider_key, public readonly string $target_key) {
        if (($configuration_key === '') || ($provider_key === '') || ($target_key === '')) {
            throw new \InvalidArgumentException('Type context requires configuration, provider and target keys');
        }
    }
}

/** A member type ID is distinct from its zero-based position in a store range. */
final class Type_Member {
    public function __construct(public readonly int $type_id, public readonly string $name, public readonly bool $writable) {
        if ($type_id < 1) { throw new \InvalidArgumentException('Invalid member type'); }
    }
}
