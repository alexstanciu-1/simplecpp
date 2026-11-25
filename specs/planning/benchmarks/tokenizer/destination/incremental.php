<?php
declare(strict_types=1);
use scpp\compiler\{Destination_Trial, Incremental_Project, entry_target};

[$script, $boot, $work, $language] = $argv;
require $boot;
require __DIR__ . '/plan.php';
$app = "$work/edits-$language";
mkdir($app);
file_put_contents("$app/module.json", json_encode(['entry'=>"main.$language"]));
$path = "$app/scanner.$language";
$source = file_get_contents(dirname(__DIR__) . "/scanner.$language");
$signature = $language === 'cpp' ? 'Scan scan(' : 'function scan(';
$split = strpos($source, $signature);
$records = substr($source, 0, $split);
$source = substr($source, $split);
$layout = "$app/records.$language";
file_put_contents($layout,$records);
file_put_contents($path, $source);
file_put_contents("$app/main.$language", 'return 8;');
$project = new Incremental_Project($app,"$app-out",new entry_target(31));
$project->run();
$key = basename($app) . "/scanner.$language";
$file = $project->session()->files()[$key];
$plan = Destination_Trial::discover($file->functions()[0]->body(), true);
if (!$plan->append) throw new RuntimeException('Initial proof failed');
file_put_contents("$app/main.$language", 'return 10;');
$report = $project->run();
$file = $project->session()->files()[$key];
if (!$plan->current($file,true) || in_array($key,$report['emitted_sources'],true))
    throw new RuntimeException('Unrelated entry edit retired scanner');
$original = $language === 'php' ? '$item->offset=$start;' : ($language === 'js' ? 'item.offset=start;' : 'item->offset=start;');
$changed = str_replace($original, '', $source, $count);
if ($count !== 1) throw new RuntimeException('Missing input');
foreach ([$changed, $source] as $index=>$text) {
    file_put_contents($path,$text);
    $project->run();
    $file = $project->session()->files()[$key];
    if ($plan->current($file,true)) throw new RuntimeException('Stale plan accepted');
    $plan = Destination_Trial::discover($file->functions()[0]->body(),true);
    if (($plan->append !== null) !== ($index === 1)) throw new RuntimeException('Unexpected recovered proof');
}

// A layout edit keeps the scanner text identical but invalidates its dependency.
$extra = ['cpp'=>'int spare;', 'php'=>'public int $spare;', 'js'=>'spare:int;'][$language];
foreach ([str_replace('struct Token {', 'struct Token {'.$extra, $records), $records] as $index=>$text) {
    file_put_contents($layout,$text);
    $report = $project->run();
    $file = $project->session()->files()[$key];
    if ($plan->current($file,true)) throw new RuntimeException('Layout edit reused old proof');
    $plan = Destination_Trial::discover($file->functions()[0]->body(),true);
    if (($plan->append !== null) !== ($index === 1)) throw new RuntimeException('Layout recovery mismatch');
}
