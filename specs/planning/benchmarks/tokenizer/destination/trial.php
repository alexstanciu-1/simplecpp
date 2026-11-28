<?php
declare(strict_types=1);
use scpp\compiler\{Cpp_Program, Destination_Trial, Program_Frontend, Preparation, Symbol_Publication, entry_target, flavor, source_file};
[$script, $boot, $out, $language, $case] = $argv;
require $boot;
$suffix = $language === 'php' ? 'phs' : $language;
require __DIR__ . '/plan.php';
$f = flavor::from($language);
$dir = dirname(__DIR__);
$source = file_get_contents("$dir/scanner.$suffix");
if ($case === 'second') {
    // A distinct record layout: two fields, omitting token kind from publication.
    $source = preg_replace('/^.*(?:int \$?kind;|kind:int;|item(?:->|\.)kind=).*\n/m', '', $source);
    $source = str_replace(['Token', 'offset', 'length'], ['Span', 'begin', 'width'], $source);
}
if ($case === 'live') {
    $read = ['cpp'=>'echo item->kind;', 'php'=>'echo $item->kind;', 'js'=>'echo item.kind;'][$language];
    $source = preg_replace('/(items\[\]=\$?item;)/', '$1' . $read, $source);
}
$path = "scanner.$suffix";
$main = ['cpp'=>'auto r=scan("if ===",0);echo count(r->tokens);',
    'php'=>'$r=scan("if ===",0);echo count($r->tokens);','js'=>'let r=scan("if ===",0);echo count(r.tokens);'][$language];
$entry = new entry_target(31);
$program = (new Program_Frontend())->prepare_sources("main.$suffix", [
    $path=>new source_file($path,$source,$f), "main.$suffix"=>new source_file("main.$suffix",$main.'return 8;',$f)
], $entry);
$file = $program->files[$path];
$body = $file->functions()[0]->body();
$baseline = Cpp_Program::emit($program);
$plan = Destination_Trial::discover($body, true);
if (($plan->append !== null) !== ($case !== 'live') || !$plan->current($file,true)
    || $plan->current($file,false) || Destination_Trial::discover($body,false)->append !== null) throw new RuntimeException("Unexpected plan: $language $case");
function emit_trial($program, $plan, $path) {
    if (!$plan->current($program->files[$path], true)) throw new LogicException('Stale plan');
    Destination_Trial::$selected = $plan;
    try { $output = Cpp_Program::emit($program); }
    finally { Destination_Trial::$selected = null; }
    $files = $output->files;
    if ($plan->append === null) return $files;
    // Supply the experimental concrete binding in the shared collection header so every TU sees one definition.
    $site = $plan->append;
    $vector = $site->receiver->type_def()->cpp_type();
    $record = $site->value->type_def()->cpp_type();
    $signature = "inline void $vector" . "::append($record value)";
    $decl = "void append($record value);";
    $args = $values = [];
    foreach ($plan->writes as $i=>$write) {
        $args[] = $write->target->type_def()->cpp_type() . " f$i";
        $values[] = "f$i";
    }
    $params = implode(', ', $args);
    $init = implode(', ', $values);
    $helper = <<<CPP
inline void $vector::append_fields($params) {
    if (storage_ && storage_->count < storage_->capacity) {
        ::new (storage_->data + storage_->count * sizeof($record)) $record{{$init}};
        ++storage_->count;
        return;
    }
    append({{$init}});
}

CPP;
    $matches = 0;
    foreach ($output->source_adapters[$path] as $header) {
        $text = $files[$header];
        if (!str_contains($text,$signature)) continue;
        if (substr_count($text,$signature)!==1 || substr_count($text,$decl)!==1) throw new LogicException('Unexpected provider recipe');
        $files[$header] = str_replace([$signature,$decl], [$helper.$signature,$decl."\n    void append_fields($params);"], $text);
        ++$matches;
    }
    if ($matches!==1) throw new LogicException('Expected one provider header');
    return $files;
}
$optimized = emit_trial($program, $plan, $path);
$unit = $baseline->source_units[$path];
if (str_contains($optimized[$unit], '.append_fields(') !== ($case !== 'live')
    || ($case === 'live' && $optimized !== $baseline->files)) throw new RuntimeException('Fallback mismatch');
foreach (['ordinary'=>$baseline->files,'selected'=>$optimized] as $name=>$output) {
    foreach ($output as $dest=>$text) {
        $target = "$out/$name/$dest";
        if (!is_dir(dirname($target))) mkdir(dirname($target),0777,true);
        file_put_contents($target,$text);
    }
}
function batch_us(Closure $op, int $count): float {
    $start=hrtime(true);
    for ($i=0;$i<$count;++$i) $op();
    return (hrtime(true)-$start)/$count/1000;
}
$series=[];
$ops=['ordinary_emit_us'=>fn()=>Cpp_Program::emit($program),
    'planned_emit_us'=>fn()=>emit_trial($program,$plan,$path)];
for ($batch=0;$batch<12;++$batch) {
    $series['proof_us'][]=batch_us(fn()=>Destination_Trial::discover($body,true),100);
    foreach ($batch%2 ? array_reverse($ops,true) : $ops as $key=>$op) $series[$key][]=batch_us($op,20);
}
$symbols=Symbol_Publication::completed($program->files);
$worker=new Preparation();
for ($batch=0;$batch<12;++$batch)
    $series['ordinary_prepare_us'][]=batch_us(fn()=>$worker->function_body($body,$symbols),20);
echo json_encode($series),"\n";
