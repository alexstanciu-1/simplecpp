<?php
declare(strict_types=1);
namespace instantiate;
/** Complete fixed-batch acceptance into the coordinator's private preparation candidates. */
final class Instance_Join {
    private array $tasks /** vector<Application_Task> */ = [];
    public function __construct(private readonly Instance_Store $input,
        array $tasks /** vector<Application_Task> */, private readonly \type_model\Type_Store $types,
        private readonly \resolve_symbols\Resolution_Set $names,
        private readonly \resolve_types\Definition_View $definitions, private readonly \type_model\Type_Catalog $catalog,
        private readonly ?Instance_Set $previous = null) {
        foreach ($tasks as $task) { $this->tasks[] = $task; }
    }
    /** Validate all results before allocation. Discard private candidates if adoption itself fails. */
    public function join(array $results /** vector<Application_Result> */): Instance_Store {
        if ($this->input->lineage() !== $this->types->lineage) { throw new \LogicException('Instance join requires its fixed type lineage'); }
        $reader = new Bindings(new \resolve_types\Annotation_Types($this->names,$this->definitions),$this->catalog,$this->input->view());
        $selected /** hash<Application_Task> */ = [];
        foreach ($this->tasks as $task) {
            $this->require_task($task,$reader);
            $key = Instance_Keys::application($task->context,$task->application->use_node_id);
            if (isset($selected[$key])) { throw new \LogicException('Duplicate application task'); }
            $selected[$key] = $task;
        }
        $accepted /** hash<Application_Result> */ = [];
        foreach ($results as $result) {
            $task = $result->task;
            $key = Instance_Keys::application($task->context,$task->application->use_node_id);
            if (!isset($selected[$key])) { throw new \LogicException('Unexpected application result'); }
            if ($selected[$key] !== $task) { throw new \LogicException('Unexpected application result task'); }
            if (isset($accepted[$key])) { throw new \LogicException('Duplicate application result'); }
            $matches = false;
            if ($result->ready()) { $matches = $this->matches($result,$reader); }
            else { $matches = $this->pending_matches($result,$reader); }
            if (!$matches) { throw new \LogicException('Stale application argument result'); }
            $accepted[$key] = $result;
        }
        if (q_count($accepted) !== q_count($selected)) { throw new \LogicException('Incomplete application preparation'); }
        foreach ($this->tasks as $task) {
            $key = Instance_Keys::application($task->context,$task->application->use_node_id);
            $result = $accepted[$key];
            if (!$result->ready()) { continue; }
            $arguments = $result->arguments();
            $id = $this->input->allocate($this->types,$task->application->definition->symbol_id,$arguments);
            $context = new Instance_Context($task->application->definition,$id,$arguments);
            $old = $this->input->context_for($context->context_id);
            if ($old === null) {
                if ($this->previous !== null) {
                    $retained = $this->previous->context_for($context->context_id);
                    if ($retained !== null) {
                        if ($retained->definition === $context->definition) {
                            if (Instance_Join::context_arguments_match($retained,$arguments)) { $old = $retained; }
                        }
                    }
                }
            }
            if ($old !== null) {
                if ($old->definition !== $context->definition) { throw new \LogicException('Conflicting current instance definition'); }
                $context = $old;
            }
            $this->input->accept($context);
            if (!$context->definition->is_source()) {
                $provider = $context->definition->provider();
                if ($provider->kind() === \collect_symbols\PROVIDER_STORAGE_FAMILY) {
                    $type = \resolve_types\Storage_Definitions::materialize($provider->storage_family(),$arguments[0]->type,$this->types);
                    $this->input->accept_type($context,$type);
                } else if ($provider->kind() === \collect_symbols\PROVIDER_STORAGE_FUNCTION) {
                    \resolve_types\Storage_Definitions::materialize($provider->storage_function()->family,$arguments[0]->type,$this->types);
                }
            }
            $this->input->bind($task->context,$task->application->use_node_id,$context);
        }
        return $this->input;
    }
    private function require_task(Application_Task $task, Bindings $reader): void {
        $owner = $task->context->definition;
        $bindings = $reader->annotations->bindings($owner);
        $target = $task->application->definition;
        $this->input->template_checks()->require_definition($target,$this->names,$this->catalog);
        $found = false;
        for ($i = 0; $i < $bindings->applications_count(); $i++) {
            if ($bindings->applications_at($i) === $task->application) { $found = true; break; }
        }
        if (!$found) { throw new \LogicException('Stale application task'); }
        if (!$this->names->has_declaration($target->symbol_id)) { throw new \LogicException('Stale application declaration'); }
        if ($this->names->declaration_for($target->symbol_id) !== $target) { throw new \LogicException('Stale application declaration'); }
        if ($task->context->instance_id !== 0) {
            if ($this->input->context_for($task->context->context_id) !== $task->context) { throw new \LogicException('Stale application context'); }
        }
    }
    /** A source result may report any nonempty subset of its unresolved type prerequisites. */
    private function pending_matches(Application_Result $result, Bindings $reader): bool {
        $task = $result->task; $owner = $task->context->definition; $tree = $owner->source_frontend()->tree;
        $node = (int)\parse\Syntax_Access::template_application_parts($tree,$task->application->use_node_id)->first_argument_id;
        $target = $task->application->definition;
        if (!$target->is_source()) {
            if ($target->provider()->kind() === \collect_symbols\PROVIDER_FAMILY) {
                $expected = Application_Worker::family_arguments($task,$reader);
                if ($expected->ready()) { return false; }
                if ($expected->prerequisite_count() !== $result->prerequisite_count()) { return false; }
                for ($i = 0; $i < $expected->prerequisite_count(); $i++) {
                    if ($expected->prerequisite_at($i) !== $result->prerequisite_at($i)) { return false; }
                }
                return true;
            }
            if ($result->prerequisite_count() !== 1) { return false; }
            if ($result->prerequisite_at(0) !== $node) { return false; }
            return $reader->type($task->context,$node) === null;
        }
        $missing /** hash<bool,int> */ = []; $bindings = $reader->annotations->bindings($target);
        for ($i = 0; $i < $bindings->parameters_count(); $i++) {
            if ($node === 0) { return false; }
            if ((int)$bindings->parameters_at($i)->type_syntax_id === 0) {
                if ($reader->type($task->context,$node) === null) { $missing[$node] = true; }
            }
            $node = (int)$tree->row($node)->next_sibling;
        }
        for ($i = 0; $i < $result->prerequisite_count(); $i++) {
            $required = $result->prerequisite_at($i);
            if (!isset($missing[$required])) { return false; }
        }
        return true;
    }
    private function matches(Application_Result $result, Bindings $reader): bool {
        $task = $result->task; $target = $task->application->definition;
        if (!$target->is_source()) {
            $expected = Application_Worker::run($task,$reader);
            if (!$expected->ready()) { return false; }
            return Instance_Join::same_arguments($expected->arguments(),$result->arguments());
        }
        $bindings = $reader->annotations->bindings($target);
        if ($bindings->parameters_count() !== $result->argument_count()) { return false; }
        $tree = $task->context->definition->source_frontend()->tree;
        $node = (int)\parse\Syntax_Access::template_application_parts($tree,$task->application->use_node_id)->first_argument_id;
        for ($i = 0; $i < $bindings->parameters_count(); $i++) {
            if ($node === 0) { return false; }
            $parameter = $bindings->parameters_at($i); $argument = $result->argument_at($i);
            if ((int)$parameter->type_syntax_id === 0) {
                $type = $reader->type($task->context,$node);
                if ($type === null) { return false; }
                if (\type_model\Generic_Contracts::missing($type,(int)$parameter->contract) !== null) { return false; }
                if (($argument->type !== $type) || ($argument->value !== null)) { return false; }
            } else {
                $expected = $reader->value($task->context,$node);
                if (($argument->type !== $expected->type) || ($argument->value !== $expected->value)) { return false; }
            }
            $node = (int)$tree->row($node)->next_sibling;
        }
        return $node === 0;
    }
    private static function same_arguments(array $left /** vector<Template_Argument> */, array $right /** vector<Template_Argument> */): bool {
        if (q_count($left) !== q_count($right)) { return false; }
        foreach ($left as $i => $argument) {
            if (($argument->type !== $right[$i]->type) || ($argument->value !== $right[$i]->value)) { return false; }
        }
        return true;
    }
    private static function context_arguments_match(Instance_Context $context, array $arguments /** vector<Template_Argument> */): bool {
        if ($context->argument_count() !== q_count($arguments)) { return false; }
        foreach ($arguments as $i => $argument) {
            $retained = $context->argument_at($i);
            if (($argument->type !== $retained->type) || ($argument->value !== $retained->value)) { return false; }
        }
        return true;
    }
}
