<?php
declare(strict_types=1);

/*
 * Role: Normalized project manifest, from JSON or a virtual one-file project.
 * Used by: Manifest_Reader; Compiler_Session; Source_Reader
 * Flow: project input -> Project_Manifest -> input selection/discovery
 */

namespace read_manifest;
// <scpp-imports>
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

/**
 * @compiler-api Manifest_Reader output, shared read-only with compile, discovery and entry selection.
 * Readable fields: path, content, directory, source_folder_paths, source_file_paths, entry_path.
 * path names the actual input; content is exact JSON, or null for a virtual manifest.
 * Source bytes belong only to source reading, never configuration comparison.
 * directory anchors relative paths. Defaults form an empty baseline, not valid project configuration.
 */
class Project_Manifest implements \compile\Step_Result
{
    public string $path = "";
    public ?string $content = "";

    // Absolute directory of the project input, used as the base for relative paths.
    public string $directory = "";

    /** @var list<string> */
    public array $source_folder_paths /** vector<string> */ = [];

    /** @var list<string> Explicit sources; their neighboring files do not participate. */
    public array $source_file_paths /** vector<string> */ = [];
    public string $entry_path = "";

    // Debug snapshot of this result, including the exact input and path context.
    /** @compiler-api On-demand debug view; not a semantic input or a persisted-cache format. */
    public function to_json(): string
    {
        $out /** string */ = '';
        try {
            $out = '{"path":' . json_quote($this->path) . ',"content":';
            $content /** string */ = '';
            if (take_nullable($content, $this->content)) { $out = $out . json_quote($content); }
            else { $out = $out . 'null'; }
            $out = $out . ',"directory":' . json_quote($this->directory) . ',"source_folder_paths":[';
            $first /** bool */ = true;
            foreach ($this->source_folder_paths as $root) {
                if (!$first) { $out = $out . ','; }
                $out = $out . json_quote($root);
                $first = false;
            }
            $out = $out . ']';
            if (count($this->source_file_paths) !== 0) {
                $out = $out . ',"source_file_paths":[';
                $first = true;
                foreach ($this->source_file_paths as $path) {
                    if (!$first) { $out = $out . ','; }
                    $out = $out . json_quote($path);
                    $first = false;
                }
                $out = $out . ']';
            }
            $out = $out . ',"entry_path":' . json_quote($this->entry_path) . '}';
        }
        catch (\JsonException $exception) {
            throw new \Exception("Cannot export project manifest: " . $exception->getMessage(), 0, $exception);
        }
        return $out;
    }
}
