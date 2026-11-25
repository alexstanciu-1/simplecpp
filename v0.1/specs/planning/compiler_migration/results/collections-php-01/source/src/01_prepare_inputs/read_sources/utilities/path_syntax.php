<?php
declare(strict_types=1);

/* Pure source-path spelling rules; filesystem and host selection stay in Source_Paths. */
namespace read_sources;
// <scpp-imports>
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

class Source_Path_Syntax
{
    /** @compiler-api Join path spellings without canonicalization or checking existence; relative must be relative. */
    public static function join(string $base, string $relative): string
    {
        if (string_byte_ends_with($base, "/")) {
            return $base . $relative;
        }
        return $base . "/" . $relative;
    }

    // Interpret path syntax on the host that will resolve and read it.
    /** @compiler-internal Host-specific path syntax check used by resolve; does not access the filesystem. */
    public static function is_absolute(string $path, bool $windows): bool
    {
        if (!$windows) {
            return string_byte_starts_with($path, "/");
        }
        if (string_byte_starts_with($path, "/") || string_byte_starts_with($path, "\\")) {
            return true;
        }
        return (string_byte_len($path) >= 3) && (string_byte_slice($path, 1, 1) === ":")
            && ((string_byte_slice($path, 2, 1) === "/") || (string_byte_slice($path, 2, 1) === "\\"));
    }

    // Source-language membership policy for the current PHP++ frontend.
    /** @compiler-internal Source membership predicate for discovery; suffix recognition does not parse a file. */
    public static function is_source(string $path): bool
    {
        return string_byte_ends_with($path, ".phs");
    }
}
