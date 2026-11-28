<?php
declare(strict_types=1);

use scpp\compiler\{Program_Frontend, Preparation, Scoped_Byte_View, Last_Use_Transfer, Symbol_Publication, entry_target, source_file, flavor};

// Arguments: compiler directory, single-flavor scanner fixture directory, flavor.
require $argv[1] . '/boot.php';
$flavor = flavor::from($argv[3]);
$suffix = $flavor === flavor::php ? 'phs' : $flavor->value;
$sources = [];
foreach (['scanner', 'main'] as $name) {
    $path = "$name.$suffix";
    $sources[$path] = new source_file($path, file_get_contents("$argv[2]/$path"), $flavor);
}
$program = (new Program_Frontend())->prepare_sources("main.$suffix", $sources, new entry_target(31));
$symbols = Symbol_Publication::completed($program->files);
$body = $program->files["scanner.$suffix"]->functions()[0]->body();
$worker = new Preparation();

function median_microseconds(Closure $operation, int $iterations): float
{
    $samples = [];
    for ($batch = 0; $batch < 11; ++$batch) {
        $start = hrtime(true);
        for ($i = 0; $i < $iterations; ++$i) $operation();
        $samples[] = (hrtime(true) - $start) / $iterations / 1000;
    }
    sort($samples);
    return round($samples[5], 2);
}

$times = ['body_us' => median_microseconds(fn() => $worker->function_body($body, $symbols), 200)];
// The same script can measure a compiler checkout from before terminal transfer.
if (class_exists(Last_Use_Transfer::class)) {
    $times['candidate_us'] = median_microseconds(fn() => Last_Use_Transfer::prepare($body), 2000);
    $main = $program->files["main.$suffix"]->main()->body();
    $times['screen_us'] = median_microseconds(fn() => Last_Use_Transfer::prepare($main), 10000);
}
if (class_exists(Scoped_Byte_View::class)) {
    $times['view_candidate_us'] = median_microseconds(fn() => Scoped_Byte_View::prepare($body), 2000);
    $times['view_screen_us'] = median_microseconds(fn() => Scoped_Byte_View::prepare($main), 10000);
}
echo json_encode($times), "\n";
