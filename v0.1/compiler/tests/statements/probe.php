<?php
declare(strict_types=1);
namespace statement_test;
final class Probe {
    public static function run(string $path): void {
        $source = new \read_sources\Source_Buffer();
        $source->path = $path;
        $source->content = fs_read_text($path);
        $tokens = \tokenize\File_Tokenizer::tokenize($source);
        $result = \parse\File_Parser::parse($tokens);
        if (!$result->valid) {
            if (($result->root !== 0) || ($result->entry !== 0) || (q_count($result->definitions) !== 0) || ($result->tree->size() !== 0)) {
                throw new \LogicException('Failed parse published partial syntax');
            }
            if ($result->error_reason === '') { throw new \LogicException('Missing rejection reason'); }
            echo '{"error":[', $result->error_start, ',', $result->error_length, "]}\n";
            return;
        }
        $root = $result->tree->row($result->root);
        if ((int)$root->kind !== \parse\SYNTAX_FILE_ROOT) { throw new \LogicException('Missing file root'); }
        if ((int)$root->first_child !== $result->entry) { throw new \LogicException('Missing entry index'); }
        $entry = $result->tree->row($result->entry);
        if ((int)$entry->kind !== \parse\SYNTAX_BLOCK) { throw new \LogicException('Missing entry block'); }
        $next_id = (int)$entry->next_sibling;
        foreach ($result->definitions as $definition) {
            if ($definition !== $next_id) { throw new \LogicException('Definition index disagrees with tree'); }
            $row = $result->tree->row($definition);
            $next_id = (int)$row->next_sibling;
        }
        if ($next_id !== 0) { throw new \LogicException('Incomplete definition index'); }
        $seen /** hash<bool,int> */ = [];
        $queue /** vector<int> */ = [];
        $queue[] = $result->root;
        echo '{"rows":[';
        for ($i /** int */ = 0; $i < q_count($queue); ++$i) {
            $id = $queue[$i];
            if (isset($seen[$id])) { throw new \LogicException('Shared/cyclic syntax node'); }
            $seen[$id] = true;
            $row = $result->tree->row($id);
            if ((int)$row->start + (int)$row->length > string_byte_len($source->content)) { throw new \LogicException('Span outside source'); }
            $count /** int */ = 0;
            $child = (int)$row->first_child;
            while ($child !== 0) {
                $queue[] = $child;
                ++$count;
                $next = $result->tree->row($child);
                if (((int)$next->start < (int)$row->start) || ((int)$next->start + (int)$next->length > (int)$row->start + (int)$row->length)) {
                    throw new \LogicException('Child outside parent span');
                }
                if ($count > $result->tree->size()) { throw new \LogicException('Cyclic siblings'); }
                $child = (int)$next->next_sibling;
            }
            if ($i !== 0) { echo ','; }
            echo '[', $row->kind, ',', $row->start, ',', $row->length, ',', $count, ']';
        }
        if (q_count($seen) !== $result->tree->size()) { throw new \LogicException('Unreachable syntax row'); }
        echo '],"definitions":', q_count($result->definitions), "}\n";
    }
}
