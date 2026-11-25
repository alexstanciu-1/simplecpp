<?php
declare(strict_types=1);

/*
 * Role: Fixed native prefix ABI tasks and private prepared outputs.
 * Used by: Storage_Preparation; Storage_Join; Backend_Context
 * Flow: runtime primitive + target -> selected task -> accepted physical ABI.
 */
namespace prepare_backend;

final class storage_preparation_task {
    public function __construct(public readonly \type_model\storage_primitive $primitive,
        public readonly backend_configuration $configuration)
    {
    }
}

final class prepared_storage_primitive {
    public function __construct(public readonly storage_preparation_task $task, public readonly abi_target $target)
    {
    }
}
