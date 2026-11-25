<?php
declare(strict_types=1);
namespace parse;

/** One parser owner; file and expression entries share the same grammar and arena. */
final class File_Parser {
    use Expression_Parsing;
    use Statement_Parsing;
    use Control_Statement_Parsing;
    use Declaration_Parsing;
    use Metaprogramming_Parsing;
    private int $cursor = 0;
    private int $end = 0;
    private array $angle_ends /** hash<int,int> */ = [];
    private array $frames /** vector<Expression_Frame> */ = [];
    private int $depth = 0;
    public function __construct(private Parse_Result $result) {}
    public static function parse_expression(\tokenize\Lexical_Buffer $tokens, bool $type): Parse_Result {
        $result = new Parse_Result($tokens, new Syntax_Arena());
        $parser = new File_Parser($result);
        return $parser->run(false, $type);
    }
    public static function parse(\tokenize\Lexical_Buffer $tokens): Parse_Result {
        $result = new Parse_Result($tokens, new Syntax_Arena());
        $parser = new File_Parser($result);
        return $parser->run(true, false);
    }
    private function run(bool $file, bool $type): Parse_Result {
        try {
            if (!$this->result->tokens->valid) {
                $this->result->error_start = $this->result->tokens->error_start;
                $this->result->error_length = $this->result->tokens->error_length;
                $this->result->error_reason = $this->result->tokens->error_reason;
                throw new \RuntimeException('Invalid lexical input');
            }
            $this->angle_ends = Binary_Syntax::angle_ends($this->result->tokens);
            $root /** int */ = 0;
            if ($file) { $root = $this->file(); }
            else { $root = $this->expression($type ? \parse\EXPR_TYPE : \parse\EXPR_VALUE); }
            $this->expect(\tokenize\TOKEN_END_OF_FILE, 'end of input');
            if ($this->cursor !== q_count($this->result->tokens->rows)) { $this->fail('Tokens after EOF'); }
            if ($this->depth !== 0) { throw new \LogicException('Unfinished expression continuations'); }
            $this->result->root = $root;
        } catch (\RuntimeException $error) {
            $this->result->valid = false;
            $this->result->root = 0;
            $this->result->entry = 0;
            $no_definitions /** vector<int> */ = [];
            $this->result->definitions = $no_definitions;
            $this->result->tree = new Syntax_Arena();
        }
        return $this->result;
    }
    private function file(): int {
        $length = string_byte_len($this->result->tokens->source->content);
        $entry = $this->node(\parse\SYNTAX_BLOCK, 0, $length);
        $this->statements($entry, \tokenize\TOKEN_END_OF_FILE, true);
        $root = $this->node(\parse\SYNTAX_FILE_ROOT, 0, $length);
        $this->result->tree->child($root, $entry);
        foreach ($this->result->definitions as $definition) { $this->result->tree->child($root, $definition); }
        $this->result->entry = $entry;
        return $root;
    }
    private function node(int $kind, int $start, int $length): int {
        return $this->result->tree->add($kind, $start, $length);
    }
    private function finish(int $id): void { $this->result->tree->finish($id, $this->end); }
    private function variable(): int {
        $token = $this->expect(\tokenize\TOKEN_VARIABLE_NAME, 'variable name');
        return $this->node(\parse\SYNTAX_VARIABLE_NAME, (int)$token->start, (int)$token->length);
    }
    private function kind(): int {
        $token = $this->peek();
        return (int)$token->kind;
    }
    private function peek(): \tokenize\Token_Row {
        if ($this->cursor >= q_count($this->result->tokens->rows)) { throw new \RuntimeException('Missing EOF token'); }
        return $this->result->tokens->rows[$this->cursor];
    }
    private function advance(): \tokenize\Token_Row {
        $token = $this->peek();
        ++$this->cursor;
        $this->end = (int)$token->start + (int)$token->length;
        return $token;
    }
    private function fail(string $reason): void {
        $token = $this->peek();
        $this->result->error_start = (int)$token->start;
        $this->result->error_length = (int)$token->length;
        $this->result->error_reason = $reason;
        throw new \RuntimeException($reason);
    }
    private function expect(int $kind, string $description): \tokenize\Token_Row {
        if ($this->kind() !== $kind) { $this->fail('Expected ' . $description); }
        return $this->advance();
    }
    private function name(): int {
        $token = $this->expect(\tokenize\TOKEN_IDENTIFIER, 'name');
        return $this->result->tree->add(\parse\SYNTAX_NAME, (int)$token->start, (int)$token->length);
    }
    private function node_kind(int $id): int { $row = $this->result->tree->row($id); return (int)$row->kind; }
    private function node_start(int $id): int { $row = $this->result->tree->row($id); return (int)$row->start; }
    private function push(int $context, int $node): void {
        $frame = new Expression_Frame($context, $node, new Parse_Int_Stack(), new Parse_Int_Stack());
        if ($this->depth === q_count($this->frames)) { $this->frames[] = $frame; }
        else { $this->frames[$this->depth] = $frame; }
        ++$this->depth;
    }
    private function frame(): Expression_Frame { return $this->frames[$this->depth - 1]; }
}
