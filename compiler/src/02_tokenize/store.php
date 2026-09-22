<?php
declare(strict_types=1);
namespace tokenize;

/** Owns the source lifetime; rows retain byte spans, never copied lexemes. */
final class Lexical_Buffer {
    public array $rows /** vector<Token_Row> */ = [];
    public bool $valid = true;
    public int $error_start = 0;
    public int $error_length = 0;
    public string $error_reason = '';
    public function __construct(public \read_sources\Source_Buffer $source) {}
}

/** Complete lexical observation; callers must check valid before parsing. */
final class Lexical_Project {
    public array $buffers /** vector<Lexical_Buffer> */ = [];
    public int $entry_index = 0;
    public bool $valid = true;
}
