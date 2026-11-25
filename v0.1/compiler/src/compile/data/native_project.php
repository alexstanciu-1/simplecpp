<?php
declare(strict_types=1);
namespace compile;

/** Explicit provenance only; verification and export publication belong to their producers. */
final class Native_Project
{
    public readonly string $source_root;
    public readonly string $output_root;

    /** Require an explicit namespace and normalized absolute roots; never infer identity from a path. */
    public function __construct(public readonly string $project_key, string $source_root, string $output_root)
    {
        if (($project_key === '') || Native_Project::has_nul($project_key)) {
            throw new \InvalidArgumentException('Native project requires an explicit project key');
        }
        $this->source_root = Native_Project::root($source_root);
        $this->output_root = Native_Project::root($output_root);
    }

    private static function has_nul(string $text): bool
    {
        for ($i /** int */ = 0; $i < string_byte_len($text); ++$i) {
            if (string_byte_at($text, $i) === 0) { return true; }
        }
        return false;
    }

    /** Lexical POSIX roots, preserving arbitrary non-NUL path bytes; no filesystem access. */
    private static function root(string $path): string
    {
        if (!string_byte_starts_with($path, '/') || Native_Project::has_nul($path)) {
            throw new \InvalidArgumentException('Native project roots must be absolute');
        }
        $out /** string */ = '';
        $start /** int */ = 0;
        $size /** int */ = string_byte_len($path);
        for ($end /** int */ = 0; $end < $size + 1; ++$end) {
            if (($end !== $size) && (string_byte_at($path, $end) !== 47)) { continue; }
            $part /** string */ = string_byte_slice($path, $start, $end - $start);
            $start = $end + 1;
            if (($part === '') || ($part === '.')) { continue; }
            if ($part === '..') {
                throw new \InvalidArgumentException('Native project roots must not contain parent traversal');
            }
            $out = $out . '/' . $part;
        }
        if ($out === '') { return '/'; }
        return $out;
    }
}
