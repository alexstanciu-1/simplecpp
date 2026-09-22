<?php
declare(strict_types=1);
namespace prepare_backend;

/** Pure physical preparation of an already accepted lifecycle operation. Ordinary callables migrate separately. */
final class Callable_Preparer {
    public static function prepare_lifecycle(Lifecycle_Preparation_Task $task): Lifecycle_Preparation_Result {
        $operation = $task->operation;
        $arity = \type_model\Lifecycle_Roles::has_source($operation->kind) ? 2 : 1;
        $parameters /** vector<Abi_Parameter> */ = [];
        for ($index = 0; $index < $arity; $index++) { $parameters[] = new Abi_Parameter('ptr'); }
        $target = new Abi_Target($operation->link_name,$operation->calling_convention,'void',$parameters,0,$operation);
        if (!Callable_Contract::lifecycle_matches($target,$task->configuration)) { throw new \LogicException('Runtime lifecycle calls require compatible stack and ABI address spaces'); }
        return new Lifecycle_Preparation_Result($task,$target);
    }
}
