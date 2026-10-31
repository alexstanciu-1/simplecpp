<?php
declare(strict_types=1);
namespace Scpp\Api;

function requireThat(bool $condition, string $message): void {
    if (!$condition) {
        throw new \RuntimeException($message);
    }
}

function object(mixed $value): array {
    requireThat($value instanceof \stdClass, 'Expected metadata object');
    $fields = get_object_vars($value);
    ksort($fields, SORT_STRING);
    return $fields;
}

function fields(mixed $value, array $allowed): array {
    $fields = object($value);
    foreach ($fields as $key => $_) {
        requireThat(in_array($key, $allowed, true), "Unknown field: $key");
    }
    return $fields;
}

function at(mixed $value, string $key): mixed {
    $fields = object($value);
    requireThat(array_key_exists($key, $fields), "Missing metadata field: $key");
    return $fields[$key];
}

function textValue(mixed $value): string {
    requireThat(is_string($value), 'Expected metadata string');
    return $value;
}

function listValue(mixed $value): array {
    requireThat(is_array($value), 'Expected metadata array');
    return $value;
}

function boolean(mixed $value): bool {
    requireThat(is_bool($value), 'Expected metadata boolean');
    return $value;
}

function unsigned(mixed $value): int {
    requireThat(is_int($value) && $value >= 0, 'Expected unsigned integer token in supported range');
    return $value;
}

function literals(mixed $record, array $expected): void {
    foreach ($expected as $key => $value) {
        requireThat(at($record, $key) === $value, "Unsupported $key");
    }
}

function names(mixed $value, string $message = 'Duplicate name'): array {
    $out = [];
    foreach (listValue($value) as $name) {
        $name = textValue($name);
        requireThat(!in_array($name, $out, true), $message);
        $out[] = $name;
    }
    sort($out, SORT_STRING);
    return $out;
}

function headerPath(mixed $value, bool $ownership = false): string {
    $path = textValue($value);
    requireThat(preg_match('~^scpp/[a-z0-9_/.]+\.hpp$~D', $path) === 1 && !str_contains($path, '..') && !str_contains($path, '//') && ($ownership || !str_contains($path, '/.')), 'Invalid public header path');
    return $path;
}

function readFile(string $path): string {
    $bytes = @file_get_contents($path);
    requireThat($bytes !== false, "Cannot read $path");
    return $bytes;
}

// Numbers outside PHP's exact nonnegative integer domain stay lexical. Semantic
// readers reject them where an integer is required; parsing never rounds them.
final readonly class JsonNumber {
    public string $spelling;

    public function __construct(string $spelling) {
        $this->spelling = $spelling;
    }
}
final class JsonReader {
    private int $offset = 0;
    private string $input;
    private string $file;

    public function __construct(string $input, string $file) {
        $this->input = $input;
        $this->file = $file;
    }
    private function fail(string $path, string $message): never {
        throw new \RuntimeException("{$this->file}: byte {$this->offset} at $path: $message");
    }
    private function space(): void {
        $this->offset += strspn($this->input, " \t\r\n", $this->offset);
    }
    private function string(string $path): string {
        $start = $this->offset++;
        $length = strlen($this->input);
        while ($this->offset < $length) {
            $byte = $this->input[$this->offset++];
            if ($byte === '\\') {
                ++$this->offset;
            }
            elseif ($byte === '"') {
                try {
                    return json_decode(substr($this->input, $start, $this->offset - $start), false, 512, JSON_THROW_ON_ERROR);
                }
                catch (\JsonException $error) {
                    $this->fail($path, $error->getMessage());
                }
            }
        }
        $this->fail($path, 'Unterminated JSON string');
    }
    private function value(string $path, int $depth): mixed {
        $this->space();
        $byte = $this->input[$this->offset] ?? '';
        if ($byte === '"') {
            return $this->string($path);
        }
        if ($byte === '{' || $byte === '[') {
            if ($depth >= 128) {
                $this->fail($path, 'nesting limit exceeded');
            }
            ++$this->offset;
            $object = $byte === '{';
            $close = $object ? '}' : ']';
            $result = $object ? new \stdClass() : [];
            $this->space();
            if (($this->input[$this->offset] ?? '') === $close) {
                ++$this->offset;
                return $result;
            }
            while (true) {
                $this->space();
                if ($object) {
                    if (($this->input[$this->offset] ?? '') !== '"') {
                        $this->fail($path, 'Expected object key');
                    }
                    $key = $this->string($path);
                    if (property_exists($result, $key)) {
                        $this->fail($path, 'duplicate object key');
                    }
                    $this->space();
                    if (($this->input[$this->offset++] ?? '') !== ':') {
                        $this->fail($path, 'Expected colon');
                    }
                    $result->{$key} = $this->value($path . '[' . json_encode($key) . ']', $depth + 1);
                }
                else {
                    $result[] = $this->value($path . '[' . count($result) . ']', $depth + 1);
                }
                $this->space();
                $next = $this->input[$this->offset++] ?? '';
                if ($next === $close) {
                    return $result;
                }
                if ($next !== ',') {
                    $this->fail($path, 'Expected comma or closing delimiter');
                }
            }
        }
        foreach (['true' => true, 'false' => false, 'null' => null] as $word => $value) {
            if (substr($this->input, $this->offset, strlen($word)) === $word) {
                $this->offset += strlen($word);
                return $value;
            }
        }
        if (preg_match('/\G-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?(?:[eE][+-]?[0-9]+)?/', $this->input, $match, 0, $this->offset) === 1) {
            $this->offset += strlen($match[0]);
            $token = $match[0];
            if ((strspn($token, '0123456789') === strlen($token)) && (strlen($token) < strlen((string) PHP_INT_MAX) || (strlen($token) === strlen((string) PHP_INT_MAX) && strcmp($token, (string) PHP_INT_MAX) <= 0))) {
                return (int) $token;
            }
            return new JsonNumber($token);
        }
        $this->fail($path, 'Invalid JSON value');
    }
    public function read(): mixed {
        if (preg_match('//u', $this->input) !== 1) {
            $this->fail('$', 'invalid UTF-8');
        }
        $value = $this->value('$', 0);
        $this->space();
        if ($this->offset !== strlen($this->input)) {
            $this->fail('$', 'Trailing JSON input');
        }
        return $value;
    }
}

function decode(string $bytes, string $file): mixed {
    return (new JsonReader($bytes, $file))->read();
}

function canonical(mixed $value): mixed {
    if ($value instanceof \stdClass) {
        $out = new \stdClass();
        foreach (object($value) as $key => $child) {
            $out->{$key} = canonical($child);
        }
        return $out;
    }
    if (is_array($value)) {
        if (!array_is_list($value)) {
            ksort($value, SORT_STRING);
        }
        return array_map(canonical(...), $value);
    }
    return $value;
}

function encode(mixed $value): string {
    return json_encode(canonical($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS);
}
