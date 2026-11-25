<?php
declare(strict_types=1);
namespace load_runtime;

/** Current adapter selection after artifact/receipt checks. Construction alone does not validate it. */
final class Package_Context {
    public function __construct(public readonly string $directory, public readonly string $manifest_source,
        public readonly \type_model\Type_Catalog $catalog, public readonly ?Package_Bindings $bindings,
        public readonly ?Project_Binding $project) {}
}
