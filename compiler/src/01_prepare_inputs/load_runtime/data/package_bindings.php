<?php
declare(strict_types=1);
namespace load_runtime;

/** Compiler-owned binding maps; membership is local, accepted definitions/exports are shared. */
final class Package_Bindings {
    public function __construct(public readonly array $types /** hash<\type_model\Type_Reference> */,
        public readonly array $callables /** hash<\type_model\Type_Reference> */,
        public readonly array $imports /** hash<Runtime_Type_Import> */,
        public readonly array $sources /** hash<\prepare_backend\Source_Type_Export> */) {}
}
