<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function artifact_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

// These are backend transport/build fixtures, not a claim of multi-source lowering.
$artifacts /** Storage<cpp_artifact> */ = new Storage();
$header = new cpp_artifact('callable:answer', cpp_artifact_kind::header,
	'callables/answer.hpp', "#pragma once\nint answer();\n");
$implementation = new cpp_artifact('implementation:library', cpp_artifact_kind::translation_unit,
	'units/library.cpp', "#include \"callables/answer.hpp\"\nint answer() { return 42; }\n");
$entry = new cpp_artifact('entry', cpp_artifact_kind::translation_unit,
	'main.cpp', "#include \"callables/answer.hpp\"\nint main() { return answer(); }\n");
$artifacts->append($header);
$artifacts->append($implementation);
$artifacts->append($entry);
$encoded = CPP_Output::encode($artifacts);
$decoded = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);
artifact_check(count($decoded['artifacts']) === 3, 'Transport dropped artifacts');
artifact_check($decoded['artifacts'][1]['text'] === $implementation->text, 'Transport changed final bytes');
artifact_check($decoded['artifacts'][0]['kind'] === 'header', 'Header became a translation unit');
artifact_check(CPP_Output::encode($artifacts) === $encoded, 'Repeated transport changed output');
$rejected = false;
try { CPP_Output::single_unit($artifacts); }
catch (\LogicException $error) { $rejected = true; }
artifact_check($rejected, 'Single-unit adapter silently discarded artifacts');

$only_header /** Storage<cpp_artifact> */ = new Storage();
$only_header->append($header);
$rejected = false;
try { CPP_Output::single_unit($only_header); }
catch (\LogicException $error) { $rejected = true; }
artifact_check($rejected, 'Single-unit adapter accepted a header');

foreach ([new cpp_artifact('entry', cpp_artifact_kind::translation_unit, 'other.cpp', ''),
	new cpp_artifact('other', cpp_artifact_kind::translation_unit, 'main.cpp', '')] as $duplicate)
{
	$conflict /** Storage<cpp_artifact> */ = new Storage();
	$conflict->append($entry);
	$conflict->append($duplicate);
	$rejected = false;
	try { CPP_Output::encode($conflict); }
	catch (\LogicException $error) { $rejected = true; }
	artifact_check($rejected, 'Duplicate identity/path escaped output validation');
}

// Strings survive escaping, including UTF-8, newlines and embedded zero bytes.
$quoted /** Storage<cpp_artifact> */ = new Storage();
$quoted->append(new cpp_artifact('quoted', cpp_artifact_kind::translation_unit,
	'quoted.cpp', "// é \\\"\n\0"));
$roundtrip = json_decode(CPP_Output::encode($quoted), true, 512, JSON_THROW_ON_ERROR);
artifact_check($roundtrip['artifacts'][0]['text'] === $quoted[0]->text, 'Transport corrupted escaped text');

if (isset($argv[1])) { file_put_contents($argv[1], $encoded); }
echo "C++ artifacts: identity, kinds, complete transport, duplicate rejection and single-unit guards passed\n";
