<?php
declare(strict_types=1);

/*
 * Role: Completed file frontend, structural validation and export.
 * Used by: File_Parser; Frontend_Join; analysis
 * Flow: tokens + syntax + entity/body IDs -> File_Frontend
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

// Parsed file: one root covering definitions and the implicit entry body.
// All node IDs below belong to this file's syntax snapshot, not the symbol index.
/**
 * @compiler-api Parser output consumed by collection, comparison, resolution and debug.
 * Readable fields: source_file_id, tokens, syntax, defined_entities, entry_body_id.
 * One syntax root contains the implicit entry block then ordered definitions. The
 * entity list indexes that tree, not duplicated nodes. Keep this exact frontend with
 * its source spans; never combine IDs/spelling with a different file version.
 * Only parse assembles the record; construction binds inputs but leaves structural assembly incomplete.
 */
class File_Frontend
{
    public int $source_file_id = 0;
    public function __construct(
        public \tokenize\Token_Buffer $tokens,
        public Syntax_Tree $syntax
    )
    {
    }

    // Index into the root's declaration children, not a second copy of syntax.
    // Top-level declaration node IDs in source order. Members belong to their entity.
    /** @var list<int> */
    public array $defined_entities /** vector<int> */ = [];

    // A completed parse has a block node here, even when it has no statements.
    // Zero denotes an unfinished result. This body is not a named declaration.
    public int $entry_body_id = 0;

    // Root/index consistency for a complete file, shared by joins and storage.
    /**
     * @compiler-api Read-only root/entry/definition-index consistency check; throws on malformed structure.
     * This is not a full grammar/semantic validation of every descendant.
     */
    public function validate(): void
    {
        $id = $this->source_file_id;
        $node_count = count($this->syntax->nodes);
        $root_id = $this->syntax->root_node_id;
        if (($this->entry_body_id < 1) || ($this->entry_body_id > $node_count)
            || ($root_id < 1) || ($root_id > $node_count)) {
            throw new \Exception('Invalid file frontend');
        }
        $entry = $this->syntax->nodes[$this->entry_body_id - 1];
        $root = $this->syntax->nodes[$root_id - 1];
        if (($id < 1) || ($this->tokens->source->source_file_id !== $id)
            || ($this->syntax->source_file_id !== $id) || ($entry->kind !== syntax_kind::block)
            || ($root->kind !== syntax_kind::file_root) || ($root->next_sibling_id !== 0)
            || ($root->first_child_id !== $this->entry_body_id)) {
            throw new \Exception('Invalid file frontend');
        }
        $next = $entry->next_sibling_id;
        foreach ($this->defined_entities as $entity_id)
        {
            if (($entity_id < 1) || ($entity_id > $node_count) || ($next !== $entity_id)
                || ($entity_id === $this->entry_body_id) || ($entity_id === $this->syntax->root_node_id)) {
                throw new \Exception('Invalid file definition index');
            }
            $entity = $this->syntax->nodes[$entity_id - 1];
            $next = $entity->next_sibling_id;
        }
        if ($next !== 0) {
            throw new \Exception('Incomplete file definition index');
        }
    }

    /** @compiler-api On-demand debug view; not a semantic input or a persisted-cache format. */
    public function to_json(): string
    {
        $json = '{"source_file_id":' . $this->source_file_id
            . ',"path":' . \read_sources\Source_Json::quote($this->tokens->source->path)
            . ',"root_node_id":' . $this->syntax->root_node_id . ',"defined_entities":[';
        $separator = '';
        foreach ($this->defined_entities as $id) {
            $json .= $separator . $id;
            $separator = ',';
        }
        $json .= '],"entry_body_id":' . $this->entry_body_id . ',"nodes":[';
        $separator = '';
        foreach ($this->syntax->nodes as $index => $node)
        {
            $json .= $separator . '{"id":' . ($index + 1)
                . ',"kind":' . \read_sources\Source_Json::quote(Syntax_Kinds::name($node->kind))
                . ',"start":' . $node->start . ',"length":' . $node->length
                . ',"first_child_id":' . $node->first_child_id . ',"next_sibling_id":' . $node->next_sibling_id;
            if (($node->kind === syntax_kind::name) || ($node->kind === syntax_kind::variable_name) || ($node->kind === syntax_kind::integer_literal)
                || ($node->kind === syntax_kind::string_literal) || ($node->kind === syntax_kind::boolean_literal)) {
                $json .= ',"text":' . \read_sources\Source_Json::quote(string_byte_slice($this->tokens->source->content, $node->start, $node->length));
            }
            $json .= '}';
            $separator = ',';
        }
        return $json . ']}';
    }
}
