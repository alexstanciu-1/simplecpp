<?php
declare(strict_types=1);

/*
 * Role: Validate and normalize manifest text.
 * Used by: Manifest_Reader::run()
 * Call map: Manifest_Syntax::parse() -> validate_path()
 */

namespace read_manifest;

/**
 * @compiler-api Configuration ingestion for compile; source membership belongs to read_sources.
 * Returns a new manifest or throws on invalid input; never mutates a baseline.
 */
class Manifest_Syntax
{

    // Decode and normalize configuration here; filesystem membership belongs to read_sources.
    /**
     * @compiler-internal Decode and validate exact JSON bytes without filesystem reads.
     * The returned manifest lacks directory context; production callers use Manifest_Reader.
     */
    public static function parse(string $path, string $content): Project_Manifest
    {
        try {
            // Preserve JSON object/list distinctions until the schema has been checked.
            $decoded = json_decode($content, flags: JSON_THROW_ON_ERROR);
        }
        catch (\JsonException $exception) {
            throw new \Exception("Invalid JSON: " . $exception->getMessage(), 0, $exception);
        }
        if ((!$decoded instanceof \stdClass)
            || (!isset($decoded->source_folders, $decoded->entry))) {
            throw new \Exception("Required settings: source_folders and entry");
        }
        foreach ($decoded as $key => $setting) {
            if (($key !== "source_folders") && ($key !== "entry")) {
                throw new \Exception("Unknown manifest setting: " . $key);
            }
        }
        if ((!is_array($decoded->source_folders)) || (!array_is_list($decoded->source_folders))) {
            throw new \Exception("source_folders must be a list");
        }
        if ($decoded->source_folders === []) {
            throw new \Exception("source_folders must be a nonempty list of paths");
        }

        // Build the normalized input only after validating the top-level manifest shape.
        $manifest = new Project_Manifest();
        $manifest->path = $path;
        $manifest->content = $content;
        foreach ($decoded->source_folders as $root) {
            self::validate_path($root);
            if (in_array($root, $manifest->source_folder_paths, true)) {
                throw new \Exception("Duplicate source folder: " . $root);
            }
            $manifest->source_folder_paths[] = $root;
        }
        self::validate_path($decoded->entry);
        $manifest->entry_path = $decoded->entry;
        return $manifest;
    }

    /** Require a nonempty textual path with no NUL bytes before filesystem resolution. */
    private static function validate_path(mixed $path): void
    {
        if ((!is_string($path)) || ($path === "")) {
            throw new \Exception("Expected a nonempty string path");
        }
        if (str_contains($path, "\0")) {
            throw new \Exception("Path must not contain NUL bytes");
        }
    }
}
