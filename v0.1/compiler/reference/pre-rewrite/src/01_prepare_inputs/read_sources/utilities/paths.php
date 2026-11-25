<?php
declare(strict_types=1);

/*
 * Role: Resolve paths and identify participating source files.
 * Used by: Source_Reader; Source_Scanner; Simulation
 * Call map:
 *   Source_Paths::resolve()
 *     -> Source_Paths::is_absolute(); Source_Paths::join() [if relative]
 */

namespace read_sources;

/**
 * @compiler-api Source-owned host path policy reused by entry selection and simulation.
 * Public helper contracts below do not expose source dataset/index storage.
 */
class Source_Paths
{
    /**
     * @compiler-api Resolve an existing path against base when relative; return canonical host-normalized spelling.
     * Throws when it cannot be resolved; may perform filesystem reads.
     */
    public static function resolve(string $base, string $path): string
    {
        $candidate = $path;
        if (!self::is_absolute($path)) {
            $candidate = self::join($base, $path);
        }
        $resolved = realpath($candidate);
        if ($resolved === false) {
            throw new \Exception("Cannot resolve source path: " . $candidate);
        }

        // Normalize native Windows separators; preserve literal backslashes in POSIX names.
        if (DIRECTORY_SEPARATOR === "\\") {
            return str_replace("\\", "/", $resolved);
        }
        return $resolved;
    }

    /** Join spelling without canonicalization or filesystem access. */
    public static function join(string $base, string $relative): string
    {
        return Source_Path_Syntax::join($base, $relative);
    }

    /** Interpret spelling on the host that will resolve it. */
    public static function is_absolute(string $path): bool
    {
        return Source_Path_Syntax::is_absolute($path, DIRECTORY_SEPARATOR === "\\");
    }

    /** Source membership is a suffix policy, not parsing. */
    public static function is_source(string $path): bool
    {
        return Source_Path_Syntax::is_source($path);
    }
}
