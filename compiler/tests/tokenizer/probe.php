<?php
declare(strict_types=1);
namespace tokenizer_test;
final class Probe {
    public static function run(string $path): void {
        $source = new \read_sources\Source_Buffer();
        $source->path = $path;
        $source->content = fs_read_text($path);
        $buffer = \tokenize\File_Tokenizer::tokenize($source);
        if (!$buffer->valid) {
            echo '{"error":[', $buffer->error_start, ',', $buffer->error_length, '],"path":', json_quote($buffer->source->path), ',"rows":', q_count($buffer->rows), "}\n";
            return;
        }
        echo '{"rows":[';
        $first = true;
        foreach ($buffer->rows as $row) {
            if (!$first) { echo ','; }
            $first = false;
            echo '[', json_quote(\tokenize\Token_Kinds::name((int)$row->kind)), ',', $row->start, ',', $row->length, ']';
        }
        echo '],"pure":', $source->content === fs_read_text($path) ? 'true' : 'false', "}\n";
    }
    public static function batch(string $good, string $bad): void {
        $sources = new \read_sources\Source_Texts();
        $first = new \read_sources\Source_Buffer();
        $first->path = $good;
        $first->content = fs_read_text($good);
        $second = new \read_sources\Source_Buffer();
        $second->path = $bad;
        $second->content = fs_read_text($bad);
        $sources->buffers[] = $first;
        $sources->buffers[] = $second;
        $sources->entry_index = 1;
        $project = \tokenize\Tokenizer::tokenize($sources);
        $buffer = $project->buffers[0];
        echo '{"batch_valid":', $project->valid ? 'true' : 'false', ',"entry":', $project->entry_index,
            ',"buffers":', q_count($project->buffers), ',"first_valid":', $buffer->valid ? 'true' : 'false', "}\n";
    }
}
