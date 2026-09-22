<?php
declare(strict_types=1);

/*
 * Role: CLI options, compiler requests and diagnostic/output delivery.
 * Call map:
 *   [action] decode CLI arguments
 *     -> Compiler_Session::compile() [normal request]
 *     -> Simulation::run() [if --simulate-increment]
 */

/**
 * @compiler-internal CLI transport; not a file for other compiler steps to include.
 * Parses request/debug/simulation flags and calls the resident session API.
 * Printing and process exit stay here; stage errors propagate as exceptions.
 */
use read_sources\Source_Reader;
use simulate_increment\Simulation;

require_once dirname(__DIR__) . '/bootstrap.php';

try
{
    // Run the common compiler entry point through the implemented stages.
    // TODO: extend this driver to resident updates as the remaining stages land.
    $arguments = $argv;
    $input_path = "";
    $debug_json = false;
    $check_only = false;
    $show_help = false;
    $simulate_path = "";
    $output_path = null;
    $runtime_package_path = null;
    $index = 1;
    while ($index < count($argv))
    {
        $argument = $arguments[$index];
        if ($argument === "--debug=json") {
            $debug_json = true;
        }
        else if ($argument === "--check") {
            $check_only = true;
        }
        else if ($argument === "--help") {
            $show_help = true;
        }
        else if ($argument === "--runtime-package")
        {
            ++$index;
            if (($index >= count($argv)) || ($arguments[$index] === '') || str_starts_with($arguments[$index], '-')) {
                throw new Exception('Expected a prepared runtime directory after --runtime-package');
            }
            $runtime_package_path ??= [];
            $runtime_package_path[] = $arguments[$index];
        }
        else if ($argument === "--output") {
            ++$index;
            if (($output_path !== null) || ($index >= count($argv)) || ($arguments[$index] === '') || str_starts_with($arguments[$index], '-')) {
                throw new Exception('Expected one executable path after --output');
            }
            $output_path = $arguments[$index];
        }
        else if ($argument === "--simulate-increment")
        {
            $index = $index + 1;
            if (($simulate_path !== "") || ($index >= count($argv))) {
                throw new Exception("Expected one folder after --simulate-increment");
            }
            $simulate_path = $arguments[$index];
            if (($simulate_path === "") || str_starts_with($simulate_path, "-")) {
                throw new Exception("Expected a folder after --simulate-increment");
            }
        }
        else if (str_starts_with($argument, "-")) {
            throw new Exception("Unsupported option: " . $argument);
        }
        else if ($input_path !== "") {
            throw new Exception("Expected exactly one project manifest or .phs source path");
        }
        else {
            $input_path = $argument;
        }
        $index = $index + 1;
    }
    if ($show_help)
    {
        echo "Usage: php compiler/src/main.php [--debug=json] [--check | --output <executable>] [--simulate-increment <edited-project-folder>] <project.json | source.phs>\n";
        echo "A .phs input creates a virtual project containing only that file and builds an executable beside it, using its stem and the target suffix (.exe for Windows). No project file is created. --output uses the exact supplied filename. --check stops after LLVM emission without native building. Folder-swap simulation requires a project manifest.\n";
        echo "Current prototype checks named functions and the manifest entry, analyzes scalar lifetimes, prepares LLVM callable bindings and lowers constants, calls and returns, then emits LLVM IR. For manifest input, omitting --output stops before native building; --output compiles, links and publishes the executable without running it. --debug=json reports inputs, tokens, ASTs, collected symbols, change descriptions, call bindings, callable signatures, selected entry, type definitions, typed bodies, lifetime results, backend context, lowered values/instructions/blocks, LLVM IR, native output, cleanup warnings, completion and the stopping point. Cleanup warnings also go to stderr without changing successful publication.\n";
        echo "Backend preparation requires the Clang selected by compiler/tools/backend.json; its target may be explicit or Clang's default. The hosted C entry ABI is probed; its integer status conversion is prepared separately from language casts. Native linking requires the target runtime/linker to be installed.\n";
        echo Source_Reader::change_detection_limitation(), "\n";
        echo "--runtime-package <directory> imports a prepared runtime package and links its ordinary bitcode. Repeat for multiple packages. Preparation is a separate command.\n";
        echo "Simulation swaps the manifest directory with the edited folder for a second input refresh, then restores both. Recovery journal: <project-folder>.scpp-simulation/journal.jsonl. Preserve unchanged timestamps in the edited copy.\n";
    }
    else
    {
        if ($input_path === "") {
            throw new Exception("Usage: php compiler/src/main.php [--debug=json] <project.json | source.phs>; use --help for current limitations");
        }
        if (($check_only) && ($output_path !== null)) {
            throw new Exception('--check cannot be combined with --output');
        }
        $single_file = \read_sources\Source_Paths::is_source($input_path);
        if (($single_file) && ($simulate_path !== '')) {
            throw new Exception('--simulate-increment requires a project manifest, not a single source file');
        }
        if ($simulate_path !== "") {
            Simulation::run($input_path, $simulate_path, $debug_json, $output_path, $runtime_package_path);
        }
        else
        {
            Simulation::check_pending($input_path);
            $session = new \compile\Compiler_Session(runtime_package_path: $runtime_package_path);
            $result = $session->compile($input_path, $output_path, ($single_file) && (!$check_only));
            foreach ($result->warnings as $warning) {
                fwrite(STDERR, $warning . "\n");
            }
            if ($debug_json) {
                echo $result->to_json(), "\n";
            }
        }
    }
}
catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
