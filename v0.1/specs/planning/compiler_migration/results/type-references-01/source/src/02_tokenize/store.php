<?php
declare(strict_types=1);

/*
 * Role: Per-source token buffers and their indexed set.
 * Used by: Tokenizer; Token_Join; Parser
 * Flow: Source_Buffer -> Token_Buffer -> Token_Set
 */

namespace tokenize;
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

// One owner per file snapshot; rows are token records.
/**
 * @compiler-api Readable source and rows produced by Tokenizer; frozen by agreement after handoff.
 * rows are zero-based lexical order including EOF; source retains exact spelling/version.
 * Construction makes an empty builder, not a complete tokenization result.
 */
class Token_Buffer
{
    // Snapshot identity and token spelling come from this one shared reference.
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(public readonly \read_sources\Source_Buffer $source)
    {
    }

    /** @var list<token> */
    public array $rows /** vector<\tokenize\token> */ = [];

    /** @compiler-api On-demand debug view; not a semantic input or a persisted-cache format. */
    public function to_json(): string
    {
        $json = '{"source_file_id":' . $this->source->source_file_id
            . ',"path":' . \read_sources\Source_Json::quote($this->source->path) . ',"tokens":[';
        $separator = '';
        foreach ($this->rows as $token) {
            $json .= $separator . '{"kind":' . \read_sources\Source_Json::quote(\tokenize\Token_Kinds::name($token->kind))
                . ',"start":' . $token->start . ',"length":' . $token->length
                . ',"text":' . \read_sources\Source_Json::quote(string_byte_slice($this->source->content, $token->start, $token->length)) . '}';
            $separator = ',';
        }
        return $json . ']}';
    }
}

// One live buffer per file. Consumers use lookup rather than storage keys.
/**
 * @compiler-api Joined current token buffers consumed by parsing and compile; private file index.
 * Lookup shares existing buffers; shared rows must not be changed.
 */
class Token_Set implements \compile\Step_Result, \compile\Step_Store
{
    /** @var array<int, Token_Buffer> */
    private array $by_file /** hash<\tokenize\Token_Buffer,int> */ = [];

    /**
     * @compiler-api Create an empty baseline; nonempty assembly belongs to the producing join.
     * Construction checks local invariants, not completeness of a compiler phase.
     * @param list<Token_Buffer>|null $buffers Null constructs the empty baseline.
     */
    public function __construct(?array $buffers /** vector<\tokenize\Token_Buffer> */ = null)
    {
        $selected /** vector<\tokenize\Token_Buffer> */ = [];
        take_nullable($selected, $buffers);
        foreach ($selected as $buffer) {
            $id = $buffer->source->source_file_id;
            if (($id < 1) || (isset($this->by_file[$id]))) {
                throw new \Exception('Invalid or duplicate token buffer');
            }
            $this->by_file[$id] = $buffer;
        }
    }

    /** @compiler-api Read the existing buffer by logical file ID, or null if absent. */
    public function for_file(int $id): ?\tokenize\Token_Buffer
    {
        if (!isset($this->by_file[$id])) { return null; }
        return $this->by_file[$id];
    }

    /** @compiler-api On-demand debug view; not a semantic input or a persisted-cache format. */
    public function to_json(): string
    {
        $json = '[';
        $separator = '';
        foreach ($this->by_file as $buffer) {
            $json .= $separator . $buffer->to_json();
            $separator = ',';
        }
        return $json . ']';
    }
}
