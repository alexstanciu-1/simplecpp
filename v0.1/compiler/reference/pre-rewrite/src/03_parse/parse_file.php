<?php
declare(strict_types=1);

/*
 * Role: Own parser state and assemble one file frontend.
 * Used by: Parser::run()
 * Call map:
 *   File_Parser::parse()
 *     -> Binary_Syntax::angle_ends(); statements() [trait]; [action] assemble File_Frontend
 */

namespace parse;

use tokenize\token;
use tokenize\token_kind;
use tokenize\Token_Buffer;

// One instance per file task. Only this task writes its cursor and output tree.
/**
 * @compiler-internal Independent file task used by Parser::run and worker proofs.
 * Cursor, continuations and append order may change within parse.
 */
class File_Parser
{
    use Statement_Parsing;
    use Control_Statement_Parsing;
    use Declaration_Parsing;
    use Metaprogramming_Parsing;
    use Expression_Parsing;

    private int $cursor = 0;
    private int $end = 0;
    private Syntax_Tree $tree;
    /** @var array<int, int> Token positions; private lexical disambiguation, never syntax identity. */
    private array $angle_ends = [];

    /** @var list<int> */
    private array $definitions = [];

    /** @compiler-internal Initialize private state for one task; use the process entry point externally. */
    public function __construct(private readonly Token_Buffer $tokens)
    {
        $this->tree = new Syntax_Tree();
        $this->tree->source_file_id = $tokens->source->source_file_id;
    }

    /** @compiler-internal Build one private frontend from fixed tokens, awaiting Frontend_Join. */
    public function parse(): File_Frontend
    {
        $this->angle_ends = Binary_Syntax::angle_ends($this->tokens);

        // Parse file statements into the same body representation used by functions.
        $entry = $this->node(syntax_kind::block, 0);
        $this->statements($entry, token_kind::end_of_file, true);
        $this->expect(token_kind::end_of_file, 'end of file');
        if ($this->cursor !== count($this->tokens->rows)) {
            throw new \LogicException('Token buffer contains tokens after EOF');
        }
        $this->tree->nodes[$entry - 1]->length = strlen($this->tokens->source->content);

        // Keep executable entry and declarations under one file root without copying nodes.
        $root = $this->node(syntax_kind::file_root, 0, strlen($this->tokens->source->content));
        $last = 0;
        $this->child($root, $last, $entry);
        foreach ($this->definitions as $definition) {
            $this->child($root, $last, $definition);
        }
        $this->tree->root_node_id = $root;

        // Publish one frontend tied to the exact token snapshot parsed above.
        $file = new File_Frontend($this->tokens, $this->tree);
        $file->source_file_id = $this->tree->source_file_id;
        $file->defined_entities = $this->definitions;
        $file->entry_body_id = $entry;
        return $file;
    }

    /** Append a child to its parent sibling chain in the private syntax tree. */
    private function child(int $parent, int &$last, int $child): void
    {
        if ($last === 0) {
            $this->tree->nodes[$parent - 1]->first_child_id = $child;
        }
        else {
            $this->tree->nodes[$last - 1]->next_sibling_id = $child;
        }
        $last = $child;
    }

    private function name(string $description): int
    {
        $token = $this->expect(token_kind::identifier, $description);
        return $this->node(syntax_kind::name, $token->start, $token->length);
    }

    private function variable(): int
    {
        $token = $this->expect(token_kind::variable_name, 'variable name');
        return $this->node(syntax_kind::variable_name, $token->start, $token->length);
    }

    private function finish(int $id): void
    {
        $this->tree->nodes[$id - 1]->length = $this->end - $this->tree->nodes[$id - 1]->start;
    }

    private function advance(): token
    {
        $token = $this->peek();
        $this->cursor++;
        $this->end = $token->start + $token->length;
        return $token;
    }

    private function fail(string $reason): never
    {
        $token = $this->peek();
        $source = $this->tokens->source;
        throw new \diagnostics\Source_Error($source->source_file_id, $source->path,
            $token->start, $token->length, $reason);
    }

    /** Append a syntax row and return its one-based identity within this tree. */
    private function node(syntax_kind $kind, int $start, int $length = 0): int
    {
        $node = new syntax_node();
        $node->kind = $kind;
        $node->start = $start;
        $node->length = $length;
        $this->tree->nodes[] = $node;
        return count($this->tree->nodes);
    }

    private function peek(): token
    {
        return $this->tokens->rows[$this->cursor]
            ?? throw new \LogicException('Incomplete token buffer: missing EOF');
    }

    private function expect(token_kind $kind, string $description): token
    {
        if ($this->peek()->kind !== $kind) {
            $this->fail('Expected ' . $description);
        }
        return $this->advance();
    }
}
