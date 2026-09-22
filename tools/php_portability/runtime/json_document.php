<?php
declare(strict_types=1);
namespace scpp;

/** Immutable schema-view subset. Raw numeric tokens and generic JSON roundtripping are not exposed. */
final class Json_View {
    private function __construct(private readonly mixed $value) {}

    public static function read(string $text): self {
        try { return new self(\json_decode($text, false, 129, JSON_THROW_ON_ERROR)); }
        catch (\JsonException $error) { throw new \JsonException('Invalid JSON', 0, $error); }
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
    private function require_object(): void {
        if (!$this->value instanceof \stdClass) { throw new \RuntimeException('JSON node is not an object'); }
    }
}
function json_read(string $text): Json_View { return Json_View::read($text); }
