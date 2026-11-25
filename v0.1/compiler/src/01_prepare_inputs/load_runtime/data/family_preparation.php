<?php
declare(strict_types=1);
namespace load_runtime;

/** Exact accepted owner of a package-local reference. Target provenance is checked by package acceptance. */
final class Runtime_Type_Import {
    public function __construct(public readonly string $provider, public readonly string $type_id,
        public readonly Runtime_Type $type, public readonly string $target_triple, public readonly string $data_layout) {}
}
