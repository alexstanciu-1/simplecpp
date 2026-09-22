<?php
declare(strict_types=1);
namespace prepare_backend;

/** Private generated probe, not measured or accepted storage. Empty source means no opaque witness needed. */
final class Layout_Witness {
    public function __construct(public readonly string $source,
        public readonly array $primitives /** hash<string,int> */) {}
}
