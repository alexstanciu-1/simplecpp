<?php
declare(strict_types=1);

/*
 * Role: Assemble one selected file module.
 * Used by: Module_Assembler::run()
 * Call map: assemble() -> imports(); module_text() -> Source_Export_Emission::entries(); entry() [entry file]
 */
namespace emit_llvm;

use prepare_backend\LLVM_Types;

/**
 * @compiler-api File-assembly process over a completed function join. Select
 * fixed file tasks before dispatch; workers build private IR, and the coordinator
 * accepts one result per task in any completion order. No native tool operations.
 */
final class Module_Worker
{

    /**
     * @compiler-api Build one complete file module from fixed accepted functions.
     * Private text/import buffers only; no tools, prior snapshot lookup or shared
     * mutation. Result awaits join. Throws for inconsistent backend/file/entry facts.
     */
    public static function assemble(module_task $task): Emitted_Module
    {
        $imports = self::imports($task);
        $ir = self::module_text($task, $imports);
        return new Emitted_Module($task->source_file_id, $task->backend, $task->functions, $task->entry, $ir, $task->lifecycle);
    }

    /** Collect current ABI references outside this file after validating local definitions and entry ownership. */
    private static function imports(module_task $task): array
    {
        $file_id = $task->source_file_id;
        $backend = $task->backend;
        $functions = $task->functions;
        $entry = $task->entry;

        // Establish which callable definitions belong to this file before discovering imports.
        $local = [];
        foreach ($functions as $function)
        {
            $id = $function->body->binding->link_name;
            if ((isset($local[$id])) || ($function->body->source_file_id() !== $file_id)
                || ($function->body->backend_context() !== $backend)) {
                throw new \LogicException('Duplicate, foreign or stale module function');
            }
            $local[$id] = true;
        }
        if (($entry !== null) && ((!isset($local[$entry->entry->link_name]))
                || ($backend->binding_for($entry->entry->callable_id) !== $entry->entry))) {
            throw new \LogicException('Foreign or stale module entry');
        }

        foreach ($task->lifecycle as $link => $definition) {
            if (($task->entry === null) || isset($local[$link]) || ($definition->task->backend !== $backend)) {
                throw new \LogicException('Duplicate or stale generated lifecycle definition');
            }
            $local[$link] = true;
        }

        // Only calls to definitions outside this module require external declarations.
        $imports = [];
        foreach ([...$functions, ...array_values($task->lifecycle)] as $function)
        {
            foreach ($function->references as $id => $binding)
            {
                if ($backend->abi_for($id) !== $binding) {
                    throw new \LogicException('Stale emitted call contract');
                }
                if (!isset($local[$id])) {
                    $imports[$id] = $binding;
                }
            }
        }
        return $imports;
    }

    /** Assemble target facts, ABI declarations, function definitions and the optional native entry into one module. */
    private static function module_text(module_task $task, array $imports): string
    {
        $backend = $task->backend;
        $functions = $task->functions;
        $entry = $task->entry;

        // Target facts and external declarations precede all definitions in this file module.
        $config = $backend->configuration;
        $ir = 'target triple = ' . LLVM_Types::quote($config->target_triple) . "\n"
            . 'target datalayout = ' . LLVM_Types::quote($config->data_layout) . "\n";
        foreach ($imports as $binding) {
            $ir .= 'declare ' . $binding->calling_convention . LLVM_Types::attribute($binding->return_extension) . ' '
                . $binding->return_type
                . ' @' . LLVM_Types::quote($binding->link_name) . '(' . implode(', ', array_map(static fn($parameter) => $parameter->type . LLVM_Types::attribute($parameter->extension), $binding->parameters)) . ")\n";
        }

        // The prepared entry plan contributes a wrapper only to the designated entry module.
        foreach ($functions as $function) {
            $ir .= $function->ir;
        }
        foreach ($task->lifecycle as $definition) {
            $ir .= $definition->ir;
        }
        if ($entry !== null) {
            $ir .= Source_Export_Emission::entries($backend, $task->lifecycle);
            $ir .= self::entry($entry);
        }
        $ir .= 'attributes #0 = { "target-cpu"=' . LLVM_Types::quote($config->cpu)
            . ' "target-features"=' . LLVM_Types::quote($config->features) . " }\n";
        return $ir;
    }

    /** Emit the prepared native entry wrapper and adapt its integer exit status when required. */
    private static function entry(\lower\native_entry_plan $plan): string
    {
        $binding = $plan->entry;
        $source_type = LLVM_Types::return_type($binding);
        $native_type = 'i' . $plan->native_bits;
        $ir = 'define ' . $plan->calling_convention . ' ' . $native_type . ' @' . LLVM_Types::quote($plan->link_name) . "() #0 {\nentry:\n  %result = call "
            . $binding->calling_convention . ' ' . $source_type . ' @' . LLVM_Types::quote($binding->link_name) . '(' . LLVM_Types::parameters($binding, false) . ")\n";
        $value = '%result';
        if ($plan->conversion !== \prepare_backend\integer_adaptation::identity) {
            $ir .= '  %status = ' . LLVM_Types::integer_conversion($plan->conversion, $binding->return_definition->representation->payload->bit_width, $plan->native_bits, '%result') . "\n";
            $value = '%status';
        }
        return $ir . '  ret ' . $native_type . ' ' . $value . "\n}\n";
    }

}
