<?php
declare(strict_types=1);

/*
 * Role: Declaration type references without concrete layout or ABI.
 * Used by: semantic signatures; family declarations; source/provider adapters
 * Flow: exact identity -> unresolved reference -> producer-owned resolution
 */
namespace type_model;
// <scpp-imports>
use function scpp\string_byte_from_int as string_byte_from_int;
use function scpp\enum_name as enum_name;
use function scpp\lock_empty as lock_empty;
use function scpp\lock_try as lock_try;
use function scpp\lock_release as lock_release;
use function scpp\lock_transfer as lock_transfer;
use function scpp\process_spawn as process_spawn;
use function scpp\process_poll as process_poll;
use function scpp\process_output as process_output;
use function scpp\process_stop as process_stop;
use function scpp\process_close as process_close;
use function scpp\sequence_map as sequence_map;
use function scpp\sequence_filter as sequence_filter;
use function scpp\keyed_map as keyed_map;
use function scpp\keyed_filter as keyed_filter;
use function scpp\string_byte_len as string_byte_len;
use function scpp\string_byte_starts_with as string_byte_starts_with;
use function scpp\string_byte_ends_with as string_byte_ends_with;
use function scpp\string_utf8_is_valid as string_utf8_is_valid;
use function scpp\string_codepoint_at as string_codepoint_at;
use function scpp\compat\substr as substr;
use function scpp\compat\strpos as strpos;
use function scpp\compat\strrpos as strrpos;
use function scpp\same_exception as same_exception;
use function scpp\string_byte_at as string_byte_at;
use function scpp\take_nullable as take_nullable;
use function scpp\take_false as take_false;
use function scpp\take_bool as take_bool;
use function scpp\compat\str_starts_with as str_starts_with;
use function scpp\compat\str_ends_with as str_ends_with;
use function scpp\compat\strlen as strlen;
use function scpp\string_byte_slice as string_byte_slice;
// </scpp-imports>

interface type_reference {
}

/** @compiler-api Exact language name awaiting resolution in the containing provider catalog's context. */
final class named_type_reference implements type_reference {
    public function __construct(public readonly string $name, public readonly string $namespace_name)
    {
    }
}

/** Exact provider type identity, independent of optional source-language exposure. */
final class provider_type_reference implements type_reference {
    public function __construct(public readonly string $provider, public readonly string $id)
    {
    }
}

final class parameter_type_reference implements type_reference {
    public function __construct(public readonly string $owner, public readonly int $slot)
    {
    }
}

final class family_type_reference implements type_reference {
    /** @param list<type_reference> $arguments */
    public function __construct(public readonly string $family, public readonly array $arguments /** vector<type_reference> */)
    {
    }
}
