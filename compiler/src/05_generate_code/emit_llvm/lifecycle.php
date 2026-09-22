<?php
declare(strict_types=1);

/*
 * Role: Emit selected complete source lifecycle plans without re-deriving semantics.
 * Call map: LLVM_Emitter -> Lifecycle_Emission::emit(); Lifecycle_Emission_Join::join()
 * Output: private generated definitions; accepted once and assembled in the entry module.
 */
namespace emit_llvm;

use prepare_backend\LLVM_Types;

final class Lifecycle_Emission
{
    /** Emit one immutable member plan; fixed arrays use a loop rather than expanded elements. */
    public static function emit(lifecycle_emission_task $task): emitted_lifecycle
    {
        // The type plan selects behavior; the prepared target supplies its physical ABI.
        $operation = $task->operation;
        $backend = $task->backend;
        $target = $backend->abi_for($operation->link_name);
        if ($target?->lifecycle_operation !== $operation) {
            throw new \LogicException('Missing prepared source lifecycle operation');
        }
        $has_source = $operation->kind->has_source();
        $reverse = $operation->order->reverse_members;
        $ir = 'define ' . $target->calling_convention . ' ' . $target->return_type . ' @' . LLVM_Types::quote($operation->link_name)
            . '(ptr %dst' . ($has_source ? ', ptr %src' : '') . ") #0 {\nentry:\n";
        $references = self::references($task);
        $body = $backend->lifecycle_bodies[$operation->link_name] ?? null;

        // A destructor body returns here before fields are destroyed, including early source returns.
        if (($body !== null) && ($operation->order->body_before_members)) {
            $ir .= self::body_call($body, $has_source);
        }
        if ($operation->repeat > 0) {
            $first = $reverse ? $operation->repeat - 1 : 0;
            $ir .= "  br label %elements\nelements:\n  %index = phi i64 [ " . $first . ", %entry ], [ %next, %elements ]\n";
        }
        // Resolve each direct member address. Nested plans remain calls to shared complete operations.
        foreach ($operation->members as $position => $member)
        {
            $storage = self::storage($backend, $member->type_id);
            $member_source = $member->kind->has_source();
            foreach ($member_source ? ['dst', 'src'] : ['dst'] as $argument)
            {
                $address = '%' . $argument . $position;
                if ($operation->repeat > 0) {
                    $ir .= '  ' . $address . ' = getelementptr ' . $storage . ', ptr %' . $argument . ", i64 %index\n";
                }
                else {
                    $layout = $backend->layouts[$operation->type_id];
                    if ($layout->fields[$member->index]->type_id !== $member->type_id) {
                        throw new \LogicException('Source lifecycle field differs from accepted layout');
                    }
                    $ir .= '  ' . $address . ' = getelementptr i8, ptr %' . $argument . ', i64 ' . $layout->offsets[$member->index] . "\n";
                }
            }
            if ($member->operation !== null)
            {
                $callee = $backend->abi_for($member->operation->link_name);
                if ($callee?->lifecycle_operation !== $member->operation) {
                    throw new \LogicException('Missing or stale constituent lifecycle target');
                }
                $ir .= '  call ' . $callee->calling_convention . ' void @' . LLVM_Types::quote($callee->link_name)
                    . '(ptr %dst' . $position . ($member_source ? ', ptr %src' . $position : '') . ")\n";
            }
            else
            {
                // Primitive behavior was selected by composition, never inferred from byte layout here.
                $value = 'zeroinitializer';
                if ($member_source) {
                    $value = '%value' . $position;
                    $ir .= '  ' . $value . ' = load ' . $storage . ', ptr %src' . $position . "\n";
                }
                $ir .= '  store ' . $storage . ' ' . $value . ', ptr %dst' . $position . "\n";
            }
        }
        // One loop covers the extent; reverse destruction needs no expanded per-element plan.
        if ($operation->repeat > 0) {
            $ir .= '  %next = ' . ($reverse ? 'sub' : 'add') . " i64 %index, 1\n"
                . '  %done = icmp eq i64 ' . ($reverse ? '%index, 0' : '%next, ' . $operation->repeat) . "\n"
                . "  br i1 %done, label %exit, label %elements\nexit:\n";
        }
        if (($body !== null) && (!$operation->order->body_before_members)) {
            $ir .= self::body_call($body, $has_source);
        }
        return new emitted_lifecycle($task, $ir . "  ret void\n}\n", $references);
    }

    /** Derive the exact direct imports from the fixed plan; joins validate imports without regenerating IR. */
    public static function references(lifecycle_emission_task $task): array
    {
        $references = [];
        $body = $task->backend->lifecycle_bodies[$task->operation->link_name] ?? null;
        if ($body !== null) {
            $references[$body->link_name] = $body;
        }
        foreach ($task->operation->members as $member)
        {
            if ($member->operation !== null)
            {
                $target = $task->backend->abi_for($member->operation->link_name);
                if ($target?->lifecycle_operation !== $member->operation) {
                    throw new \LogicException('Missing or stale constituent lifecycle target');
                }
                $references[$target->link_name] = $target;
            }
        }
        return $references;
    }

    /** Invoke an ordinary checked source body; the complete operation owns field lifecycle around it. */
    private static function body_call(\prepare_backend\abi_target $body, bool $has_source): string
    {
        return '  call ' . $body->calling_convention . ' void @' . LLVM_Types::quote($body->link_name) . '(ptr %dst' . ($has_source ? ', ptr %src' : '') . ")\n";
    }

    /** Use accepted target storage; arrays preserve the measured stride of their element. */
    private static function storage(\prepare_backend\Backend_Context $backend, int $id): string
    {
        if (isset($backend->layouts[$id])) {
            return $backend->layouts[$id]->llvm_type;
        }
        $shape = $backend->types->representation_for_type($id);
        if ($shape->kind === \type_model\representation_kind::fixed_array) {
            return '[' . $shape->payload->count . ' x ' . self::storage($backend, $shape->payload->element_type) . ']';
        }
        return LLVM_Types::storage($shape);
    }
}
