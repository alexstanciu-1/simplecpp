<?php
declare(strict_types=1);

/*
 * Role: Check file membership and retained module dependencies.
 * Used by: Module_Assembler::select(); Module_Join::join()
 * Call map:
 *   Module_Validity::is_current()
 *     -> [action] compare functions, imports and entry adaptation
 */

namespace emit_llvm;

/** @compiler-internal Shared module validity for assembly selection and joining. */
class Module_Validity
{
    public static function is_current(?Emitted_Module $module, Emitted_Function_Set $current, int $id): bool
    {
        return ($module !== null) && ($module->source_file_id === $id) && ($module->backend === $current->backend)
            && ($module->entry === $current->entry_for_file($id)) && ($module->functions === $current->for_file($id))
            && ($module->lifecycle === ($current->entry_for_file($id) === null ? [] : $current->lifecycle));
    }
}
