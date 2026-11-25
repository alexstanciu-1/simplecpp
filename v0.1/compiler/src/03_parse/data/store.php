<?php
declare(strict_types=1);
namespace parse;

/** Complete current membership, ordered by the input snapshot. Publish read-only. */
final class Frontend_Set {
    public array $files /** vector<Parse_Result> */ = [];
    private array $by_path /** hash<int> */ = [];
    public int $entry_index = 0;
    public bool $valid = true;
    public string $error_path = '';
    public int $error_start = 0;
    public int $error_length = 0;
    public string $error_reason = '';

    public function __construct() { $this->entry_index = -1; }
    public function find_path(string $path): int {
        if (!isset($this->by_path[$path])) { return -1; }
        return $this->by_path[$path];
    }
    /** Producer-only append; the join owns membership completeness. */
    public function add(Parse_Result $file): void {
        Frontend_Set::require_file($file);
        $path = $file->tokens->source->path;
        if (isset($this->by_path[$path])) { throw new \LogicException('Duplicate frontend path'); }
        $this->by_path[$path] = q_count($this->files);
        $this->files[] = $file;
    }
    public static function failed(Parse_Result $failure): Frontend_Set {
        $out = new Frontend_Set();
        $out->valid = false;
        $out->error_path = $failure->tokens->source->path;
        $out->error_start = $failure->error_start;
        $out->error_length = $failure->error_length;
        $out->error_reason = $failure->error_reason;
        return $out;
    }
    /** Check file-root/index consistency, not every descendant or semantic role. */
    public static function require_file(Parse_Result $file): void {
        if ((!$file->valid) || (!$file->tokens->valid) || ($file->tokens->source->path === '')) { throw new \LogicException('Invalid frontend'); }
        $size = $file->tree->size();
        if (($file->root < 1) || ($file->root > $size) || ($file->entry < 1) || ($file->entry > $size)) { throw new \LogicException('Invalid frontend indexes'); }
        $root = $file->tree->row($file->root);
        $entry = $file->tree->row($file->entry);
        $length = string_byte_len($file->tokens->source->content);
        if (((int)$root->kind !== \parse\SYNTAX_FILE_ROOT) || ((int)$root->first_child !== $file->entry) || ((int)$root->next_sibling !== 0)
            || ((int)$root->start !== 0) || ((int)$root->length !== $length) || ((int)$entry->kind !== \parse\SYNTAX_BLOCK)
            || ((int)$entry->start !== 0) || ((int)$entry->length !== $length)) { throw new \LogicException('Invalid frontend root'); }
        $next = (int)$entry->next_sibling;
        foreach ($file->definitions as $id) {
            if (($id < 1) || ($id > $size) || ($id !== $next) || ($id === $file->entry) || ($id === $file->root)) { throw new \LogicException('Invalid frontend definition index'); }
            $row = $file->tree->row($id);
            $next = (int)$row->next_sibling;
        }
        if ($next !== 0) { throw new \LogicException('Incomplete frontend definition index'); }
    }
}

/** Fixed plan: current positions partition into worker tasks and retained results. */
final class Parser_Plan {
    public array $tasks /** vector<int> */ = [];
    public array $retained /** hash<Parse_Result,int> */ = [];
    public function __construct(public \tokenize\Lexical_Project $current) {}
}
