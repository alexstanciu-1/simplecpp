<?php
declare(strict_types=1);

/* Structural role views: read-only node IDs into one supplied syntax tree. */
namespace parse;
// <scpp-imports>
use function scpp\fs_is_link as fs_is_link;
use function scpp\fs_is_dir as fs_is_dir;
use function scpp\fs_is_file as fs_is_file;
use function scpp\fs_size as fs_size;
use function scpp\fs_mtime as fs_mtime;
use function scpp\fs_scan as fs_scan;
use function scpp\json_quote as json_quote;
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

// Temporary structural view: IDs into the supplied tree, no retained dataset.
/**
 * @compiler-api Read-only structural view returned by Syntax_Access; all fields are AST node IDs
 * in the supplied tree, not semantic type/local IDs. No syntax is copied.
 */
class function_parts
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly int $name_id,
        public readonly int $parameters_id,
        public readonly int $return_type_id,
        public readonly int $body_id
    )
    {
    }
}

// Temporary structural views; all fields are node IDs in the same syntax tree.
/**
 * @compiler-api Read-only structural view returned by Syntax_Access; all fields are AST node IDs
 * in the supplied tree, not semantic type/local IDs. No syntax is copied.
 */
class local_declaration_parts
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly int $variable_id,
        public readonly int $type_syntax_id,
        public readonly int $initializer_id,
    )
    {
    }
}

/**
 * @compiler-api Read-only structural view returned by Syntax_Access; all fields are AST node IDs
 * in the supplied tree, not semantic type/local IDs. No syntax is copied.
 */
class parameter_parts
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly int $variable_id,
        public readonly int $type_syntax_id,
        public readonly ?syntax_kind $reference = null,
    )
    {
    }
}

/**
 * @compiler-api Read-only structural view returned by Syntax_Access; all fields are AST node IDs
 * in the supplied tree, not semantic type/local IDs. No syntax is copied.
 */
class assignment_parts
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly int $target_id,
        public readonly int $value_id,
    )
    {
    }
}

/**
 * @compiler-api Control roles in one tree: condition=0 for consteval, alternative=0 when absent.
 * Body is a block; an alternative can be a block or a nested conditional where permitted.
 */
final class control_parts {
    public function __construct(public readonly int $condition, public readonly int $body, public readonly int $alternative)
    {
    }
}

/** @compiler-api Struct syntax roles; first_member_id is zero for an empty parsed declaration. */
final class struct_parts {
    public function __construct(public readonly int $name_id, public readonly int $first_member_id)
    {
    }
}

/** @compiler-api Field declaration roles; IDs refer to one fixed syntax tree, never canonical types. */
final class field_declaration_parts {
    public function __construct(public readonly int $type_syntax_id, public readonly int $variable_id,
        public readonly int $extent_id = 0)
    {
    }
}

/** @compiler-api Ordered template parameters and wrapped declaration, all IDs in the same source tree. */
final class template_parts {
    public function __construct(public readonly int $parameters_id, public readonly int $declaration_id)
    {
    }
}

/** @compiler-api A parameter's name and optional value-type syntax; zero means a type parameter. */
final class template_parameter_parts {
    public function __construct(public readonly int $name_id, public readonly int $type_syntax_id)
    {
    }
}

/** @compiler-api A template application keeps its target name and nonempty ordered argument chain. */
final class template_application_parts {
    public function __construct(public readonly int $name_id, public readonly int $first_argument_id)
    {
    }
}

/** @compiler-api A constant's name, optional type syntax and required initializer; no computed value. */
final class constant_parts {
    public function __construct(public readonly int $name_id, public readonly int $type_syntax_id,
        public readonly int $initializer_id)
    {
    }
}
