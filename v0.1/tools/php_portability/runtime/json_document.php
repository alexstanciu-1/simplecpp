<?php
declare(strict_types=1);
namespace scpp;

/** Immutable schema-view subset. Raw numeric tokens and generic JSON roundtripping are not exposed. */
final class Json_View {
    private function __construct(private readonly mixed $value) {}

    public static function read(string $text): self {
        try { $value = \json_decode($text, false, 129, JSON_THROW_ON_ERROR); }
        catch (\JsonException $error) { throw new \JsonException('Invalid JSON', 0, $error); }
        Json_Unique_Keys::validate($text);
        return new self($value);
    }
    public function kind(): string {
        return match (true) {
            $this->value === null => 'null',
            \is_bool($this->value) => 'boolean',
            \is_int($this->value), \is_float($this->value) => 'number',
            \is_string($this->value) => 'string',
            \is_array($this->value) => 'array',
            default => 'object',
        };
    }
    public function size(): int {
        if (\is_array($this->value)) { return \count($this->value); }
        if ($this->value instanceof \stdClass) { return \count(\get_object_vars($this->value)); }
        throw new \RuntimeException('JSON node is not a container');
    }
    public function has(string $key): bool {
        $this->require_object();
        return \property_exists($this->value, $key);
    }
    public function key(int $index): string {
        $this->require_object();
        $keys = \array_keys(\get_object_vars($this->value));
        if ($index < 0 || $index >= \count($keys)) { throw new \RuntimeException('Invalid JSON member index'); }
        return (string) $keys[$index];
    }
    public function member(string $key): self {
        if (!$this->has($key)) { throw new \RuntimeException('Missing JSON member'); }
        return new self($this->value->{$key});
    }
    public function at(int $index): self {
        if (!\is_array($this->value) || $index < 0 || $index >= \count($this->value)) { throw new \RuntimeException('Invalid JSON element index'); }
        return new self($this->value[$index]);
    }
    public function text(): string {
        if (!\is_string($this->value)) { throw new \RuntimeException('JSON node is not a string'); }
        return $this->value;
    }
    public function integer(): int {
        if (!\is_int($this->value)) { throw new \RuntimeException('JSON node is not a signed 64-bit integer'); }
        return $this->value;
    }
    public function boolean(): bool {
        if (!\is_bool($this->value)) { throw new \RuntimeException('JSON node is not a boolean'); }
        return $this->value;
    }
    private function require_object(): void {
        if (!$this->value instanceof \stdClass) { throw new \RuntimeException('JSON node is not an object'); }
    }
}

/** Host-only policy check on already-valid JSON; PHP's decoder owns the grammar.
 * Scan original tokens because decoded objects have already lost duplicate names.
 */
final class Json_Unique_Keys {
    public static function validate(string $text): void {
        $scopes = [];
        $length = \strlen($text);
        for ($position = 0; $position < $length; $position++) {
            $byte = $text[$position];
            if ($byte === '{' || $byte === '[') {
                $scopes[] = [];
            } elseif ($byte === '}' || $byte === ']') {
                \array_pop($scopes);
            } elseif ($byte === '"') {
                $start = $position++;
                while ($text[$position] !== '"') {
                    if ($text[$position] === '\\') { $position++; }
                    $position++;
                }
                $after = $position + 1;
                while ($after < $length && \str_contains(" \t\r\n", $text[$after])) { $after++; }
                if ($after < $length && $text[$after] === ':') {
                    $token = \substr($text, $start, $position - $start + 1);
                    $name = \json_decode($token, false, 512, JSON_THROW_ON_ERROR);
                    // Prefix prevents PHP's numeric-string array-key conversion.
                    $identity = ':' . $name;
                    $scope = \count($scopes) - 1;
                    if (isset($scopes[$scope][$identity])) {
                        throw new \JsonException('json error at byte ' . $start . ': duplicate object key ' . $token);
                    }
                    $scopes[$scope][$identity] = true;
                }
            }
        }
    }
}
function json_read(string $text): Json_View { return Json_View::read($text); }
