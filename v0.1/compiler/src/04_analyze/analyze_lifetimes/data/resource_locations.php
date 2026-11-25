<?php
declare(strict_types=1);
namespace analyze_lifetimes;
/** Immutable body-local descriptor identity; dynamic element indices never belong to its path. */
final class Resource_Location {
    private array $ordinals /** vector<int> */ = [];
    public function __construct(public readonly int $local, array $path /** vector<int> */) {
        if ($local < 1) { throw new \InvalidArgumentException('Invalid resource local'); }
        foreach ($path as $ordinal) {
            if ($ordinal < 0) { throw new \InvalidArgumentException('Invalid resource field ordinal'); }
            $this->ordinals[] = $ordinal;
        }
    }
    public function size(): int { return q_count($this->ordinals); }
    public function at(int $index): int {
        if (($index < 0) || ($index >= q_count($this->ordinals))) { throw new \OutOfBoundsException('Missing resource field ordinal'); }
        return $this->ordinals[$index];
    }
    public function path(): array /** vector<int> */ { return $this->ordinals; }
    public function key(): string {
        $suffix = ''; if (q_count($this->ordinals) !== 0) { $suffix = ':' . Resource_Locations::path_key($this->ordinals); }
        return $this->local . $suffix;
    }
}
final class Parameter_Endpoint {
    public function __construct(public readonly int $position, public readonly string $path) {}
}
final class Distinct_Endpoints {
    public function __construct(public readonly string $left, public readonly string $right) {}
}
/** Named row replaces a zero-based-parameter -> nested-array setup map. */
final class Parameter_Resources {
    private array $rows /** vector<vector<int>> */ = [];
    public function __construct(public readonly int $position, array $paths /** vector<vector<int>> */) {
        foreach ($paths as $path) { $copy /** vector<int> */ = []; foreach ($path as $ordinal) { $copy[] = $ordinal; } $this->rows[] = $copy; }
    }
    public function size(): int { return q_count($this->rows); }
    public function at(int $index): array /** vector<int> */ {
        if (($index < 0) || ($index >= q_count($this->rows))) { throw new \OutOfBoundsException('Missing parameter resource path'); }
        $copy /** vector<int> */ = []; foreach ($this->rows[$index] as $ordinal) { $copy[] = $ordinal; } return $copy;
    }
}
