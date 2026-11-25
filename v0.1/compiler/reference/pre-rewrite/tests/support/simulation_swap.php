<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

class Simulation_Swap_Test
{
    public static function pause_swap(\simulate_increment\Folder_Swap $swap, string $stage, string $requested): void
    {
        if ($stage === $requested)
        {
            $swap->record("test_paused");
            while (!file_exists($swap->journal . ".continue")) {
                usleep(20000);
            }
            if (!@unlink($swap->journal . ".continue")) {
                throw new Exception("Cannot remove test continuation marker");
            }
        }
    }
}

// Crash probe: use the production swap owner, pause at a real rename boundary.
// The Python test kills this process or releases it to exercise failed cleanup.

$arguments = $argv;
$manifest = $arguments[1];
$edited = $arguments[2];
$stage = $arguments[3];
$swap = \simulate_increment\Simulation::prepare_folder_swap($manifest, $edited);
$swap->begin();
Simulation_Swap_Test::pause_swap($swap, "prepared", $stage);
$swap->park_original();
Simulation_Swap_Test::pause_swap($swap, "original_parked", $stage);
$swap->install_edited();
Simulation_Swap_Test::pause_swap($swap, "edited_installed", $stage);
$swap->return_edited();
Simulation_Swap_Test::pause_swap($swap, "edited_returned", $stage);
$swap->restore_original();
Simulation_Swap_Test::pause_swap($swap, "original_restored", $stage);
$swap->finish();
