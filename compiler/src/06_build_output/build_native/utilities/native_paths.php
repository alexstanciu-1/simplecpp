<?php
declare(strict_types=1);

/*
 * Role: Choose default executable names and validate output paths against compiler inputs.
 * Used by: Compiler_Session::finish()
 * Call map:
 *   default_output() -> [action] name output beside the entry for the prepared target
 *   destination() -> [action] validate source selections and protected inputs
 */

namespace build_native;

final class Native_Paths
{
    /** @compiler-api Default executable beside the entry; naming follows Clang's canonical target triple. */
    public static function default_output(\read_sources\Source_Set $sources,
        \prepare_backend\backend_configuration $configuration): string
    {
        $entry = $sources->entry_file()->full_path;
        $name = pathinfo($entry, PATHINFO_FILENAME);
        if ($name === '') {
            throw new \InvalidArgumentException('Source filename needs a stem for default executable naming; use --output');
        }

        // Clang normalizes Windows targets (including MinGW) to the Windows OS component.
        $components = explode('-', $configuration->target_triple);
        $os = $components[2] ?? '';
        $suffix = preg_match('/^windows[0-9.]*$/', $os) === 1 ? '.exe' : '';
        return \read_sources\Source_Paths::join(dirname($entry), $name . $suffix);
    }

    /**
     * @compiler-api Validate/canonicalize an output path outside recursive roots and protected input files.
     * Filesystem reads only; caller supplies active provider/tool inputs. Throws on
     * invalid/conflicting paths; validation does not reserve the destination.
     * @param list<string> $protected_inputs Paths supplied by active owners, including the project lock.
     */
    public static function destination(string $requested, \read_manifest\Project_Manifest $manifest,
        \read_sources\Source_Set $sources, array $protected_inputs): string
    {
        $parent = realpath(dirname($requested));
        if (($requested === '') || ($parent === false) || (!is_dir($parent))) {
            throw new \InvalidArgumentException('Native output requires an existing parent directory');
        }
        $path = \read_sources\Source_Paths::join($parent, basename($requested));
        if (is_link($path) || (file_exists($path) && (!is_file($path)))) {
            throw new \InvalidArgumentException('Native output must be a regular file path');
        }
        if ($path === realpath($manifest->path)) {
            throw new \InvalidArgumentException('Native output would replace compiler input');
        }
        foreach ($sources->folders as $folder) {
            if (($folder->file_names === null)
                && (($path === $folder->resolved_path) || str_starts_with($path, $folder->resolved_path . '/'))) {
                throw new \InvalidArgumentException('Native output must be outside participating source folders');
            }
        }
        if ($sources->find_file_id($path) !== 0) {
            throw new \InvalidArgumentException('Native output would replace compiler input: ' . $path);
        }
        foreach ($protected_inputs as $input)
        {
            $canonical = realpath($input);
            if ($canonical === false) {
                throw new \RuntimeException('Cannot locate protected compiler input: ' . $input);
            }
            if ($path === $canonical) {
                throw new \InvalidArgumentException('Native output would replace compiler input: ' . $canonical);
            }
        }
        return $path;
    }
}
