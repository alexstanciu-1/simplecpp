<?php
declare(strict_types=1);

/*
 * Role: Compile original/edited trees through the same session.
 * Used by: main.php [if simulation requested]
 * Call map:
 *   Simulation::run()
 *     -> prepare_folder_swap(); Compiler_Session::compile() [twice]; Folder_Swap::finish()
 */

namespace simulate_increment;

use read_sources\Source_Paths;

/**
 * @compiler-api Optional CLI harness outside compilation semantics; uses Compiler_Session::compile
 * for both runs. Journal/rename mechanics stay here, never in normal compiler stages.
 */
class Simulation
{
    /**
     * @compiler-api Explicit simulation request: reserve both trees, compile twice through one session,
     * journal/swap/restore folders and optionally export JSON. Catchable failures attempt
     * restoration; hard crashes leave a recovery journal. Never executes produced binaries.
     */
    public static function run(string $manifest_path, string $edited_path, bool $debug_json, ?string $output_path = null,
        string|array|null $runtime_package_path = null): void
    {
        $swap = self::prepare_folder_swap($manifest_path, $edited_path);
        if ($output_path !== null)
        {
            $parent = realpath(dirname($output_path));
            if ($parent === false) {
                throw new \InvalidArgumentException('Native output requires an existing parent directory');
            }
            $output_path = $parent . '/' . basename($output_path);
            foreach ([$swap->project, $swap->edited, $swap->journal_directory] as $directory) {
                if (($output_path === $directory) || str_starts_with($output_path, $directory . '/')) {
                    throw new \InvalidArgumentException('Simulation output must be outside both project trees and the journal');
                }
            }
        }
        $swap->begin();

        $session = new \compile\Compiler_Session($swap->project_lock(), runtime_package_path: $runtime_package_path);
        try
        {
            $swap->record("before_initial_run");
            $first = $session->compile($swap->manifest_path, $output_path);
            foreach ($first->warnings as $warning) {
                fwrite(STDERR, $warning . "\n");
            }
            $swap->record("initial_run_complete");

            // Install the edited tree at the same path so the resident session observes an update.
            $swap->park_original();
            $swap->install_edited();
            $swap->record("before_incremental_run");
            $second = $session->compile($swap->manifest_path, $output_path);
            foreach ($second->warnings as $warning) {
                fwrite(STDERR, $warning . "\n");
            }
            $swap->record("incremental_run_complete");
        }
        finally {
            // Catchable failures restore too. A hard crash leaves the journal behind.
            $swap->finish();
        }
        if ($debug_json) {
            echo "{\"runs\":[", $first->to_json(), ",", $second->to_json(), "]}\n";
        }
    }

    /** @compiler-internal Validate two separate project trees and prepare swap paths; no locks/renames yet. */
    public static function prepare_folder_swap(string $manifest_path, string $edited_path): Folder_Swap
    {
        // The manifest directory is the project folder for this initial simulation.
        self::check_pending($manifest_path);
        $swap = new Folder_Swap();
        $swap->project = Source_Paths::resolve(".", dirname($manifest_path));
        $swap->journal_directory = $swap->project . ".scpp-simulation";
        $swap->journal = Source_Paths::join($swap->journal_directory, "journal.jsonl");
        $swap->edited = Source_Paths::resolve(".", $edited_path);
        if ((!is_dir($swap->project)) || (!is_dir($swap->edited))
            || (dirname($swap->project) === $swap->project)
            || ($swap->project === $swap->edited)
            || str_starts_with($swap->edited, Source_Paths::join($swap->project, ""))
            || str_starts_with($swap->project, Source_Paths::join($swap->edited, ""))) {
            throw new \Exception("Simulation requires two distinct, non-nested project directories");
        }

        // Reserve space outside both trees and reject another simulation of the copy.
        if (($swap->edited === $swap->journal_directory)
            || file_exists($swap->edited . ".scpp-simulation") || is_link($swap->edited . ".scpp-simulation")) {
            throw new \Exception("Edited project has a conflicting simulation journal");
        }
        $swap->backup = Source_Paths::join($swap->journal_directory, "original");
        $swap->manifest_path = Source_Paths::join($swap->project, basename($manifest_path));
        if ((!is_file($swap->manifest_path))
            || (!is_file(Source_Paths::join($swap->edited, basename($manifest_path))))) {
            throw new \Exception("Both simulation folders must contain the named project manifest");
        }
        return $swap;
    }

    /**
     * @compiler-api CLI preflight: read pending-journal state and throw with recovery location if present.
     * Works even when a crash left the original project directory absent; no restoration.
     */
    public static function check_pending(string $manifest_path): void
    {
        clearstatcache(true);

        // Check the lexical path first: the original may be absent after a crash.
        $directory = dirname($manifest_path);
        self::require_clear_journal($directory);
        if (is_dir($directory)) {
            self::require_clear_journal(Source_Paths::resolve(".", $directory));
        }
    }

    private static function require_clear_journal(string $directory): void
    {
        $pending = $directory . ".scpp-simulation";
        if (file_exists($pending) || is_link($pending)) {
            throw new \Exception("Pending simulation; restore folders using: " . $pending . "/journal.jsonl");
        }
    }
}
