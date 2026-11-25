<?php
declare(strict_types=1);

/*
 * Role: Own accepted physical layouts throughout one compiler update.
 * Call map: Concrete_Preparation / LLVM_Backend -> Layout_Coordinator::prepare()
 *   -> Layout_Preparation::capture(); select(); prepare(); Layout_Join::join()
 * Output: accepted subset layouts; final backend owns retained results, never this service.
 */
namespace prepare_backend;

/** Coordinator service; fixed workers receive neither this owner nor the mutable type store. */
final class Layout_Coordinator
{
    private array $accepted = [];
    private array $dependencies = [];
    private ?\type_model\type_lineage $lineage = null;
    private ?backend_configuration $configuration = null;

    /** Capture one update's target service, previous facts and fixed full-selection policy. */
    public function __construct(private readonly LLVM_Toolchain $toolchain, private readonly array $previous,
        private readonly bool $full_rebuild)
    {
    }

    /** Prepare an explicitly requested ready subset; accepted facts are reused within this update even on full rebuild. */
    public function prepare(\type_model\Type_Store $types, array $roots): array
    {
        if (($this->lineage !== null) && ($this->lineage !== $types->lineage)) {
            throw new \LogicException('Layout coordinator cannot cross type lineages');
        }
        $this->lineage = $types->lineage;
        $roots = array_values(array_unique($roots));
        sort($roots);
        $input = Layout_Preparation::capture($types, $roots, $this->dependencies);
        $pending = [];
        foreach ($roots as $id)
        {
            $accepted = $this->accepted[$id] ?? null;
            if ($accepted === null) {
                $pending[] = $id;
            }
            elseif (!Layout_Preparation::current($accepted, $input, $id, $this->configuration)) {
                throw new \LogicException('Accepted layout dependencies changed within the update');
            }
        }
        if ($pending !== [])
        {
            // Configuration and dependency rows are fixed before selecting this batch.
            $this->configuration ??= $this->toolchain->configuration();
            $input = Layout_Preparation::subset($input, $pending);
            [$command, $launcher] = $this->toolchain->layout_tools($this->configuration);
            [$native] = $this->toolchain->layout_tools($this->configuration, true);
            $tasks = Layout_Preparation::select($input, $this->configuration, $this->previous,
                $this->full_rebuild, $command, $launcher, $native);
            $results = array_map(Layout_Preparation::prepare(...), $tasks);
            $accepted = (new Layout_Join($input, $this->configuration, $this->previous, $tasks))->join($results);
            $this->accepted = array_replace($this->accepted, $accepted);

            // Share accepted nodes across later batches; each retained layout keeps only its own reachable DAG.
            $nodes = array_map(static fn($layout) => $layout->dependency, array_values($accepted));
            while ($nodes !== [])
            {
                $node = array_pop($nodes);
                if (($this->dependencies[$node->type_id] ?? null) === $node) {
                    continue;
                }
                $this->dependencies[$node->type_id] = $node;
                foreach ($node->children as $child) {
                    $nodes[] = $child;
                }
            }
        }

        // Returning only requested membership removes deleted contributions at final preparation.
        $layouts = array_intersect_key($this->accepted, array_fill_keys($roots, true));
        ksort($layouts);
        return $layouts;
    }
}
