<?php
declare(strict_types=1);

/*
 * Role: Selected lifecycle ABI work and private results.
 * Used by: LLVM_Backend; Callable_Preparer; Lifecycle_Join
 * Flow: fixed inputs -> owned contracts -> read-only consumers
 */

namespace prepare_backend;

/** @compiler-internal Fixed lifecycle preparation inputs selected before worker execution; shared read-only. */
final class lifecycle_preparation_task {
    public function __construct(public readonly \type_model\runtime_lifecycle_operation|\type_model\source_lifecycle_operation $operation,
        public readonly backend_configuration $configuration)
    {
    }
}

/** @compiler-internal Private worker output with input provenance; accepted targets alone enter Backend_Context. */
final class lifecycle_preparation_result {
    public function __construct(public readonly lifecycle_preparation_task $task, public readonly abi_target $target)
    {
    }
}
