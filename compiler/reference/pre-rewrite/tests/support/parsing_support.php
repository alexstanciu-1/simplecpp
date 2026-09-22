<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use parse\File_Parser;
use parse\File_Frontend;
use parse\syntax_kind;
use parse\Syntax_Access;
use tokenize\Tokenizer;
use read_sources\Source_Buffer;

class Parsing_Test
{
    public static function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    public static function parse(string $text): File_Frontend
    {
        $tokens = \tokenize\File_Tokenizer::tokenize(new Source_Buffer(9, 'parser-test.phs', 0, $text));
        $before = serialize($tokens);
        $result = (new File_Parser($tokens))->parse();
        self::check((serialize($tokens) === $before) && ($result->tokens === $tokens), 'Parser must read unchanged tokens and retain their exact snapshot');
        self::verify_tree($result);
        return $result;
    }

    public static function children(File_Frontend $file, int $parent): array
    {
        $children = [];
        $id = $file->syntax->nodes[$parent - 1]->first_child_id;
        while ($id !== 0) {
            self::check(($id > 0) && ($id <= count($file->syntax->nodes)) && (!isset($children[$id])), 'Child list must contain valid IDs without a cycle');
            $children[$id] = $file->syntax->nodes[$id - 1];
            $id = $file->syntax->nodes[$id - 1]->next_sibling_id;
        }
        return $children;
    }

    public static function text(File_Frontend $file, int $id): string
    {
        $node = $file->syntax->nodes[$id - 1];
        return substr($file->tokens->source->content, $node->start, $node->length);
    }

    public static function verify_tree(File_Frontend $file): void
    {
        $root = $file->syntax->root_node_id;
        self::check(($file->syntax->nodes[$root - 1]->kind === syntax_kind::file_root)
            && ($file->syntax->nodes[$root - 1]->next_sibling_id === 0),
            'Every file has one file root without siblings');
        self::check(array_keys(self::children($file, $root)) === [$file->entry_body_id, ...$file->defined_entities],
            'Entry and definition indexes reference the single root children without copies');
        $pending = [$root];
        $seen = [];
        while ($pending !== [])
        {
            $id = array_pop($pending);
            self::check(!isset($seen[$id]), 'A syntax node belongs to one parent or root');
            $seen[$id] = true;
            $node = $file->syntax->nodes[$id - 1];
            self::check(($node->start >= 0) && ($node->length >= 0)
                && (($node->start + $node->length) <= strlen($file->tokens->source->content)), 'Every span stays within its source');
            foreach (self::children($file, $id) as $child_id => $child) {
                self::check(($child->start >= $node->start) && (($child->start + $child->length) <= ($node->start + $node->length)),
                    'Parent span covers each child');
                $pending[] = $child_id;
            }
        }
        self::check(count($seen) === count($file->syntax->nodes), 'Every allocated node is reachable');
    }

    public static function reject(string $text, int $offset, string $reason): void
    {
        try {
            self::parse($text);
        }
        catch (\diagnostics\Source_Error $error) {
            self::check(($error->source_file_id === 9) && ($error->path === 'parser-test.phs')
                && ($error->start === $offset) && str_contains($error->getMessage(), $reason), 'Syntax error has correct source/offset/reason: ' . $error->getMessage());
            return;
        }
        throw new Exception('Expected syntax rejection: ' . $text);
    }

    public static function reject_access(callable $action): void
    {
        try {
            $action();
        }
        catch (\LogicException $error) {
            return;
        }
        throw new Exception('Expected invalid syntax access rejection');
    }
}
