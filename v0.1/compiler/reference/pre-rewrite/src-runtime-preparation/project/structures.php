<?php
declare(strict_types=1);
namespace runtime_preparation\project;

/** Portable compiler import. Native preparation does not retain its compiler implementation or local IDs. */
final class source_import {
    public function __construct(public readonly string $symbol, public readonly string $role,
        public readonly array $abi, public readonly array $semantics)
    {
    }
}

/** Version-one source payload contract; field provenance invalidates native artifacts independently of nominal identity. */
final class source_type
{
    public const PROFILE = 'inline_source_payload_v1';

    /** @param array<string, source_import|null> $operations All six roles, null only with an unavailable state. */
    public function __construct(public readonly string $project, public readonly string $key,
        public readonly array $target, public readonly int $size, public readonly int $alignment,
        public readonly array $provenance, public readonly array $states, public readonly array $operations,
        public readonly string $profile = self::PROFILE)
    {
    }
}

/** Explicit link obligations distinguish a project module from a self-contained runtime package. */
final class module_contract {
    /** @param array<string, source_type> $sources Exact source keys, including transitive native dependencies. */
    public function __construct(public readonly string $project, public readonly array $sources)
    {
    }
}
