<?php
declare(strict_types=1);

/*
 * Role: Emit stable source entry points forwarding to common complete lifecycle definitions.
 * Call map: Module_Worker::module_text() -> Source_Export_Emission::entries()
 *   Module_Join -> closure() before publishing the emitted program
 * Output: entry-module wrappers; no field traversal or native behavior is reimplemented here.
 */
namespace emit_llvm;

final class Source_Export_Emission
{
    /** The selected entry module owns these wrappers and the existing lifecycle emission they call. */
    public static function entries(\prepare_backend\Backend_Context $backend, array $lifecycle): string
    {
        $ir = '';
        foreach ($backend->source_exports as $symbol => $export)
        {
            $implementation = $backend->abi_for($export->implementation->link_name);
            if (($implementation?->lifecycle_operation !== $export->capability->operation)
                || (($lifecycle[$implementation->link_name]->task->operation ?? null) !== $export->capability->operation)) {
                throw new \LogicException('Source export requires an emitted complete implementation');
            }
            $parameters = [];
            foreach ($export->import->parameters as $index => $parameter) {
                $parameters[] = $parameter->type . ' %arg' . $index;
            }
            $signature = implode(', ', $parameters);
            $ir .= 'define ccc void @' . \prepare_backend\LLVM_Types::quote($symbol) . '(' . $signature . ") #0 {\nentry:\n"
                . '  call ccc void @' . \prepare_backend\LLVM_Types::quote($implementation->link_name) . '(' . $signature . ")\n"
                . "  ret void\n}\n";
        }
        return $ir;
    }

    /** Require exactly one real definition for each selected native source import before final linking. */
    public static function closure(\prepare_backend\Backend_Context $backend, array $modules): void
    {
        $definitions = [];
        foreach ($modules as $module)
        {
            preg_match_all('/^define ccc void @"(scpp_source_[^"]+)"\(([^\n]*)\)/m', $module->ir, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                if (isset($definitions[$match[1]]) || !isset($backend->source_exports[$match[1]])) {
                    throw new \LogicException('Duplicate or unauthorized source export definition');
                }
                $definitions[$match[1]] = $match[2];
            }
        }
        foreach ($backend->source_exports as $symbol => $export)
        {
            $parameters = [];
            foreach ($export->import->parameters as $index => $parameter) {
                $parameters[] = $parameter->type . ' %arg' . $index;
            }
            if (($definitions[$symbol] ?? null) !== implode(', ', $parameters)) {
                throw new \LogicException('Missing or incompatible emitted source export definition');
            }
        }
    }
}
