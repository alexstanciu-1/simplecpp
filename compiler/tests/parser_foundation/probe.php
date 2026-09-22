<?php
declare(strict_types=1);
namespace parser_test;
final class Probe {
    public static function angles(string $path): void {
        $source = new \read_sources\Source_Buffer();
        $source->content = fs_read_text($path);
        $tokens = \tokenize\File_Tokenizer::tokenize($source);
        $ends = \parse\Binary_Syntax::angle_ends($tokens);
        echo '[';
        $first = true;
        foreach ($ends as $open => $close) {
            if (!$first) { echo ','; }
            $first = false;
            echo '[', $open, ',', $close, ']';
        }
        echo "]\n";
    }
    public static function arena(): void {
        $arena = new \parse\Syntax_Arena();
        $root = $arena->add(\parse\SYNTAX_BLOCK, 0, 0);
        $first = $arena->add(\parse\SYNTAX_INTEGER_LITERAL, 2, 1);
        $second = $arena->add(\parse\SYNTAX_NAME, 5, 3);
        $arena->child($root, $first);
        $arena->child($root, $second);
        $before = $arena->row($root);
        $arena->finish($root, 8);
        $copy = $arena->row($first);
        $copy->start = 99;
        $stored = $arena->row($first);
        $owner = $arena->row($root);
        echo '{"size":', $arena->size(), ',"first":', $owner->first_child,
            ',"last":', $owner->last_child, ',"next":', $stored->next_sibling,
            ',"length":', $owner->length, ',"old_length":', $before->length,
            ',"stored_start":', $stored->start, "}\n";
        try { $bad = $arena->row(0); echo "false\n"; } catch (\Exception $error) { echo "true\n"; }
        try { $arena->finish($second, 1); echo "false\n"; } catch (\Exception $error) { echo "true\n"; }
        try { $arena->child($root, $root); echo "false\n"; } catch (\Exception $error) { echo "true\n"; }
        try { $arena->child($root, $second); echo "false\n"; } catch (\Exception $error) { echo "true\n"; }
        echo '[', \parse\Binary_Syntax::from_token(\tokenize\TOKEN_PLUS), ',',
            \parse\Binary_Syntax::precedence(\parse\SYNTAX_ADDITION_EXPRESSION), ',',
            \parse\Binary_Syntax::precedence(\parse\SYNTAX_LESS_THAN_EXPRESSION), ',',
            \parse\Binary_Syntax::from_token(\tokenize\TOKEN_INTEGER_LITERAL), "]\n";
    }
}
