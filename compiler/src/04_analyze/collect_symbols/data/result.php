<?php
declare(strict_types=1);
namespace collect_symbols;

final class File_Declarations {
    public array $rows /** vector<Declaration_Fact> */ = [];
    public bool $valid = true;
    public int $error_start = 0;
    public int $error_length = 0;
    public string $error_reason = '';
    public function __construct(public readonly \parse\Parse_Result $frontend) {}
}

/** Failed collection publishes no candidate rows/changes; previous remains untouched. */
final class Symbol_Refresh {
    public array $changes /** vector<Symbol_Change> */ = [];
    public bool $valid = true;
    public string $error_path = '';
    public int $error_start = 0;
    public int $error_length = 0;
    public string $error_reason = '';
    public function __construct(public readonly Symbol_Store $previous, public Symbol_Store $current) {}
}
