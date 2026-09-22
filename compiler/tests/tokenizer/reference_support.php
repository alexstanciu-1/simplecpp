<?php
namespace read_sources {
class Source_Buffer {
    public function __construct(public int $source_file_id, public string $path, public int $mtime, public string $content) {}
}
}
namespace diagnostics {
class Source_Error extends \Exception {
    public function __construct(public int $source_file_id, public string $path, public int $start, public int $length, string $reason) { parent::__construct($reason); }
}
}
namespace tokenize {
class Token_Buffer {
    public array $rows = [];
    public function __construct(public \read_sources\Source_Buffer $source) {}
    public function to_json(): string {
        return json_encode(['tokens'=>array_map(fn($t)=>['text'=>substr($this->source->content,$t->start,$t->length)],$this->rows)]);
    }
}
}
