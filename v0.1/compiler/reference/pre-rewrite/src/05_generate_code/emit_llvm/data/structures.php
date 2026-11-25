<?php
declare(strict_types=1);

/*
 * Role: Fixed file-module assembly task.
 * Used by: Module_Assembler; Module_Join
 * Flow: function IR + backend/entry -> module_task
 */
namespace emit_llvm;

/**
 * @compiler-api Fixed file-assembly input selected by Module_Assembler. Shared
 * function rows and contracts are read-only; the worker owns only its output IR.
 */
final class module_task
{
    /** @param list<Emitted_Function> $functions In deterministic definition order. */
    public function __construct(
        public readonly int $source_file_id,
        public readonly \prepare_backend\Backend_Context $backend,
        public readonly array $functions,
        public readonly ?\lower\native_entry_plan $entry,
        public readonly array $lifecycle = [],
    )
    {
    }
}

/** A generated definition is owned by an accepted type operation, never a fabricated source AST. */
final class lifecycle_emission_task {
    public function __construct(public readonly \type_model\source_lifecycle_operation $operation,
        public readonly \prepare_backend\Backend_Context $backend)
    {
    }
}

/** Private generated output with exact task provenance and prepared call references. */
final class emitted_lifecycle {
    public function __construct(public readonly lifecycle_emission_task $task, public readonly string $ir,
        public readonly array $references)
    {
    }
}
