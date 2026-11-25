<?php
declare(strict_types=1);

/*
 * Role: Function/file IR outputs and completed program queries.
 * Used by: Emission_Worker; module assembly/joins; Native_Builder
 * Flow: Emitted_Function -> Emitted_Module -> Emitted_Program
 */
namespace emit_llvm;

/**
 * @compiler-api Private worker output awaiting emission join. body/ir are shared
 * unchanged; references contains distinct called backend contracts, collected
 * during emission rather than by rescanning retained syntax or instructions.
 */
class Emitted_Function {
    /** @param array<string, \prepare_backend\abi_target> $references */
    public function __construct(public readonly \lower\Lowered_Body $body, public readonly string $ir,
        public readonly array $references = [])
    {
    }
}

/**
 * @compiler-api Completed function-emission join, read by file-assembly selection
 * and join. Groups existing rows without copying functions or their IR. Temporary
 * phase input; the accepted Emitted_Program remains the resident reuse baseline.
 */
final class Emitted_Function_Set implements \compile\Step_Result, \compile\Step_Store
{
    /**
     * @compiler-internal Only the function join constructs complete current groups.
     * @param array<int, list<Emitted_Function>> $by_file In current file/definition order.
     */
    public function __construct(
        public readonly \prepare_backend\Backend_Context $backend,
        public readonly \lower\native_entry_plan $entry,
        public readonly int $entry_file_id,
        private readonly array $by_file,
        public readonly array $lifecycle = [],
    )
    {
    }

    /** @compiler-api Iterate fixed file groups; no copied function rows or IR. */
    public function files(): array
    {
        return $this->by_file;
    }

    /** @compiler-api Read a current file's definitions; null means absent. */
    public function for_file(int $id): ?array
    {
        return $this->by_file[$id] ?? null;
    }

    /** @compiler-api The native adapter belongs only to the selected entry file. */
    public function entry_for_file(int $id): ?\lower\native_entry_plan
    {
        return $id === $this->entry_file_id ? $this->entry : null;
    }
}

/**
 * @compiler-api One source file's complete LLVM compilation unit, produced by
 * emission and consumed by native building. Definitions, external declarations,
 * target facts and optional native entry are self-contained in ir. Shared result
 * identity is its resident reuse boundary; consumers must not mutate its inputs.
 */
class Emitted_Module
{
    /**
     * Retain one file module after checking unique definitions and optional entry ownership.
     * @param list<Emitted_Function> $functions
     */
    public function __construct(
        public readonly int $source_file_id,
        public readonly \prepare_backend\Backend_Context $backend,
        public readonly array $functions,
        public readonly ?\lower\native_entry_plan $entry,
        public readonly string $ir,
        public readonly array $lifecycle = [],
    )
    {
        if (($source_file_id <= 0) || ($functions === [])) {
            throw new \LogicException('Module requires source definitions');
        }
        $seen = [];
        foreach ($functions as $function) {
            $id = $function->body->binding->callable_id;
            if ((isset($seen[$id])) || ($function->body->source_file_id() !== $source_file_id)) {
                throw new \LogicException('Duplicate or foreign module definition');
            }
            $seen[$id] = true;
        }
        if (($entry !== null) && (!isset($seen[$entry->entry->callable_id]))) {
            throw new \LogicException('Foreign module entry');
        }
    }
}

/**
 * @compiler-api Complete emission snapshot for compile, debug and native build.
 * Owns one module per contributing source file. Function rows and lowered inputs
 * are shared; the indexes are private. Only emission assembles nonempty results.
 * No combined project IR is retained or passed to Clang.
 */
class Emitted_Program implements \compile\Step_Result, \compile\Step_Store
{
    private readonly array $by_symbol;
    public readonly array $lifecycle;
    private readonly array $by_file;

    /**
     * Index a complete module snapshot after checking backend consistency and its native entry.
     * @param list<Emitted_Module> $modules
     */
    public function __construct(
        public readonly \prepare_backend\Backend_Context $backend,
        public readonly \lower\native_entry_plan $entry,
        public readonly array $modules,
    )
    {
        $files = [];
        $functions = [];
        foreach ($modules as $module)
        {
            if ((isset($files[$module->source_file_id])) || ($module->backend !== $backend)) {
                throw new \LogicException('Duplicate or stale emitted module');
            }
            $files[$module->source_file_id] = $module;
            foreach ($module->functions as $function) {
                $id = $function->body->binding->callable_id;
                if (isset($functions[$id])) {
                    throw new \LogicException('Invalid or duplicate emitted function owner');
                }
                $functions[$id] = $function;
            }
        }
        $entry_function = $functions[$entry->entry->callable_id] ?? null;
        if (($entry_function === null) || ($files[$entry_function->body->source_file_id()]->entry !== $entry)) {
            throw new \LogicException('Missing native entry module');
        }
        $this->lifecycle = $files[$entry_function->body->source_file_id()]->lifecycle;
        foreach ($modules as $module) {
            if (($module->entry === null) && ($module->lifecycle !== [])) {
                throw new \LogicException('Generated lifecycle definitions require their entry module owner');
            }
        }
        $this->by_file = $files;
        $this->by_symbol = $functions;
    }

    /** @compiler-api Shared current function by concrete callable ID, or null. */
    public function function_for(int $id): ?Emitted_Function
    {
        return $this->by_symbol[$id] ?? null;
    }

    /** @compiler-api Shared module by source-file ID, or null for no emitted definitions. */
    public function module_for(int $id): ?Emitted_Module
    {
        return $this->by_file[$id] ?? null;
    }

    /** @compiler-api Iterate shared functions in module/definition order. */
    public function functions(): array
    {
        return array_values($this->by_symbol);
    }

    /** @compiler-api On-demand per-file IR for inspection; each string is a separate LLVM input. */
    public function ir_by_file(): array
    {
        $result = [];
        foreach ($this->modules as $module) {
            $result[$module->source_file_id] = $module->ir;
        }
        return $result;
    }

    /** @compiler-api Total emitted bytes without concatenating/copying the module texts. */
    public function byte_count(): int
    {
        $bytes = 0;
        foreach ($this->modules as $module) {
            $bytes += strlen($module->ir);
        }
        return $bytes;
    }

    /** @compiler-api On-demand debug export; not an object cache or LLVM verifier. */
    public function to_array(): array
    {
        return ['entry_callable_id' => $this->entry->entry->callable_id, 'native_return_bits' => $this->entry->native_bits,
            'entry_conversion' => $this->entry->conversion->value,
            'modules' => array_map(static fn($m) => ['source_file_id' => $m->source_file_id,
                    'callable_ids' => array_map(static fn($f) => $f->body->binding->callable_id, $m->functions),
                    'ir' => $m->ir], $this->modules)];
    }
}
