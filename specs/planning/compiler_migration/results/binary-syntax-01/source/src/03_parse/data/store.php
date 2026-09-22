<?php
declare(strict_types=1);

/*
 * Role: Flat syntax storage and frontend lookup by file.
 * Used by: File_Parser; Frontend_Join; analysis
 * Flow: Syntax_Tree / File_Frontend -> Frontend_Set
 */

namespace parse;
// <scpp-imports>
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

// One complete parse result per file, shared unchanged between updates.
/**
 * @compiler-api Joined current file frontends; semantic steps query by logical file identity.
 * Removed files are absent. Returned frontends/trees are shared read-only.
 */
class Frontend_Set implements \compile\Step_Result, \compile\Step_Store
{
    /** @var array<int, File_Frontend> */
    private array $by_file /** hash<File_Frontend,int> */ = [];

    /**
     * @compiler-api Create an empty baseline; nonempty assembly belongs to the producing join.
     * Construction checks local invariants, not completeness of a compiler phase.
     * @param list<File_Frontend>|null $files Null constructs the empty baseline.
     */
    public function __construct(?array $files /** vector<File_Frontend> */ = null)
    {
        $selected /** vector<File_Frontend> */ = [];
        take_nullable($selected, $files);
        foreach ($selected as $file)
        {
            $id = $file->source_file_id;
            if (isset($this->by_file[$id])) {
                throw new \Exception('Invalid or duplicate file frontend');
            }
            $file->validate();
            $this->by_file[$id] = $file;
        }
    }

    /** @compiler-api Return the shared file frontend or null for an absent logical file ID. */
    public function for_file(int $id): ?File_Frontend
    {
        if (!isset($this->by_file[$id])) { return null; }
        return $this->by_file[$id];
    }

    /** @compiler-api On-demand debug view; not a semantic input or a persisted-cache format. */
    public function to_json(): string
    {
        $json = '[';
        $separator = '';
        foreach ($this->by_file as $file) {
            $json .= $separator . $file->to_json();
            $separator = ',';
        }
        return $json . ']';
    }
}
