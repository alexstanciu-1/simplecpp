<?php
declare(strict_types=1);
namespace check_bodies;
const PROJECTION_FIELD = 1;
const PROJECTION_INDEX = 2;
const PROJECTION_ELEMENT = 3;
/** Field ordinal or evaluated index, with target-evaluation call boundary. */
final class Place_Projection {
    public function __construct(public readonly int $kind, public readonly int $operand,
        public readonly int $type_id, public readonly int $call_end = 0) {
        if (($kind < \check_bodies\PROJECTION_FIELD) || ($kind > \check_bodies\PROJECTION_ELEMENT)) {
            throw new \InvalidArgumentException('Invalid checked projection kind');
        }
        if (($operand < 0) || ($type_id < 1) || ($call_end < 0)) {
            throw new \InvalidArgumentException('Invalid checked location projection');
        }
        if ($kind !== \check_bodies\PROJECTION_FIELD) {
            if ($operand === 0) { throw new \InvalidArgumentException('Invalid checked location projection'); }
        }
    }
}
/** Live storage root and fixed root-to-leaf projections; no independent allocation ownership. */
final class Place {
    private array $path /** vector<Place_Projection> */ = [];
    public function __construct(public readonly int $local_id, array $projections /** vector<Place_Projection> */) {
        if ($local_id < 1) { throw new \InvalidArgumentException('Invalid storage place'); }
        foreach ($projections as $projection) { $this->path[] = $projection; }
    }
    public function size(): int { return q_count($this->path); }
    public function at(int $index): Place_Projection {
        if (($index < 0) || ($index >= q_count($this->path))) { throw new \OutOfBoundsException('Missing location projection'); }
        return $this->path[$index];
    }
    public function allocation_backed(): bool {
        $found = false;
        foreach ($this->path as $projection) {
            if ($projection->kind === \check_bodies\PROJECTION_ELEMENT) { $found = true; break; }
        }
        return $found;
    }
    /** Position of the next evaluated index, or -1. No copied index list or generator state. */
    public function next_index(int $start): int {
        if (($start < 0) || ($start > q_count($this->path))) { throw new \OutOfBoundsException('Invalid location index cursor'); }
        $found = -1;
        for ($i = $start; $i < q_count($this->path); $i++) {
            if ($this->path[$i]->kind !== \check_bodies\PROJECTION_FIELD) { $found = $i; break; }
        }
        return $found;
    }
}
