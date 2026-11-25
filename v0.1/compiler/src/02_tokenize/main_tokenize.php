<?php
declare(strict_types=1);
namespace tokenize;

final class Tokenizer {
    public static function tokenize(\read_sources\Source_Texts $sources): Lexical_Project {
        if ($sources->entry_index < 0) { throw new \InvalidArgumentException('Missing lexical entry'); }
        if ($sources->entry_index >= q_count($sources->buffers)) { throw new \InvalidArgumentException('Invalid lexical entry'); }
        $project = new Lexical_Project();
        $project->entry_index = $sources->entry_index;
        foreach ($sources->buffers as $source) {
            $buffer = File_Tokenizer::tokenize($source);
            if (!$buffer->valid) { $project->valid = false; }
            $project->buffers[] = $buffer;
        }
        return $project;
    }
}
