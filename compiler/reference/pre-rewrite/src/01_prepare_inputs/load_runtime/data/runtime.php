<?php
declare(strict_types=1);

/*
 * Role: Package-local storage entries and implementation artifact kinds.
 * Used by: Package_Adapter; Runtime_Package
 * Flow: fixed inputs -> owned contracts -> read-only consumers
 */

namespace load_runtime;

use type_model\named_type_definition;

enum runtime_storage_kind: string
{
    case integer = 'integer';
    case address = 'address';
    case byte_span = 'byte_span';
    case void_type = 'void';
    case opaque_inline = 'opaque_inline';
    case record = 'record';
}

/** @compiler-api Measured storage in the containing package's target context; no lifetime readiness implied. */
final class runtime_storage {
    public function __construct(public readonly runtime_storage_kind $kind,
        public readonly int $size_bytes, public readonly int $alignment_bytes)
    {
    }
}

/** @compiler-api Provider-local storage with an exact scalar/opaque definition or normalized structural input. */
final class runtime_type
{
    public function __construct(public readonly string $id, public readonly runtime_storage $storage,
        public readonly ?int $integer_bits, public readonly ?bool $signed,
        public readonly ?named_type_definition $language_type,
        public readonly ?\type_model\record_declaration $record = null)
    {
    }
}

/** @compiler-api Prepared module kinds; LTO variants are queryable but production linking uses ordinary. */
enum runtime_module_kind: string {
    case ordinary = 'ordinary';
    case full_lto = 'full_lto';
    case thin_lto = 'thin_lto';
}
