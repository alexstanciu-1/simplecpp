<?php
declare(strict_types=1);

/*
 * Role: Compile and seal a fixed object batch.
 * Used by: Native_Builder::run()
 * Call map: Native_Compiler::compile_batch() -> LLVM_Toolchain::compile_objects(); Native_Object::seal()
 */

namespace build_native;

final class Native_Compiler
{
    /**
     * @compiler-api Reserve private outputs for the fixed selected modules, execute
     * through the tool service's bounded external-process queue, then seal results.
     * All running writers stop before any failed batch files are discarded.
     * @param list<\emit_llvm\Emitted_Module> $modules @return list<Native_Object>
     */
    public static function compile_batch(array $modules, \prepare_backend\LLVM_Toolchain $toolchain): array
    {
        $objects = [];
        $requests = [];
        try
        {
            // Reserve every private object path before launching any tool that can write to it.
            foreach ($modules as $module)
            {
                $path = sys_get_temp_dir() . '/scpp-object-' . bin2hex(random_bytes(16)) . '.o';
                $file = fopen($path, 'x');
                if ($file === false) {
                    throw new \RuntimeException('Cannot reserve native object');
                }
                fclose($file);
                $objects[] = new Native_Object($module, $path);
                if (!chmod($path, 0600)) {
                    throw new \RuntimeException('Cannot protect native object');
                }
                $requests[] = new \prepare_backend\object_compilation($module->ir, $path, $module->backend->configuration);
            }

            // Seal artifacts only after the whole selected batch has completed successfully.
            $toolchain->compile_objects($requests);
            foreach ($objects as $object) {
                $object->seal();
            }
            return $objects;
        }
        catch (\Throwable $error)
        {
            $warnings = [];
            foreach ($objects as $object) {
                array_push($warnings, ...$object->discard());
            }
            if ($warnings !== []) {
                throw new \RuntimeException($error->getMessage() . "\n" . implode("\n", $warnings), 0, $error);
            }
            throw $error;
        }
    }

    /** @compiler-api One module uses the same batch executor; no separate sequential path. */
    public static function compile(\emit_llvm\Emitted_Module $module, \prepare_backend\LLVM_Toolchain $toolchain): Native_Object
    {
        return self::compile_batch([$module], $toolchain)[0];
    }
}
