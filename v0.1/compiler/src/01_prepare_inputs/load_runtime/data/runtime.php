<?php
declare(strict_types=1);
namespace load_runtime;
const RUNTIME_STORAGE_INTEGER = 0;
const RUNTIME_STORAGE_ADDRESS = 1;
const RUNTIME_STORAGE_BYTE_SPAN = 2;
const RUNTIME_STORAGE_VOID = 3;
const RUNTIME_STORAGE_OPAQUE = 4;
const RUNTIME_STORAGE_RECORD = 5;
const MODULE_ORDINARY = 0;
const MODULE_FULL_LTO = 1;
const MODULE_THIN_LTO = 2;

final class Runtime_Modes {
    public static function storage_name(int $kind): string {
        $name = '';
        if ($kind === \load_runtime\RUNTIME_STORAGE_INTEGER) { $name = 'integer'; }
        elseif ($kind === \load_runtime\RUNTIME_STORAGE_ADDRESS) { $name = 'address'; }
        elseif ($kind === \load_runtime\RUNTIME_STORAGE_BYTE_SPAN) { $name = 'byte_span'; }
        elseif ($kind === \load_runtime\RUNTIME_STORAGE_VOID) { $name = 'void'; }
        elseif ($kind === \load_runtime\RUNTIME_STORAGE_OPAQUE) { $name = 'opaque_inline'; }
        elseif ($kind === \load_runtime\RUNTIME_STORAGE_RECORD) { $name = 'record'; }
        else { throw new \InvalidArgumentException('Unknown runtime storage kind'); }
        return $name;
    }
    public static function storage(string $name): int {
        $found = -1;
        for ($kind = 0; $kind < 6; $kind++) { if (Runtime_Modes::storage_name($kind) === $name) { $found = $kind; break; } }
        if ($found === -1) { throw new \InvalidArgumentException('Unknown runtime storage kind'); }
        return $found;
    }
    public static function module_name(int $kind): string {
        $name = '';
        if ($kind === \load_runtime\MODULE_ORDINARY) { $name = 'ordinary'; }
        elseif ($kind === \load_runtime\MODULE_FULL_LTO) { $name = 'full_lto'; }
        elseif ($kind === \load_runtime\MODULE_THIN_LTO) { $name = 'thin_lto'; }
        else { throw new \InvalidArgumentException('Unknown runtime module kind'); }
        return $name;
    }
    public static function module_kind(string $name): int {
        $found = -1;
        for ($kind = 0; $kind < 3; $kind++) { if (Runtime_Modes::module_name($kind) === $name) { $found = $kind; break; } }
        if ($found === -1) { throw new \InvalidArgumentException('Unknown runtime module kind'); }
        return $found;
    }
}

/** Measured storage in the containing package's target context, not lifetime readiness.
 * The package importer validates size/alignment against the declared type category. */
final class Runtime_Storage {
    public function __construct(public readonly int $kind, public readonly int $size_bytes, public readonly int $alignment_bytes) {
        Runtime_Modes::storage_name($kind);
    }
}

/** Exact provider-local storage and optional language definition/normalized record. */
final class Runtime_Type {
    public function __construct(public readonly string $id, public readonly Runtime_Storage $storage,
        public readonly ?int $integer_bits, public readonly ?bool $signed,
        public readonly ?\type_model\Named_Definition $language_type,
        public readonly ?\type_model\Record_Declaration $record = null) {}
}
