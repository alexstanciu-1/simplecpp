<?php
declare(strict_types=1);

/*
 * Role: Accept selected member requests into the private concrete registry.
 * Used by: Concrete_Preparation::prepare_members()
 * Call map: Member_Join::join() -> Member_Worker receiver/method validation; Instance_Store::allocate()/accept()/bind()
 * Inputs stay fixed; complete validation precedes candidate identity allocation.
 */
namespace instantiate;

final class Member_Join implements \compile\Join
{
    /**
     * Capture the fixed selected batch; all accepted contexts are assembled privately.
     * @param list<member_task> $tasks
     */
    public function __construct(private readonly Instance_Store $input, private readonly array $tasks,
        private readonly \type_model\Type_Store $types, private readonly \resolve_symbols\Resolution_Set $names,
        private readonly \resolve_types\Definition_View $definitions, private readonly \collect_symbols\Symbol_Store $symbols,
        private readonly ?Instance_Set $previous)
    {
    }

    /**
     * Accept complete outputs in any order, validating receiver provenance before allocating identities.
     * @param list<member_result> $results
     */
    public function join(array $results): Instance_Store
    {
        if ($this->input->lineage !== $this->types->lineage) {
            throw new \LogicException('Member join requires its fixed type lineage');
        }
        $selected = [];
        foreach ($this->tasks as $task)
        {
            $id = $task->key();
            $owner = $task->context->definition;
            if (isset($selected[$id]) || ($this->symbols->symbol_by_id($owner->symbol_id) !== $owner)
                || !(($task->use !== null)
                    ? in_array($task->use, $this->names->for_symbol($owner->symbol_id)->members, true)
                    : (($task->declaration->owner_symbol_id === $owner->symbol_id) && in_array($owner->kind, [\collect_symbols\symbol_kind::struct_symbol, \collect_symbols\symbol_kind::template_struct], true)
                        && ($task->declaration->kind === ($owner->is_template() ? \collect_symbols\symbol_kind::template_function : \collect_symbols\symbol_kind::function_symbol))
                        && ($this->symbols->symbol_by_id($task->declaration->symbol_id) === $task->declaration)))
                || (($task->context->instance_id !== 0) && ($this->input->context_for($task->context->context_id) !== $task->context))) {
                throw new \LogicException('Duplicate or stale member task');
            }
            $selected[$id] = $task;
        }
        $accepted = [];
        foreach ($results as $result)
        {
            $task = $result->task;
            $id = $task->key();
            if ((($selected[$id] ?? null) !== $task) || isset($accepted[$id])
                || (Member_Worker::receiver($task, $this->names, $this->definitions, $this->input) !== $result->receiver)) {
                throw new \LogicException('Unexpected, duplicate or stale member result');
            }
            if ($result->receiver !== null)
            {
                $context = $this->input->type_context($result->receiver);
                $owner = $context?->definition ?? Member_Worker::owner($result->receiver, $this->symbols);
                if (($owner === null) || (($task->declaration ?? Member_Worker::method($task, $owner, $this->symbols)) !== $result->definition)
                    || ($result->definition === null) || ($result->arguments !== ($context?->arguments ?? []))) {
                    throw new \LogicException('Member result changed its owning record or arguments');
                }
            }
            elseif (($result->definition !== null) || ($result->arguments !== [])) {
                throw new \LogicException('Pending member result contains a concrete target');
            }
            $accepted[$id] = $result;
        }
        if (count($selected) !== count($accepted)) {
            throw new \LogicException('Incomplete member preparation');
        }
        foreach ($selected as $id => $task)
        {
            $result = $accepted[$id];
            if ($result->receiver === null) {
                continue;
            }
            $instance = $this->input->allocate($this->types, $result->definition->symbol_id, $result->arguments);
            $context = new instance_context($result->definition, $instance, $result->arguments, $result->receiver);
            $old = $this->input->context_for($context->context_id) ?? $this->previous?->contexts[$context->context_id] ?? null;
            if (($old?->definition === $context->definition) && ($old->receiver_type === $context->receiver_type)
                && ($old->arguments === $context->arguments)) {
                $context = $old;
            }
            $this->input->accept($context);
            if ($task->use !== null) {
                $this->input->bind($task->context, $task->use->use_node_id, $context);
            }
        }
        return $this->input;
    }
}
