<?php

declare(strict_types=1);
namespace scpp\compiler;
require_once __DIR__ . '/../boot.php';

// The native consumer requests a field loop directly while source import syntax is undecided.
$schema = <<<'SCPP'
data struct Packet {
    public vector<int> $data;
    public int $id;
    public int $version=7;
    public static int $drops=0;
    public function __destruct() { self::$drops++; }
}
data struct Envelope { public Packet $packet; }
function exportMixed(Packet $p):mixed { return (mixed)$p; }
function exportDynamic(Packet $p):dynamic { return (dynamic)$p; }
return 0;
SCPP;
$frontend = new Source_Frontend();
$file = $frontend->parse(new source_file('packet.php', $schema, flavor::php));
$files = ['packet.php' => $file];
$symbols = Symbol_Publication::completed($files);
$program = Program_Preparation::prepare('packet.php', $files, new entry_target(31), $symbols);
$record = array_find($file->declaration_nodes, static fn($node) => $node instanceof ast_record_declaration && $node->short_name === 'Envelope');
$seen = [];
Preparation::check_decode_schema($record->definition, $record, $symbols, $program->dependencies, $file->main()->body(), $seen);
echo (new Cpp_Emitter())->entry_body($program->entry);
