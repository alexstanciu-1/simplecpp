<?php
declare(strict_types=1);

/*
 * Role: Coordinate concrete dependencies before signature and body consumers.
 * Used by: Type_Resolver::run()
 * Call map: init() -> Template_Checker lifecycle; [action] select literal and declaration roots
 *   run() -> admit_context(); prepare_applications(); prepare_family_types(); prepare_records(); prepare_members() [ready batches]
 *   prepare_family_types() -> Source_Export_Coordinator::tasks(); Family_Preparation::prepare_types(); record_ready()
 *   prepare_records() -> Record_Join::join(); Layout_Coordinator::prepare() [ready physical subset]
 * Workers read fixed registry/type views; joins alone accept private results.
 * Output: one immutable Instance_Set, with original declarations and ASTs retained.
 */
namespace resolve_types;

use instantiate\Application_Worker;
use instantiate\Bindings;
use instantiate\Constant_Join;
use instantiate\Constant_Worker;
use instantiate\Instance_Join;
use instantiate\Instance_Set;
use instantiate\Instance_Store;
use instantiate\Instantiation_Policy;
use instantiate\Member_Join;
use instantiate\Member_Worker;
use instantiate\application_task;
use instantiate\instance_context;
use instantiate\member_task;
use collect_symbols\symbol_kind;

final class Concrete_Preparation implements \compile\Step, \compile\Runnable_Step
{
    private \compile\step_status $state = \compile\step_status::created;
    private Instance_Store $candidate;
    private Instance_Set $output;
    private Preparation_Queue $queue;
    private array $roots = [];
    private array $ordinary_members = [];
    private array $ordinary_contexts = [];
    private array $constant_tasks = [];
    private array $constants = [];
    private array $constant_owners = [];
    /** @var array<int, \resolve_symbols\Symbol_Resolution> Current context provenance, also the admitted-context set. */
    private array $bindings = [];
    /** @var array<int, list<string>> Occurrences waiting for an accepted specialization to have storage. */
    private array $record_uses = [];
    private array $family_tasks = [];
    private int $limit;
    private bool $reuse = false;
    private \check_templates\Template_Set $template_checks;
    private array $counts = ['contexts' => 0, 'applications' => 0, 'records' => 0, 'members' => 0, 'reused' => 0];

    /** Capture fixed inputs; all candidate allocation and selection starts in init().
     * @param list<record_task> $ordinary_records */
    public function __construct(private readonly \collect_symbols\Symbol_Store $symbols,
        private readonly \resolve_symbols\Resolution_Set $names, private readonly \type_model\Type_Catalog $catalog,
        private readonly \type_model\Type_Store $types, private readonly ?Instance_Set $previous,
        private readonly bool $full_rebuild, private readonly ?int $instance_limit = null,
        private readonly array $ordinary_records = [],
        private readonly ?\load_runtime\Family_Preparation $families = null,
        private readonly ?\prepare_backend\Layout_Coordinator $layouts = null,
        private readonly ?\prepare_backend\Source_Export_Coordinator $exports = null)
    {
    }

    /** Accept definition permissions, then select literal work and concrete roots. */
    public function init(): void
    {
        $this->require_status(\compile\step_status::created, __FUNCTION__);
        try
        {
            // No concrete demand may bypass checking an unused generic definition.
            $checker = new \check_templates\Template_Checker($this->symbols, $this->names, $this->catalog,
                $this->previous?->template_checks() ?? new \check_templates\Template_Set(), $this->full_rebuild);
            $checker->init();
            $checker->run();
            $checker->finalize();
            $this->template_checks = $checker->result();

            if ((!$this->full_rebuild) && ($this->previous !== null) && ($this->previous->lineage !== $this->types->lineage)) {
                throw new \LogicException('Concrete preparation requires the retained type lineage or full rebuild');
            }
            $this->queue = new Preparation_Queue();
            $this->limit = $this->instance_limit ?? Instantiation_Policy::load();
            if (($this->limit <= 0) || ($this->limit > \collect_symbols\MAX_SYMBOL_ID)) {
                throw new \LogicException('Invalid fixed instantiation limit');
            }
            foreach ($this->symbols->records() as $symbol)
            {
                if ($symbol->kind === symbol_kind::constant_symbol)
                {
                    $this->constant_owners[$symbol->symbol_id] = $symbol;
                    if (($this->full_rebuild) || (($this->previous?->constant_owners[$symbol->symbol_id] ?? null) !== $symbol)) {
                        $this->constant_tasks[$symbol->symbol_id] = $symbol;
                    }
                    else {
                        $this->constants[$symbol->symbol_id] = $this->previous->constants[$symbol->symbol_id];
                    }
                }
                elseif (($symbol->frontend !== null) && !$symbol->is_template())
                {
                    if ($symbol->owner_symbol_id === 0) {
                        $this->roots[] = new instance_context($symbol);
                    }
                    else {
                        $this->ordinary_members[] = $symbol;
                    }
                }
            }
            $this->state = \compile\step_status::ready;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Prepare literals, then consume only ready batches; joins publish facts before dependent selection. */
    public function run(): void
    {
        $this->require_status(\compile\step_status::ready, __FUNCTION__);
        $this->state = \compile\step_status::running;
        try
        {
            $results = [];
            foreach ($this->constant_tasks as $id => $task) {
                $results[$id] = Constant_Worker::resolve($task, $this->names, $this->catalog);
            }
            $this->constants += (new Constant_Join($this->constant_tasks, $this->catalog, $this->names))->join($results);
            $this->reuse = (!$this->full_rebuild) && ($this->previous !== null) && ($this->constants === $this->previous->constants);
            $this->candidate = new Instance_Store(new Instance_Set(constants: $this->constants,
                keys: $this->full_rebuild ? [] : ($this->previous?->keys ?? []), next_id: $this->previous?->next_id ?? 1,
                constant_owners: $this->constant_owners, lineage: $this->types->lineage, templates: $this->template_checks));

            // Build one reuse index; dependent discovery never rescans completed contexts.
            foreach ($this->reuse ? $this->previous->contexts : [] as $context) {
                if (!$context->definition->is_template() && ($context->definition->owner_symbol_id !== 0)) {
                    $this->ordinary_contexts[$context->definition->symbol_id] = $context;
                }
            }
            foreach ($this->roots as $context) {
                $this->admit_context($context);
            }
            foreach ($this->ordinary_records as $task) {
                $this->schedule_record(new record_task($task->input, $task->catalog, $task->names, instances: $this->candidate, symbols: $this->symbols));
            }
            foreach ($this->ordinary_members as $method) {
                $owner = $this->symbols->symbol_by_id($method->owner_symbol_id);
                $task = new member_task(new instance_context($owner), declaration: $method);
                $known = $this->types->find_type($owner->name, $owner->namespace_name);
                $this->queue->add(new preparation_request('m:' . $task->key(), preparation_kind::member, $task),
                    $known === 0 ? [self::record_fact($owner)] : []);
            }

            // Each worker batch reads fixed state; only completed joins unlock the next work.
            while ($this->queue->ready())
            {
                $this->prepare_applications();
                $this->admit_introduced();
                $this->prepare_family_types();
                $this->prepare_records();
                $this->prepare_members();
                $this->admit_introduced();
            }
            if (($request = $this->queue->pending()) !== null) {
                $task = $request->task;
                $context = $task instanceof record_task ? $task->instance : $task->context;
                $owner = $context?->definition ?? $task->input;
                Bindings::fail($owner, $owner->declaration_node_id ?: $owner->body_node_id, 'Unresolved concrete preparation prerequisite (including a possible by-value record cycle)');
            }
            $this->output = $this->candidate->snapshot($this->bindings);
            $this->state = \compile\step_status::processed;
        }
        catch (\Throwable $error) {
            $this->state = \compile\step_status::failed;
            throw $error;
        }
    }

    /** Register each context once; exact occurrence dependencies remain scoped by its concrete ID. */
    private function admit_context(instance_context $context): void
    {
        if ($context->definition->external instanceof \type_model\family_declaration) {
            $this->family_tasks[] = new \load_runtime\family_preparation_task($context);
            return;
        }
        if ($context->definition->external instanceof \type_model\family_method) {
            return;
        }
        if ($context->definition->external !== null)
        {
            $external = $context->definition->external;
            $family = $external instanceof \type_model\storage_family ? $external : $external->family;
            $id = $this->types->find_type(Storage_Definitions::name($family, $context->arguments[0]->type), "\0element_storage");
            $type = $this->types->definition_for_type($id);
            if ($external instanceof \type_model\storage_family) {
                $this->record_ready($context, $type);
            }
            return;
        }
        if (isset($this->bindings[$context->context_id])) {
            return;
        }
        ++$this->counts['contexts'];
        $bindings = $this->names->for_symbol($context->definition->symbol_id);
        $this->bindings[$context->context_id] = $bindings;
        foreach ($bindings->applications as $application) {
            $task = new application_task($context, $application);
            $this->queue->add(new preparation_request('a:' . self::type_fact($context, $application->use_node_id), preparation_kind::application, $task));
        }
        foreach ($bindings->members as $use) {
            $task = new member_task($context, $use);
            $this->queue->add(new preparation_request('m:' . $task->key(), preparation_kind::member, $task));
        }
        if ($context->definition->kind === symbol_kind::template_struct) {
            $this->schedule_record(new record_task($context->definition, $this->catalog, $this->names, $context, $this->candidate, symbols: $this->symbols));
        }
    }

    /** Newly accepted contexts become work; no completed registry membership is rescanned. */
    private function admit_introduced(): void
    {
        foreach ($this->candidate->take_introduced() as $context) {
            if ($this->candidate->count() > $this->limit) {
                Bindings::fail($context->definition, $context->definition->declaration_node_id, 'Template instance limit exceeded');
            }
            $this->admit_context($context);
        }
    }

    /** Ordinary and specialized records share one task queue and normalized join. */
    private function schedule_record(record_task $task): void
    {
        $context = $task->instance;
        if ($context !== null)
        {
            $known = $this->types->find_type($context->type_name(), $context->type_namespace());
            if ($known !== 0) {
                $this->record_ready($context, $this->types->definition_for_type($known));
                return;
            }
        }
        $key = $task->input instanceof \type_model\record_declaration
            ? 'provider:' . json_encode([$task->input->namespace_name, $task->input->name], JSON_THROW_ON_ERROR)
            : 'source:' . ($context?->context_id ?? $task->input->symbol_id);
        $prerequisites = [];
        if ($task->input instanceof \collect_symbols\symbol_record)
        {
            $context ??= new instance_context($task->input);
            $view = new Definition_View($this->catalog, $this->types);
            $member_cursor = \parse\Syntax_Access::struct_members($task->input->frontend->syntax, $task->input->declaration_node_id,
                \parse\syntax_kind::field_declaration);
            while ($member_cursor->advance())
            {
                $field = $member_cursor->current();
                $node = \parse\Syntax_Access::field_declaration_parts($task->input->frontend->syntax, $field)->type_syntax_id;
                if (Bindings::type($context, $node, $this->names, $view, $this->candidate) === null) {
                    $prerequisites[] = $this->prerequisite_fact($context, $node);
                }
            }
            foreach ($this->names->for_symbol($task->input->symbol_id)->applications as $application) {
                $prerequisites[] = self::type_fact($context, $application->use_node_id);
            }
        }
        $this->queue->add(new preparation_request('r:' . $key, preparation_kind::record, $task), $prerequisites);
    }

    /** Reuse valid occurrences before selecting argument work; accept one fixed application batch. */
    private function prepare_applications(): void
    {
        $batch = $this->queue->take_ready(preparation_kind::application);
        $requests = [];
        foreach ($batch as $request)
        {
            $task = $request->task;
            $old = $this->retained_target($task->context, $task->application->use_node_id);
            if (($old !== null) && ($old->definition === $task->application->definition)) {
                $this->candidate->accept($this->candidate->context_for($old->context_id) ?? $old);
                $this->candidate->bind($task->context, $task->application->use_node_id, $old);
                $this->application_ready($task);
                $this->queue->complete($request);
                ++$this->counts['reused'];
            }
            else {
                $requests[] = $request;
            }
        }
        if ($requests === []) {
            return;
        }
        // Reused targets are already accepted; compute only the remaining fixed batch.
        $tasks = array_map(static fn($request) => $request->task, $requests);
        $view = new Definition_View($this->catalog, $this->types);
        $results = [];
        foreach ($tasks as $task) {
            $results[] = Application_Worker::run($task, $this->names, $view, $this->catalog, $this->candidate);
        }
        $this->counts['applications'] += count($tasks);
        (new Instance_Join($this->candidate, $tasks, $this->types, $this->names, $view, $this->catalog,
            $this->full_rebuild ? null : $this->previous))->join($results);
        foreach ($requests as $index => $request)
        {
            $result = $results[$index];
            if ($result->arguments === null) {
                $facts = array_map(fn($node) => $this->prerequisite_fact($request->task->context, $node), $result->prerequisites);
                $this->queue->wait_for($request, $facts);
            }
            else {
                $this->application_ready($request->task);
                $this->queue->complete($request);
            }
        }
    }

    /** An accepted type application becomes ready only after its concrete storage is materialized. */
    private function application_ready(application_task $task): void
    {
        $target = $this->candidate->application($task->context, $task->application->use_node_id);
        if ($target->definition->kind !== symbol_kind::template_struct) {
            return;
        }
        $fact = self::type_fact($task->context, $task->application->use_node_id);
        if ($this->candidate->instance_type($target->instance_id) !== null) {
            $this->queue->publish($fact);
        }
        else {
            $this->record_uses[$target->instance_id][] = $fact;
        }
    }

    /** Prepare the fixed native frontier after semantic argument acceptance; no worker runs native tools. */
    private function prepare_family_types(): void
    {
        $tasks = $this->family_tasks;
        $this->family_tasks = [];
        if ($tasks === []) {
            return;
        }
        $tasks = $this->exports?->tasks($tasks, $this->types, $this->symbols, $this->candidate) ?? $tasks;
        $preparation = $this->families ?? new \load_runtime\Family_Preparation(null, $this->catalog);
        foreach ($preparation->prepare_types($tasks) as $result)
        {
            $context = $result->task->context;
            $type = $result->package->type_for($result->type_id)->language_type;
            Type_Cache::materialize($this->types, $type);
            $this->record_ready($context, $type);
        }
    }

    /** Fix and run the ready structural batch, then publish accepted record facts. */
    private function prepare_records(): void
    {
        $requests = $this->queue->take_ready(preparation_kind::record);
        if ($requests === []) {
            return;
        }
        // Freeze the now-ready dependencies before workers run; joins mutate a separate candidate.
        $view = new Definition_View($this->catalog, clone $this->types);
        $instances = $this->candidate->snapshot();
        $tasks = array_map(fn($request) => new record_task($request->task->input, $request->task->catalog,
            $request->task->names, $request->task->instance, $instances, $view, $this->symbols), $requests);
        $results = array_map(Record_Preparation::resolve(...), $tasks);
        $this->counts['records'] += count($tasks);
        (new Record_Join($this->types, $tasks, $this->symbols))->join($results);

        // Physical workers capture only this ready batch and its accepted storage dependencies.
        // Their join completes before the queue exposes records to the next dependency frontier.
        $ids = array_map(fn($result) => $this->types->find_type($result->declaration->name,
            $result->declaration->namespace_name), $results);
        $this->layouts?->prepare($this->types, $ids);
        foreach ($requests as $request)
        {
            $context = $request->task->instance;
            if ($context !== null) {
                $id = $this->types->find_type($context->type_name(), $context->type_namespace());
                $this->record_ready($context, $this->types->definition_for_type($id));
            }
            else {
                $this->queue->publish(self::record_fact($request->task->input));
            }
            $this->queue->complete($request);
        }
    }

    /** Update the registry index once and awaken only occurrences waiting for this record. */
    private function record_ready(instance_context $context, \type_model\named_type_definition $definition): void
    {
        $this->candidate->accept_type($context, $definition);

        // Lifecycle bodies are implicit member demands, even without an explicit method call.
        foreach ([$definition->lifetime->default_constructor, $definition->lifetime->destructor, $definition->lifetime->copy_constructor, $definition->lifetime->copy_assignment, $definition->lifetime->move_constructor] as $operation)
        {
            if (($operation instanceof \type_model\source_lifecycle_operation) && ($operation->body_symbol_id !== 0)) {
                $task = new member_task($context, declaration: $this->symbols->symbol_by_id($operation->body_symbol_id));
                $this->queue->add(new preparation_request('m:' . $task->key(), preparation_kind::member, $task));
            }
        }
        foreach ($this->record_uses[$context->instance_id] ?? [] as $fact) {
            $this->queue->publish($fact);
        }
        unset($this->record_uses[$context->instance_id]);
    }

    /** Prepare ordinary declarations and demanded template members through the same method contract. */
    private function prepare_members(): void
    {
        $batch = $this->queue->take_ready(preparation_kind::member);
        $view = new Definition_View($this->catalog, $this->types);
        $requests = [];
        foreach ($batch as $request)
        {
            $task = $request->task;
            $old = $task->declaration !== null
                ? ($this->ordinary_contexts[$task->declaration->symbol_id] ?? null)
                : $this->retained_target($task->context, $task->use->use_node_id);
            if (($old !== null) && ($this->symbols->symbol_by_id($old->definition->symbol_id) === $old->definition)
                && (Member_Worker::receiver($task, $this->names, $view, $this->candidate) === $old->receiver_type))
            {
                $this->candidate->accept($this->candidate->context_for($old->context_id) ?? $old);
                if ($task->use !== null) {
                    $this->candidate->bind($task->context, $task->use->use_node_id, $old);
                }
                $this->queue->complete($request);
                ++$this->counts['reused'];
            }
            else {
                $requests[] = $request;
            }
        }
        if ($requests === []) {
            return;
        }
        $tasks = array_map(static fn($request) => $request->task, $requests);
        $results = [];
        foreach ($tasks as $task) {
            $results[] = Member_Worker::run($task, $this->names, $view, $this->candidate, $this->symbols);
        }
        $this->counts['members'] += count($tasks);
        (new Member_Join($this->candidate, $tasks, $this->types, $this->names, $view, $this->symbols,
            $this->full_rebuild ? null : $this->previous))->join($results);
        foreach ($requests as $index => $request)
        {
            if ($results[$index]->receiver === null) {
                $task = $request->task;
                $node = Member_Worker::receiver_annotation($task, $this->names);
                $this->queue->wait_for($request, [$this->prerequisite_fact($task->context, $node)]);
            }
            else {
                $this->queue->complete($request);
            }
        }
    }

    /** Reuse requires current source bindings and the unchanged concrete argument environment. */
    private function retained_target(instance_context $context, int $node): ?instance_context
    {
        if ((!$this->reuse) || (($this->previous->source_bindings[$context->context_id] ?? null) !== $this->bindings[$context->context_id])) {
            return null;
        }
        return $this->previous->application($context, $node);
    }

    /** Distinguish occurrence-scoped specializations from an ordinary nominal record dependency. */
    private function prerequisite_fact(instance_context $context, int $node): string
    {
        if ($context->definition->frontend->syntax->nodes[$node - 1]->kind === \parse\syntax_kind::template_application) {
            return self::type_fact($context, $node);
        }
        $binding = $this->names->for_symbol($context->definition->symbol_id)->name_for($node);
        $target = $binding->kind === \resolve_symbols\reference_kind::source_type
            ? $this->names->declaration_for($binding->target) : $binding->target;
        return self::record_fact($target);
    }

    private static function record_fact(\collect_symbols\symbol_record|\type_model\record_declaration $record): string
    {
        return 'record:' . json_encode([$record->namespace_name, $record->name], JSON_THROW_ON_ERROR);
    }

    private static function type_fact(instance_context $context, int $node): string
    {
        return $context->context_id . ':' . $node;
    }

    /** Release temporary scheduling state; retained consumers receive only the completed snapshot. */
    public function finalize(): void
    {
        $this->require_status(\compile\step_status::processed, __FUNCTION__);
        $this->roots = $this->ordinary_members = $this->constant_tasks = $this->constants = $this->constant_owners = [];
        $this->bindings = $this->record_uses = $this->ordinary_contexts = [];
        unset($this->candidate, $this->queue);
        $this->state = \compile\step_status::finished;
    }

    public function result(): Instance_Set
    {
        $this->require_status(\compile\step_status::finished, __FUNCTION__);
        return $this->output;
    }

    /** Small diagnostic counters make selected work testable without wall-clock thresholds. */
    public function work_counts(): array
    {
        $this->require_status(\compile\step_status::finished, __FUNCTION__);
        return $this->counts;
    }

    public function status(): \compile\step_status
    {
        return $this->state;
    }

    public function supports_run(): bool
    {
        return true;
    }

    private function require_status(\compile\step_status $expected, string $operation): void
    {
        if ($this->state !== $expected) {
            throw new \LogicException('Concrete_Preparation::' . $operation . ' requires ' . $expected->name . '; current status is ' . $this->state->name);
        }
    }
}
