<?php
declare(strict_types=1);
namespace prepare_backend;

/** Fixed selected lifecycle ABI work and private output provenance. */
final class Lifecycle_Preparation_Task {
    public function __construct(public readonly \type_model\Lifecycle_Operation $operation,
        public readonly Backend_Configuration $configuration) {}
}
final class Lifecycle_Preparation_Result {
    public function __construct(public readonly Lifecycle_Preparation_Task $task, public readonly Abi_Target $target) {}
}
