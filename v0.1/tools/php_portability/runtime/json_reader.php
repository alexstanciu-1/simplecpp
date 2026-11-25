<?php
declare(strict_types=1);
namespace scpp;

/** Host-only pull facade: no decoded object/array tree. JSON decoding is scalar-string only. */
final class Json_Reader {
    private int $position = 0;
    private array $frames = [];
    private bool $pending = true;
    private bool $failed = false;
    private bool $finished = false;
    private string $path = '$';
    private string $key = '';
    public function __construct(private string $source, private string $file, private int $limit) {
        if ($limit < 1 || $limit > 256) { $this->fail('max_depth must be 1..256'); }
        if (preg_match('//u', $source) !== 1) { $this->fail('invalid UTF-8'); }
    }
    private function fail(string $message): never {
        $this->failed = true;
        throw new \RuntimeException($this->file . ': byte ' . $this->position . ' at ' . $this->path . ': ' . $message);
    }
    private function ready(): void { if ($this->failed) { $this->fail('JSON reader is failed'); } }
    private function peek(): string { return $this->source[$this->position] ?? ''; }
    private function whitespace(): void {
        while ($this->position < strlen($this->source) && str_contains(" \t\r\n", $this->peek())) { $this->position++; }
    }
    public function kind(): string {
        $this->ready();
        if (!$this->pending || $this->finished) { $this->fail('no selected JSON value'); }
        $this->whitespace();
        $byte = $this->peek();
        return match ($byte) {
            '{' => 'object', '[' => 'array', '"' => 'string', 't', 'f' => 'boolean', 'n' => 'null',
            default => ($byte === '-' || ($byte !== '' && ctype_digit($byte))) ? 'number' : $this->fail('unexpected character'),
        };
    }
    private function require_kind(string $kind): void { if ($this->kind() !== $kind) { $this->fail('expected ' . $kind); } }
    private function string_token(): string {
        $start = $this->position;
        if ($this->peek() !== '"') { $this->fail('expected string'); }
        $this->position++;
        while ($this->position < strlen($this->source)) {
            $byte = $this->source[$this->position++];
            if ($byte === '"') {
                try { return json_decode(substr($this->source, $start, $this->position - $start), false, 2, JSON_THROW_ON_ERROR); }
                catch (\JsonException $error) { $this->position = $start; $this->fail('invalid JSON string'); }
            }
            if ($byte === '\\') { $this->position++; }
        }
        $this->position = $start; $this->fail('unterminated string');
    }
    private function begin(bool $object): void {
        $this->require_kind($object ? 'object' : 'array');
        if (count($this->frames) >= $this->limit) { $this->fail('nesting limit exceeded'); }
        $this->position++;
        $this->frames[] = ['object' => $object, 'first' => true, 'index' => 0, 'path' => $this->path, 'keys' => []];
        $this->pending = false;
    }
    public function begin_object(): void { $this->begin(true); }
    public function begin_array(): void { $this->begin(false); }
    private function next(bool $object): bool {
        $this->ready();
        $index = count($this->frames) - 1;
        if ($this->pending || $index < 0 || $this->frames[$index]['object'] !== $object) { $this->fail('consume selected value before advancing the matching container'); }
        $frame =& $this->frames[$index];
        $this->path = $frame['path']; $this->whitespace();
        if ($this->peek() === ($object ? '}' : ']')) { $this->position++; array_pop($this->frames); return false; }
        if (!$frame['first']) {
            if ($this->peek() !== ',') { $this->fail('expected comma or container end'); }
            $this->position++; $this->whitespace();
        }
        $frame['first'] = false;
        if ($object) {
            $start = $this->position; $this->key = $this->string_token();
            $this->path .= '[' . substr($this->source, $start, $this->position - $start) . ']';
            $identity = 'k:' . $this->key;
            if (isset($frame['keys'][$identity])) { $this->position = $start; $this->fail('duplicate object key'); }
            $frame['keys'][$identity] = true;
            $this->whitespace();
            if ($this->peek() !== ':') { $this->fail("expected ':'"); }
            $this->position++;
        } else { $this->path .= '[' . $frame['index']++ . ']'; }
        $this->pending = true; return true;
    }
    public function next_property(): bool { return $this->next(true); }
    public function next_element(): bool { return $this->next(false); }
    public function property_name(): string {
        $this->ready(); $index = count($this->frames) - 1;
        if (!$this->pending || $index < 0 || !$this->frames[$index]['object']) { $this->fail('no selected object property'); }
        return $this->key;
    }
    public function read_string(): string {
        $this->require_kind('string'); $value = $this->string_token(); $this->pending = false; return $value;
    }
    public function read_number(): string {
        $this->require_kind('number');
        if (!preg_match('/\G-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?(?:[eE][+-]?[0-9]+)?/', $this->source, $matches, 0, $this->position)) { $this->fail('invalid number'); }
        $value = $matches[0]; $this->position += strlen($value);
        if ($this->peek() !== '' && str_contains('0123456789.eE', $this->peek())) { $this->fail('invalid number'); }
        $this->pending = false; return $value;
    }
    public function read_integer(): int {
        $this->require_kind('number'); $start = $this->position; $value = $this->read_number();
        $negative = str_starts_with($value, '-'); $digits = $negative ? substr($value, 1) : $value;
        $bound = $negative ? '9223372036854775808' : '9223372036854775807';
        if (strpbrk($value, '.eE') !== false || strlen($digits) > 19 || (strlen($digits) === 19 && strcmp($digits, $bound) > 0)) {
            $this->position = $start; $this->fail('expected signed 64-bit integer spelling');
        }
        return (int)$value;
    }
    private function literal(string $text): void {
        if (substr($this->source, $this->position, strlen($text)) !== $text) { $this->fail('invalid literal'); }
        $this->position += strlen($text); $this->pending = false;
    }
    public function read_boolean(): bool { $this->require_kind('boolean'); $value = $this->peek() === 't'; $this->literal($value ? 'true' : 'false'); return $value; }
    public function read_null(): void { $this->require_kind('null'); $this->literal('null'); }
    public function reject(string $message): void { $this->ready(); $this->fail($message); }
    public function finish_document(): void {
        $this->ready();
        if ($this->pending || $this->frames !== []) { $this->fail('unconsumed JSON value'); }
        $this->whitespace(); if ($this->position !== strlen($this->source)) { $this->fail('trailing non-whitespace after JSON value'); }
        $this->finished = true;
    }
}
