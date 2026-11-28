<?php
declare(strict_types=1);

use scpp\compiler\{Cpp_Emitter, Last_Use_Transfer, Preparation, Program_Frontend, Symbol_Publication, entry_target, flavor, source_file};

// One resident benchmark worker. The driver interleaves batches with a control worker.
[$script, $boot, $fixtures, $language, $case, $mode] = $argv;
require $boot;
$suffix = $language === 'php' ? 'phs' : $language;
$flavor = flavor::from($language);
$text = $language === 'php' ? '$text' : 'text';
$source = file_get_contents("$fixtures/scanner.$suffix");
$assignment = 'source=' . $text . ';';
if (substr_count($source, $assignment) !== 1) throw new RuntimeException('Scanner source assignment changed');
$source = match ($case) {
    'move' => $source,
    'miss' => str_replace($assignment, 'source="if";', $source),
    'live' => str_replace($assignment, $assignment . 'echo ' . $text . ';', $source),
};
$entry = new entry_target(31);
$path = "scanner.$suffix";
$program = (new Program_Frontend())->prepare_sources("main.$suffix", [
    $path => new source_file($path, $source, $flavor),
    "main.$suffix" => new source_file("main.$suffix", 'return 8;', $flavor),
], $entry);
$symbols = Symbol_Publication::completed($program->files);
$file = $program->files[$path];
$body = $file->functions()[0]->body();
$worker = new Preparation();
$code = (new Cpp_Emitter())->source_definitions($file);
$needle = match (true) {
    $case === 'miss' => '.source = scpp::string{',
    $case === 'move' && $mode === 'enabled' => '.source = std::move(var_text_',
    default => '.source = var_text_',
};
if (!str_contains($code, $needle) || !str_contains($code, '.tokens = std::move(var_items_')
    || !str_contains($code, 'scpp_byte_view.read(')) throw new RuntimeException($code);
$proof = Closure::bind(static fn() => Last_Use_Transfer::parameter_assignments($body), null, Last_Use_Transfer::class);
$prepare = static fn() => $worker->function_body($body, $symbols);
echo json_encode(['php' => PHP_VERSION, 'control_sha256' => hash('sha256', preg_replace('~(\.source = )std\::move\((var_text_[0-9]+)\)~', '\1\2', $code))]), "\n";
while (($request = fgets(STDIN)) !== false) {
    [$kind, $iterations] = explode(' ', trim($request));
    $op = $kind === 'proof' ? $proof : $prepare;
    $count = (int)$iterations;
    $start = hrtime(true);
    for ($i = 0; $i < $count; ++$i) $op();
    echo (hrtime(true) - $start) / $count / 1000, "\n";
}
