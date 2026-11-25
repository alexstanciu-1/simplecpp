<?php
declare(strict_types=1);

/*
 * Role: Explicit project namespace and roots for compiler-owned native exports.
 * Used by: Source_Identities; Source_Export_Preparation
 * Flow: coordinator configuration -> fixed preparation tasks; no filesystem writes.
 */
namespace compile;

/** Portable identity uses project_key; physical roots only locate inputs and future outputs. */
final class baseline_native_project
{
    public readonly string $source_root;
    public readonly string $output_root;

    /** Require an explicit namespace and normalized absolute roots; never infer identity from a path. */
    public function __construct(public readonly string $project_key, string $source_root, string $output_root)
    {
        if (($project_key === '') || str_contains($project_key, "\0")) {
            throw new \InvalidArgumentException('Native project requires an explicit project key');
        }
        $this->source_root = self::root($source_root);
        $this->output_root = self::root($output_root);
    }

    /** Configuration roots are lexical, not realpath identities; output need not exist yet. */
    private static function root(string $path): string
    {
        if (!str_starts_with($path, '/') || str_contains($path, "\0")) {
            throw new \InvalidArgumentException('Native project roots must be absolute');
        }
        $parts = [];
        foreach (explode('/', $path) as $part)
        {
            if (($part === '') || ($part === '.')) {
                continue;
            }
            if ($part === '..') {
                throw new \InvalidArgumentException('Native project roots must not contain parent traversal');
            }
            $parts[] = $part;
        }
        return '/' . implode('/', $parts);
    }
}
