<?php
declare(strict_types=1);

/*
 * Role: Normalized project manifest, from JSON or a virtual one-file project.
 * Used by: Manifest_Reader; Compiler_Session; Source_Reader
 * Flow: project input -> Project_Manifest -> input selection/discovery
 */

namespace read_manifest;

/**
 * @compiler-api Manifest_Reader output, shared read-only with compile, discovery and entry selection.
 * Readable fields: path, content, directory, source_folder_paths, source_file_paths, entry_path.
 * path names the actual input; content is exact JSON, or null for a virtual manifest.
 * Source bytes belong only to source reading, never configuration comparison.
 * directory anchors relative paths. Defaults form an empty baseline, not valid project configuration.
 */
class Baseline_Project_Manifest implements \compile\Step_Result
{
    public string $path = "";
    public ?string $content = "";

    // Absolute directory of the project input, used as the base for relative paths.
    public string $directory = "";

    /** @var list<string> */
    public array $source_folder_paths = [];

    /** @var list<string> Explicit sources; their neighboring files do not participate. */
    public array $source_file_paths = [];
    public string $entry_path = "";

    // Debug snapshot of this result, including the exact input and path context.
    /** @compiler-api On-demand debug view; not a semantic input or a persisted-cache format. */
    public function to_json(): string
    {
        // Dynamic values stay local to the serialization boundary.
        $roots = [];
        foreach ($this->source_folder_paths as $index => $root) {
            $roots[$index] = $root;
        }
        $fields = [];
        $fields["path"] = $this->path;
        $fields["content"] = $this->content;
        $fields["directory"] = $this->directory;
        $fields["source_folder_paths"] = $roots;
        if ($this->source_file_paths !== []) {
            $fields["source_file_paths"] = $this->source_file_paths;
        }
        $fields["entry_path"] = $this->entry_path;
        try {
            return json_encode($fields, JSON_THROW_ON_ERROR);
        }
        catch (\JsonException $exception) {
            throw new \Exception("Cannot export project manifest: " . $exception->getMessage(), 0, $exception);
        }
    }
}
