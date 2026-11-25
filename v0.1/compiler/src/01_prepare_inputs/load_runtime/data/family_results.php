<?php
declare(strict_types=1);
namespace load_runtime;
/** Fixed specialization demand. Source exports retain accepted owners by formal position. */
final class Family_Preparation_Task {
    private array $operations /** vector<string> */ = [];
    private array $sources /** hash<\prepare_backend\Source_Type_Export,int> */ = [];
    public function __construct(public readonly \instantiate\Instance_Context $context,
        array $operations /** vector<string> */, array $sources /** hash<\prepare_backend\Source_Type_Export,int> */) {
        foreach ($operations as $operation) { $this->operations[] = $operation; }
        foreach ($sources as $position => $source) { $this->sources[$position] = $source; }
    }
    public function operation_count(): int { return q_count($this->operations); }
    public function operation_at(int $index): string {
        if (($index < 0) || ($index >= q_count($this->operations))) { throw new \OutOfBoundsException('Missing family operation demand'); }
        return $this->operations[$index];
    }
    public function source_exports(): array /** hash<\prepare_backend\Source_Type_Export,int> */ { return $this->sources; }
}
/** Association produced by family acceptance; construction alone does not authorize a package. */
final class Family_Preparation_Result {
    private array $operations /** hash<\type_model\Runtime_Callable> */ = [];
    public function __construct(public readonly Family_Preparation_Task $task, public readonly Runtime_Package $package,
        public readonly string $type_id, array $operations /** hash<\type_model\Runtime_Callable> */) {
        foreach ($operations as $id => $operation) { $this->operations[$id] = $operation; }
    }
    public function operation_for(string $id): \type_model\Runtime_Callable {
        if (!isset($this->operations[$id])) { throw new \OutOfBoundsException('Missing prepared family operation'); }
        return $this->operations[$id];
    }
    public function callable_operations(): array /** hash<\type_model\Runtime_Callable> */ { return $this->operations; }
}
