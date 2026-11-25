<?php
declare(strict_types=1);
namespace load_runtime;

/** Compiler-side receipt authorization; file integrity and artifact inspection remain adapter/producer work. */
final class Project_Receipt {
    private static function object(\scpp\Json_View $value): void {
        if ($value->kind() !== 'object') { throw new \RuntimeException('Expected project receipt object'); }
    }
    /** PHP producer emits [] for an empty keyed map. Nonempty lists never represent keyed records. */
    private static function map(\scpp\Json_View $value): void {
        if ($value->kind() === 'object') { return; }
        if ($value->kind() === 'array') { if ($value->size() === 0) { return; } }
        throw new \RuntimeException('Expected project receipt map');
    }
    private static function operation(\scpp\Json_View $row, \prepare_backend\Source_Operation_Export $operation): void {
        Project_Receipt::object($row);
        if ($row->size() !== 4) { throw new \RuntimeException('Unexpected source import fields'); }
        $abi = $operation->import;
        if ($abi === null) { throw new \RuntimeException('Unavailable source operation imported'); }
        if (($row->member('symbol')->text() !== $abi->link_name)
            || ($row->member('role')->text() !== \prepare_backend\Source_Export_Roles::name($operation->capability->role))) {
            throw new \RuntimeException('Project source import identity mismatch');
        }
        $semantics = $row->member('semantics'); Project_Receipt::object($semantics);
        $expected = \prepare_backend\Source_Export_Roles::semantics($operation->capability->role);
        if (($semantics->size() !== 9) || ($semantics->member('destination_before')->text() !== $expected->destination_before)
            || ($semantics->member('destination_after')->text() !== $expected->destination_after)
            || ($semantics->member('source_access')->text() !== $expected->source_access)
            || ($semantics->member('source_after')->text() !== $expected->source_after)
            || ($semantics->member('aliasing')->text() !== $expected->aliasing)
            || ($semantics->member('payload_escape')->text() !== $expected->payload_escape)
            || ($semantics->member('failure')->text() !== $expected->failure)
            || ($semantics->member('unwind')->text() !== $expected->unwind)
            || ($semantics->member('resources')->text() !== $expected->resources)) { throw new \RuntimeException('Project source effects mismatch'); }
        $physical = $row->member('abi'); Project_Receipt::object($physical);
        if (($physical->size() !== 4) || ($physical->member('calling_convention')->text() !== $abi->calling_convention)
            || ($physical->member('return_type')->text() !== $abi->return_type)
            || ($physical->member('return_extension')->text() !== \type_model\Callable_Modes::extension_name($abi->return_extension))) {
            throw new \RuntimeException('Project source ABI mismatch');
        }
        $parameters = $physical->member('parameters');
        if ($parameters->kind() !== 'array') { throw new \RuntimeException('Expected source ABI parameter list'); }
        if ($parameters->size() !== q_count($abi->parameters)) { throw new \RuntimeException('Source ABI parameter count mismatch'); }
        foreach ($abi->parameters as $index => $parameter) {
            $part = $parameters->at($index); Project_Receipt::object($part);
            if (($part->size() !== 2) || ($part->member('type')->text() !== $parameter->type)
                || ($part->member('extension')->text() !== \type_model\Callable_Modes::extension_name($parameter->extension))) { throw new \RuntimeException('Source ABI parameter mismatch'); }
        }
    }
    public static function validate(Project_Binding $binding, string $receipt, Package_Target $target): array /** hash<\prepare_backend\Source_Operation_Export> */ {
        if ($receipt !== $binding->receipt) { throw new \RuntimeException('Project module receipt changed before compiler acceptance'); }
        $data = json_read($receipt); Project_Receipt::object($data);
        if ($data->member('schema_version')->integer() !== 1) { throw new \RuntimeException('Unsupported project receipt schema'); }
        $contract = $data->member('contract'); Project_Receipt::object($contract);
        $sources = $contract->member('sources'); Project_Receipt::map($sources);
        if ($sources->size() !== q_count($binding->exports)) { throw new \RuntimeException('Project module source membership mismatch'); }
        $authorized /** hash<\prepare_backend\Source_Operation_Export> */ = [];
        foreach ($binding->exports as $key => $source_export) {
            \prepare_backend\Source_Export_Preparation::validate($source_export);
            if (!$sources->has($key)) { throw new \RuntimeException('Missing project source'); }
            $task = $source_export->task; $source = $sources->member($key); Project_Receipt::object($source);
            $source_target = Manifest_Reader::target($source->member('target'));
            if (($source->member('profile')->text() !== \prepare_backend\Source_Type_Export::profile())
                || ($task->identity->key() !== $key) || ($contract->member('project')->text() !== $task->project->project_key)
                || ($source->member('key')->text() !== $key) || ($source->member('project')->text() !== $task->project->project_key)
                || ($source->member('size')->integer() !== $task->layout->size) || ($source->member('alignment')->integer() !== $task->layout->alignment)
                || ($source_target->triple !== $target->triple) || ($source_target->data_layout !== $target->data_layout)
                || ($task->layout->configuration->target_triple !== $target->triple) || ($task->layout->configuration->data_layout !== $target->data_layout)) {
                throw new \RuntimeException('Project source identity/layout/target mismatch');
            }
            $states = $source->member('states'); $operations = $source->member('operations');
            Project_Receipt::object($states); Project_Receipt::object($operations);
            if (($states->size() !== q_count($source_export->operations)) || ($operations->size() !== q_count($source_export->operations))) { throw new \RuntimeException('Project source role membership mismatch'); }
            foreach ($source_export->operations as $role => $operation) {
                if ($states->member($role)->text() !== \prepare_backend\Source_Export_Roles::state_name($operation->capability->state)) { throw new \RuntimeException('Project source capability mismatch'); }
                $row = $operations->member($role);
                if ($operation->import === null) {
                    if ($row->kind() !== 'null') { throw new \RuntimeException('Unavailable source capability acquired an import'); }
                    continue;
                }
                Project_Receipt::operation($row,$operation); $symbol = $operation->import->link_name;
                if (isset($authorized[$symbol])) { throw new \RuntimeException('Duplicate project source export identity'); }
                $authorized[$symbol] = $operation;
            }
        }
        $variants /** vector<string> */ = ['runtime.ll','runtime.bc','runtime.lto.bc','runtime.thin.bc'];
        $imports = $data->member('required_imports'); Project_Receipt::object($imports);
        if ($imports->size() !== q_count($variants)) { throw new \RuntimeException('Project artifact variant membership mismatch'); }
        $required /** hash<\prepare_backend\Source_Operation_Export> */ = [];
        $names /** vector<string> */ = [];
        foreach ($variants as $variant) {
            $rows = $imports->member($variant); Project_Receipt::map($rows);
            for ($index = 0; $index < $rows->size(); $index++) {
                $symbol = $rows->key($index);
                if (!isset($authorized[$symbol])) { throw new \RuntimeException('Unauthorized project source import'); }
                $operation = $authorized[$symbol]; Project_Receipt::operation($rows->member($symbol),$operation);
                if (!isset($required[$symbol])) { $names[] = $symbol; }
                $required[$symbol] = $operation;
            }
        }
        // Stable bytewise link order. Symbol names have a fixed nonnumeric ASCII prefix.
        for ($index = 1; $index < q_count($names); $index++) {
            $name = $names[$index]; $position = $index;
            while ($position > 0) {
                if (!($name < $names[$position - 1])) { break; }
                $names[$position] = $names[$position - 1]; $position = $position - 1;
            }
            $names[$position] = $name;
        }
        $ordered /** hash<\prepare_backend\Source_Operation_Export> */ = [];
        foreach ($names as $name) { $ordered[$name] = $required[$name]; }
        return $ordered;
    }
}
