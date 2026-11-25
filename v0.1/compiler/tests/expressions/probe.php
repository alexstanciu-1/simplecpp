<?php
declare(strict_types=1);
namespace expression_test;
final class Probe {
    public static function run(string $path, bool $type): void {
        $source = new \read_sources\Source_Buffer();
        $source->path = $path;
        $source->content = fs_read_text($path);
        $tokens = \tokenize\File_Tokenizer::tokenize($source);
        $result = \parse\File_Parser::parse_expression($tokens, $type);
        if (!$result->valid) {
            echo '{"error":[', $result->error_start, ',', $result->error_length, "]}\n";
            return;
        }
        $queue /** vector<int> */ = [];
        $queue[] = $result->root;
        echo '{"rows":[';
        for ($i /** int */ = 0; $i < q_count($queue); ++$i) {
            $row = $result->tree->row($queue[$i]);
            $count /** int */ = 0;
            $child = (int)$row->first_child;
            while ($child !== 0) {
                $queue[] = $child;
                ++$count;
                $next = $result->tree->row($child);
                $child = (int)$next->next_sibling;
            }
            if ($i !== 0) { echo ','; }
            echo '[', $row->kind, ',', $row->start, ',', $row->length, ',', $count, ']';
        }
        echo "]}\n";
    }
}
