<?php
declare(strict_types=1);

/*
 * Role: Prepare explicitly demanded source arguments at a ready concrete frontier.
 * Call map: Concrete_Preparation -> Source_Export_Coordinator::tasks()
 *   -> Source_Identities; Layout_Coordinator::prepare(); Source_Export_Preparation; Source_Export_Join
 * Output: fixed family tasks sharing accepted exports; no source behavior is delegated to native preparation.
 */
namespace prepare_backend;

final class Source_Export_Coordinator
{
    private array $accepted = [];

    public function __construct(private readonly ?\compile\native_project $project,
        private readonly Layout_Coordinator $layouts, private readonly backend_configuration $configuration,
        private readonly \type_model\Type_Catalog $language, private readonly ?\load_runtime\Runtime_Input_Set $runtime,
        private readonly bool $full, private readonly array $previous = [])
    {
    }

    /** Retain accepted source exports independently of which native specialization demanded them. */
    public static function retained(array $families): array
    {
        $exports = [];
        foreach ($families as $family) {
            foreach ($family->package->project?->exports ?? [] as $export) {
                $exports[$export->task->layout->dependency->type_id] = $export;
            }
        }
        return $exports;
    }

    /** Capture source record arguments only after canonical definitions and physical dependencies are ready. */
    public function tasks(array $tasks, \type_model\Type_Store $types, \collect_symbols\Symbol_Store $symbols,
        \instantiate\Instance_View $instances): array
    {
        $roots = [];
        foreach ($tasks as $task) {
            foreach ($task->context->arguments as $argument) {
                if (($argument->value === null) && ($argument->type->representation->kind === \type_model\representation_kind::structure)) {
                    $roots[$types->find_type($argument->type->name, $argument->type->namespace_name)] = true;
                }
            }
        }
        if ($roots === []) {
            return $tasks;
        }
        if ($this->project === null) {
            throw new \RuntimeException('Source family arguments require explicit native project configuration');
        }
        $ids = array_keys($roots);
        $identities = new \resolve_types\Source_Identities($this->project, $types, $symbols, $instances, $this->language, $this->runtime);
        $current = Source_Export_Preparation::capture($this->project, $identities, $types, $ids,
            $this->layouts->prepare($types, $ids), $this->configuration);
        // Full rebuild selects all first demands; later frontiers share exports already accepted this update.
        $previous = array_replace($this->full ? [] : $this->previous, $this->accepted);
        $selected = Source_Export_Preparation::select(array_diff_key($current, $this->accepted), $previous, $this->full);
        $accepted = (new Source_Export_Join($current, $previous, $selected))->join(array_map(Source_Export_Preparation::prepare(...), $selected));
        $this->accepted = array_replace($this->accepted, $accepted);
        $output = [];
        foreach ($tasks as $task)
        {
            $sources = [];
            foreach ($task->context->arguments as $position => $argument) {
                $id = $types->find_type($argument->type->name, $argument->type->namespace_name);
                if (isset($accepted[$id])) {
                    $sources[$position] = $accepted[$id];
                }
            }
            $output[] = new \load_runtime\family_preparation_task($task->context, $task->operations, $sources);
        }
        return $output;
    }
}
