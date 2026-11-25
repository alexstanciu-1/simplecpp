<?php
declare(strict_types=1);
namespace load_runtime;

/** Exact receipt bytes and current compiler exports; construction does not authorize a receipt. */
final class Project_Binding {
    public function __construct(public readonly string $receipt,
        public readonly array $exports /** hash<\prepare_backend\Source_Type_Export> */) {}
}
