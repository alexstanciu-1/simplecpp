<?php
declare(strict_types=1);
namespace runtime_preparation\families;

use runtime_preparation as native;

/** Runtime-only family coordinator. No compiler phase can implicitly invoke it. */
final class Preparation
{
    public function __construct(private readonly Store $store)
    {
    }

    /** Select fixed coverage, execute a private worker, accept it, then publish at the stable slot. */
    public function run(specialization_request $request): array
    {
        $task = $this->select($request);
        $result = self::execute($task);
        try {
            $accepted = (new Join([$task]))->join([$result]);
            return $accepted[$task->key]->candidate->publish();
        }
        finally {
            $result->candidate->release();
        }
    }

    /** Validate eligibility before allocation/generation, and extend only matching semantic contracts. */
    public function select(specialization_request $request): preparation_task
    {
        if (native\Files::hashes(array_keys($request->dependency_hashes)) !== $request->dependency_hashes) {
            throw new \RuntimeException('Accepted native argument headers changed before selection');
        }
        $family = $request->catalog->families[$request->family] ?? throw new \RuntimeException('Unknown requested family');
        $arguments = Requests::arguments($request->catalog, $family, $request->arguments);
        foreach ($request->catalog->types as $type) {
            if (!in_array($type->definition['kind'], ['integer', 'void'], true)) {
                throw new \RuntimeException('Production family preparation requires runtime scalar mappings; source imports are deferred');
            }
        }
        if ($request->scope === '') {
            throw new \RuntimeException('Specialization requires an explicit identity scope');
        }
        $project = \runtime_preparation\project\Module::from_arguments($request->scope, $arguments);
        $coverage = Requests::coverage($family, $request->operations);
        native\Definitions::fields($request->configuration, ['clang', 'target', 'standard', 'include_directories'], 'family context');
        $config = native\Native_Types::context($request->configuration, '/');
        foreach ($arguments as $argument) {
            if (($argument->context !== []) && ($argument->context !== $config)) {
                throw new \RuntimeException('Native argument preparation context mismatch');
            }
        }
        $key = json_encode([$request->scope, $family->semantic->key(), array_map(static fn($type) =>
            [$type->identity->provider, $type->identity->id], $arguments), $config], JSON_THROW_ON_ERROR);
        $contract = json_encode([$request->catalog, $config,
            array_map(static fn($type) => $type->contract, $arguments)], JSON_THROW_ON_ERROR);
        $output = $this->store->locate($key);
        $reservation = new native\Package_Reservation($output);
        $reservation->acquire();
        native\Publication::recover_publication($output);
        $retained = Store::retained($output);
        if (($retained !== null) && (($retained['key'] ?? null) !== $key)) {
            throw new \RuntimeException('Specialization slot identity mismatch');
        }
        if (($retained['contract'] ?? null) === $contract) {
            $coverage = Requests::coverage($family, array_values(array_unique([...$coverage, ...$retained['coverage']])));
        }

        // Exact identity determines symbols. The directory slot is never used as a global symbol identity.
        $provider = native\Symbols::name(['family', $key]);
        $instance_id = Requests::instance_id($request->catalog);
        $expanded = Requests::export($family, $arguments, $coverage, $provider, $instance_id);
        $inputs = $output . '/inputs';
        native\Files::directory($inputs . '/definitions');
        $types = array_map(static fn($type) => $type->definition, array_values($request->catalog->types));
        $headers = array_values(array_unique([$family->header, ...array_column($types, 'header')]));
        $imported = [];
        foreach ($arguments as $argument)
        {
            $headers = array_values(array_unique([...$headers, ...$argument->headers]));
            if (isset($argument->definition['native_import']) || isset($argument->definition['source_payload'])) {
                $imported[$argument->definition['id']] = $argument->definition;
            }
        }
        $types = [...$types, ...array_values($imported)];
        $declarations = Arguments::declarations($arguments);
        $native_declarations = $declarations;
        $native_declarations[$expanded['type']['cpp_name']] = $expanded['declaration'];
        $expanded['type']['native_preparation'] = ['headers' => $headers, 'declarations' => $native_declarations,
            'contract' => $contract, 'context' => $config];
        sort($headers);
        $header = "#pragma once\n";
        foreach ($headers as $include) {
            $header .= '#include <' . $include . ">\n";
        }
        native\Files::write($inputs . '/source_types.hpp', $header . implode('', $declarations) . $expanded['declaration']);
        native\Files::write_json($inputs . '/definitions/request.json', ['schema_version' => 1,
            'types' => [...$types, $expanded['type']], 'operations' => $expanded['operations']]);
        $config = ['schema_version' => 1, 'provider' => $provider, ...$config,
            'definitions_directory' => $inputs . '/definitions', 'output_directory' => $output];
        $config['include_directories'][] = $inputs;
        native\Files::write_json($inputs . '/config.json', $config);
        $receipt = json_encode(['key' => $key, 'contract' => $contract, 'coverage' => $coverage,
            'instance_type' => $instance_id], JSON_THROW_ON_ERROR);
        $input_hashes = [...$request->dependency_hashes, ...native\Files::hashes([$inputs . '/config.json',
            $inputs . '/definitions/request.json', $inputs . '/source_types.hpp'])];
        ksort($input_hashes);
        return new preparation_task($key, $family, $arguments, $coverage, $contract,
            $inputs . '/config.json', $receipt, $input_hashes, $reservation, $project);
    }

    /** The same fixed worker can run serially now and in a future scheduling layer. */
    public static function execute(preparation_task $task): preparation_result
    {
        if (native\Files::hashes(array_keys($task->input_hashes)) !== $task->input_hashes) {
            throw new \RuntimeException('Selected family inputs changed before execution');
        }
        $candidate = (new native\Runtime_Preparation())->stage($task->configuration, $task->reservation, $task->receipt, $task->project);
        return new preparation_result($task, $candidate);
    }
}
