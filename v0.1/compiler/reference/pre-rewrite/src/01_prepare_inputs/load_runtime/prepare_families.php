<?php
declare(strict_types=1);

/*
 * Role: Coordinate native preparation batches separately from semantic workers and canonical stores.
 * Call map: Family_Preparation::prepare_types() -> Family_Preparer::prepare(); Family_Preparation_Join::join()
 *   prepare_methods() -> [action] select missing coverage with complete demands; prepare/join; bind callable IDs
 * Output: fixed current package associations; no reader leases or mutable native services in snapshots
 */
namespace load_runtime;

final class Family_Preparation
{
    private array $current = [];

    /** Capture fixed compiler and native package inputs; native description reads belong to the service.
     * @param array<int, family_preparation_result> $previous Retained associations from the accepted type phase.
     * @param array<string, Runtime_Package> $packages Accepted ordinary runtime owners. */
    public function __construct(private readonly ?Family_Preparer $provider, private readonly \type_model\Type_Catalog $catalog,
        private readonly array $previous = [], private readonly array $packages = [])
    {
    }

    /** Select each newly admitted semantic instance once, before invoking native preparation. */
    public function prepare_types(array $tasks): array
    {
        if ($tasks === []) {
            return [];
        }
        if ($this->provider === null) {
            $context = $tasks[0]->context;
            throw new \RuntimeException('Provider family specialization preparation is not implemented without a configured preparation bridge: '
                . $context->definition->name);
        }
        $results = $this->provider->prepare($tasks, $this->catalog, array_replace($this->previous, $this->current), $this->packages);
        $accepted = (new Family_Preparation_Join($tasks, $this->catalog))->join($results);
        foreach ($accepted as $id => $result) {
            if (isset($this->current[$id])) {
                throw new \LogicException('Repeated family type preparation');
            }
            $this->current[$id] = $result;
        }
        return $accepted;
    }

    /** Coalesce accepted method contexts by their owning type before extending native coverage. */
    public function prepare_methods(\instantiate\Instance_Set $instances): array
    {
        $demands = [];
        $missing = [];
        $methods = [];
        foreach ($instances->functions() as $context)
        {
            $method = $context->definition->external;
            if (!($method instanceof \type_model\family_method)) {
                continue;
            }
            $owner = $instances->type_context($context->receiver_type);
            $prepared = $this->current[$owner->instance_id] ?? throw new \LogicException('Missing prepared family method owner');
            $methods[$context->context_id] = [$owner->instance_id, $method->id];
            $demands[$owner->instance_id][$method->id] = true;
            if (!isset($prepared->operations[$method->id])) {
                $missing[$owner->instance_id] = true;
            }
        }

        // Missing coverage selects work; the request states everything current callers require.
        // A replacement cannot discard a previously available method before signature binding.
        $tasks = [];
        foreach ($missing as $id => $_) {
            $prepared = $this->current[$id];
            $tasks[] = new family_preparation_task($prepared->task->context, array_keys($demands[$id]), $prepared->task->sources);
        }
        if ($tasks !== [])
        {
            $results = $this->provider->prepare($tasks, $this->catalog, array_replace($this->previous, $this->current), $this->packages);
            $accepted = (new Family_Preparation_Join($tasks, $this->catalog))->join($results);
            foreach ($accepted as $id => $result) {
                if ($result->package->type_for($result->type_id)->language_type !== $instances->concrete_types[$id]) {
                    throw new \LogicException('Family coverage replacement changed accepted type identity');
                }
            }
            $this->current = array_replace($this->current, $accepted);
        }

        // Signature workers see fixed implementations; they never extend native packages themselves.
        $calls = [];
        foreach ($methods as $context => [$id, $operation]) {
            $calls[$context] = $this->current[$id]->operations[$operation];
        }
        return $calls;
    }

    public function result(): array
    {
        return $this->current;
    }
}
