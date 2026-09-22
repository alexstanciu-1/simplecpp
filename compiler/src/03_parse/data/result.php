<?php
declare(strict_types=1);
namespace parse;

/** One grammar result retains its exact source/token snapshot; zero IDs mean absent. */
final class Parse_Result {
    public bool $valid = true;
    public int $root = 0;
    public int $entry = 0;
    public array $definitions /** vector<int> */ = [];
    public int $error_start = 0;
    public int $error_length = 0;
    public string $error_reason = '';
    public function __construct(public \tokenize\Lexical_Buffer $tokens, public Syntax_Arena $tree) {}
}
