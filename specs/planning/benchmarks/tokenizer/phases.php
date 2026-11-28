<?php
declare(strict_types=1);

use scpp\compiler\{Program_Frontend, Cpp_Program, source_file, flavor, entry_target};
require $argv[1] . '/boot.php';
$f = flavor::from($argv[3]);
$sources = [];
foreach (['main', 'scanner'] as $name) {
    $path = $name . '.' . $f->source_extension();
    $sources[$path] = new source_file($path, file_get_contents("$argv[2]/$path"), $f);
}
$start = hrtime(true);
$program = (new Program_Frontend())->prepare_sources('main.' . $f->source_extension(), $sources, new entry_target(31));
$prepared = hrtime(true);
Cpp_Program::emit($program);
echo json_encode([($prepared - $start) / 1e6, (hrtime(true) - $prepared) / 1e6]);
