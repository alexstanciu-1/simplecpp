<?php
declare(strict_types=1);

/*
 * Role: Select dependency-ready ownership work and retain exact accepted contracts.
 * Used by: Lifetime_Analyzer before ordinary lifetime selection
 * Call map: run() -> requests() -> body_dependencies()
 *   run() -> Ownership_Worker::prepare(); Ownership_Join::join() [each ready batch]
 * The coordinator owns readiness only; workers own all resource/lifecycle analysis.
 */
namespace analyze_lifetimes;

final class Ownership_Preparation
{
    /** Execute ready batches through the same fixed-input worker/join protocol future workers use. */
    public static function run(\check_bodies\Body_Set $bodies, ?\type_model\Type_Store $types, array $previous, bool $full): array
    {
        // Reverse dependency edges wake only subjects whose prerequisites have been accepted.
        [$subjects, $dependencies] = self::requests($bodies, $types);
        $remaining = [];
        $users = [];
        $ready = [];
        foreach ($subjects as $key => $_)
        {
            $remaining[$key] = count($dependencies[$key]);
            if ($remaining[$key] === 0) {
                $ready[] = $key;
            }
            foreach ($dependencies[$key] as $dependency) {
                $users[$dependency][] = $key;
            }
        }
        $accepted = [];
        while ($ready !== [])
        {
            // Select against a fixed accepted dependency set before any worker in this batch runs.
            $batch = $ready;
            $ready = [];
            $tasks = [];
            foreach ($batch as $key)
            {
                $inputs = [];
                foreach ($dependencies[$key] as $dependency) {
                    $inputs[$dependency] = $accepted[$dependency]->summary;
                }
                $old = $previous[$key] ?? null;
                $subject = $subjects[$key];
                // Lifecycle subjects are rebuilt descriptors; definitions and body identity are exact.
                $same = ($old !== null) && (($old->task->subject === $subject)
                    || (($subject instanceof ownership_lifecycle) && ($old->task->subject instanceof ownership_lifecycle)
                        && ($old->task->subject->definition === $subject->definition)
                        && ($old->task->subject->type_id === $subject->type_id)
                        && ($old->task->subject->kind === $subject->kind)
                        && ($old->task->subject->children === $subject->children)
                        && ($old->task->subject->body_id === $subject->body_id)));
                if ((!$full) && ($same) && ($old->task->dependencies === $inputs)) {
                    $accepted[$key] = $old;
                }
                else {
                    $tasks[] = new ownership_task($key, $subject, $inputs);
                }
            }

            // Only joined private results can satisfy prerequisites for the next batch.
            $outputs = array_map(Ownership_Worker::prepare(...), $tasks);
            $accepted += (new Ownership_Join($tasks, $previous))->join($outputs);
            foreach ($batch as $key) {
                foreach ($users[$key] ?? [] as $user) {
                    if (--$remaining[$user] === 0) {
                        $ready[] = $user;
                    }
                }
            }
        }

        // Unfinished subjects form unsupported dependency cycles, not an invitation to recurse into bodies.
        if (count($accepted) !== count($subjects)) {
            throw new \RuntimeException('Recursive ownership summary dependencies are unsupported: ' . implode(', ', array_keys(array_diff_key($subjects, $accepted))));
        }
        return $accepted;
    }

    /** Build only owning aggregate requests; raw local allocation analysis retains its direct body path. */
    private static function requests(\check_bodies\Body_Set $bodies, ?\type_model\Type_Store $types): array
    {
        $subjects = [];
        $dependencies = [];
        $members = [];
        foreach ($bodies->bodies() as $body) {
            if (($body->owner->owner_symbol_id !== 0) && ($body->entry_parameter_count() > 0)) {
                $members[$body->local_type_for(1)][$body->owner->symbol_id] = $body->callable_id;
            }
        }
        foreach ($types?->structures() ?? [] as $type => $definition)
        {
            if ($definition->resource_paths === []) {
                continue;
            }
            $children = [];
            for ($index = 0; $index < $definition->representation->payload->count; ++$index) {
                $child = $types->field_for($type, $index)->type_id;
                if ($types->definition_for_type($child)->resource_paths !== []) {
                    $children[$index] = $child;
                }
            }
            foreach (\type_model\lifecycle_operation_kind::cases() as $kind)
            {
                $life = $definition->lifetime;
                if (($kind === \type_model\lifecycle_operation_kind::copy_construct)
                    && ($life->copy === \type_model\copy_kind::unavailable)) {
                    continue;
                }
                if (($kind === \type_model\lifecycle_operation_kind::copy_assign)
                    && ($life->assignment === \type_model\assignment_kind::unavailable)) {
                    continue;
                }
                if (($kind === \type_model\lifecycle_operation_kind::move_construct)
                    && ($life->expiring !== \type_model\expiring_construction::construct)) {
                    continue;
                }
                $key = self::lifecycle_key($kind, $type);
                $operation = $life->operation($kind);
                $symbol = $operation instanceof \type_model\source_lifecycle_operation ? $operation->body_symbol_id : 0;
                $body = $symbol === 0 ? null : ($members[$type][$symbol] ?? throw new \LogicException('Missing checked source lifecycle body'));
                $order = $operation instanceof \type_model\source_lifecycle_operation ? $operation->order : $kind->composition(false);
                // Index actual roles once: automatic movement may legally copy individual fields.
                $member_roles = [];
                foreach ($operation instanceof \type_model\source_lifecycle_operation ? $operation->members : [] as $member) {
                    $member_roles[$member->index] = $member->kind;
                }
                $child_keys = [];
                foreach ($order->member_kind === null ? [] : $children as $index => $child) {
                    $child_keys[$index] = self::lifecycle_key($member_roles[$index] ?? $order->member_kind, $child);
                }
                if ($order->reverse_members) {
                    $child_keys = array_reverse($child_keys, true);
                }
                $subjects[$key] = new ownership_lifecycle($type, $definition, $kind, $child_keys, $body);
                $dependencies[$key] = array_values(array_unique([...($body === null ? [] : ['body:' . $body]), ...array_values($child_keys)]));
            }
        }

        foreach ($bodies->bodies() as $body)
        {
            $deps = self::body_dependencies($body, $bodies);
            $owns = false;
            foreach ($body->type_dependencies as $type) {
                $owns = ($owns) || ($type->definition->resource_paths !== []);
            }
            if (($owns) || ($deps !== [])) {
                $subjects['body:' . $body->callable_id] = $body;
                $dependencies['body:' . $body->callable_id] = $deps;
            }
        }
        foreach ($dependencies as $key => $deps) {
            foreach ($deps as $dependency) {
                if (!isset($subjects[$dependency])) {
                    throw new \LogicException('Missing ownership preparation input: ' . $key . ' -> ' . $dependency);
                }
            }
        }
        return [$subjects, $dependencies];
    }

    /** Depend on semantic calls and owned constructions, never implicitly on the receiver's own lifecycle. */
    private static function body_dependencies(\check_bodies\Checked_Body $body, \check_bodies\Body_Set $bodies): array
    {
        $deps = [];
        foreach ($body->calls as $call) {
            $callee = $bodies->for_callable($call->target_callable_id);
            if (($callee !== null) && ((Resource_Locations::parameters($callee) !== [])
                || ($callee->definition_for($callee->signature_for($callee->callable_id)->return_type)->resource_paths !== []))) {
                $deps['body:' . $callee->callable_id] = true;
            }
        }
        foreach ($body->values as $value)
        {
            if (($value->kind === \check_bodies\value_kind::call_result)
                && ($body->definition_for($value->type_id)->resource_paths !== [])) {
                $deps['destroy:' . $value->type_id] = true;
            }
            if (in_array($value->kind, [\check_bodies\value_kind::default_construct, \check_bodies\value_kind::record_default], true)
                && ($body->definition_for($value->type_id)->resource_paths !== [])) {
                $deps['construct:' . $value->type_id] = true;
                $deps['destroy:' . $value->type_id] = true;
            }
        }
        foreach ($body->statements as $statement)
        {
            if (in_array($statement->return, [\check_bodies\return_kind::copy_construct, \check_bodies\return_kind::move_construct], true)
                && ($body->definition_for($body->values[$statement->value_id - 1]->type_id)->resource_paths !== [])) {
                $prefix = $statement->return === \check_bodies\return_kind::copy_construct ? 'copy:' : 'move:';
                $deps[$prefix . $body->values[$statement->value_id - 1]->type_id] = true;
            }
            if (in_array($statement->write, [\check_bodies\local_write_kind::copy_construct, \check_bodies\local_write_kind::copy_assign], true)
                && ($body->definition_for($body->values[$statement->value_id - 1]->type_id)->resource_paths !== [])) {
                $type = $body->values[$statement->value_id - 1]->type_id;
                $deps[($statement->write === \check_bodies\local_write_kind::copy_assign ? 'assign:' : 'copy:') . $type] = true;
                $deps['destroy:' . $type] = true;
            }
        }
        return array_keys($deps);
    }

    /** Exact work identities are shared by selection and lifecycle consumers. */
    public static function lifecycle_key(\type_model\lifecycle_operation_kind $kind, int $type): string
    {
        return match ($kind) {
            \type_model\lifecycle_operation_kind::default_construct => 'construct:',
            \type_model\lifecycle_operation_kind::copy_construct => 'copy:',
            \type_model\lifecycle_operation_kind::move_construct => 'move:',
            \type_model\lifecycle_operation_kind::copy_assign => 'assign:',
            \type_model\lifecycle_operation_kind::destroy => 'destroy:',
        } . $type;
    }
}
