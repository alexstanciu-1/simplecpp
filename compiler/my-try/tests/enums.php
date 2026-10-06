<?php
namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';

function enum_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Independent in-memory sources exercise the normal coordinator and artifact pipeline. */
function enum_project(array $sources): Compiler
{
	Compiler_Lifecycle::reset();
	$module = new module('/enums', '/enums', '/enums');
	foreach ($sources as $path => $text) {
		$file = new file();
		$file->path = $path;
		$file->content = $text;
		Source_Registry::add($module, $file);
	}
	Model::$modules->add($module->name, $module);
	$compiler = new Compiler();
	$compiler->select_entry('main', 'main.phs');
	return $compiler;
}

function enum_edit(Compiler $compiler, source_record $source, string $text): void
{
	$source->file->content = $text;
	$source->file->changes = SYNC_CHANGED;
	$source->changes = change_state::changed;
	$compiler->tokenize();
	$compiler->parse();
}

/** A rejection must come from shared preparation, before any C++ attempt. */
function enum_failure(Compiler $compiler, string $message): void
{
	$failed = false;
	try {
		$compiler->prepare(true);
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), $message);
		if (!$failed) { throw $error; }
	}
	enum_check($failed, 'Missing preparation rejection: ' . $message);
}

$fixtures = [
	'unit' => ['main.phs' => 'enum Suit { case Hearts; case Spades; } function copy(Suit $v): Suit { return $v; } function read(Suit $v): int { return 7; } $a = Suit::Spades; $b value<Suit> = copy($a); $a = Suit::Hearts; return read($b);'],
	'compact' => ['main.phs' => 'enum Status: int { case Ok = 200; case Missing = 404; } enum Signed: int { case Low = -129; case High = 127; } $status Status = Status::Missing; $signed Signed = Signed::Low; return 7;'],
	'fixed' => ['main.phs' => 'enum U16: uint16 { case A = 1; } enum I64: int64 { case Min = -9223372036854775808; } enum U64: uint64 { case Max = 18446744073709551615; } enum Byte: byte { case Max = 255; } $v U64 = U64::Max; return 7;'],
	'cross_file' => ['main.phs' => 'namespace App; use Lib\State as S; function roundtrip(S $value): S { return $value; } $v S = roundtrip(S::Ready); $w \Lib\State = \Lib\State::Busy; return 7;',
		'lib.phs' => 'namespace Lib; enum State: uint32 { case Ready = 1; case Busy = 2; }'],
	'reference' => ['main.phs' => 'enum State { case A; case B; } function change(State &$v): void { $v = State::B; } function &ref(State &$v): State { return $v; } $v = State::A; $alias State =& $v; change($alias); $copy = ref($v); return 7;'],
];
$many = 'enum Many {';
$fixtures['equality'] = ['main.phs' => 'enum E { case A; case B; } $a = E::A; $b = E::B; if ($a == E::A && $a === E::A && $a != $b && $a !== $b) { if ($a == $b || $a !== E::A) { return 1; } return 7; } return 2;'];
$fixtures['selection'] = ['main.phs' => 'enum E { case A; case B; case C; } function select(int &$calls): E { $calls += 1; return E::B; } function result(int &$calls): int { $calls += 10; return 7; } $calls = 0; $value = match (select($calls)) { default => result($calls), E::A => result($calls), E::B, E::C => 7, E::B => result($calls), }; if ($calls !== 1) { return 1; } switch (select($calls)) { case E::A: return 2; case E::B: $calls += 1; case E::C: $calls += 1; break; default: return 3; } switch (E::A) { case E::B: return 4; } if ($calls !== 4) { return 5; } return $value;'];
$fixtures['enum_extremes'] = ['main.phs' => 'enum U: uint64 { case Max = 18446744073709551615; case Zero = 0; } enum S: int64 { case Min = -9223372036854775808; case Zero = 0; } $u = U::Max; $s = S::Min; if ($u !== U::Max || $s != S::Min) { return 1; } switch ($u) { case U::Zero: return 2; case U::Max: break; } return match ($s) { S::Min => 7, S::Zero => 3 };'];
$fixtures['enum_import_selection'] = ['main.phs' => 'namespace App; use Lib\E as Alias; $v = Alias::A; if ($v !== \Lib\E::A) { return 1; } switch ($v) { case \Lib\E::A: break; default: return 2; } return match ($v) { Alias::A => 7 };', 'lib.phs' => 'namespace Lib; enum E { case A; }'];
$fixtures['enum_unhandled'] = ['main.phs' => 'enum E { case A; case B; } return match (E::B) { E::A => 7 };'];
$fixtures['enum_joins'] = ['main.phs' => 'enum E { case A; case B; } function choose(bool $b, E $a, E $z): E { return $b ? $a : $z; } function accept(E $v): int { if ($v === E::B) { return 7; } return 1; } $a = E::A; $b = E::B; $copy = true ? $a : $b; $a = E::B; if ($copy !== E::A) { return 2; } $m = match ($a) { E::A => $a, E::B => $b }; $b = E::A; if ($m !== E::B) { return 3; } return accept(choose(false, $b, match (1) { 1 => E::B, default => E::A }));'];
$fixtures['enum_join_lazy'] = ['main.phs' => 'enum E { case A; case B; } function condition(int &$n): bool { $n += 1; return false; } function branch(int &$n, E $v): E { $n += 10; return $v; } $n = 0; $v = condition($n) ? branch($n, E::A) : branch($n, E::B); if ($n !== 11 || $v !== E::B) { return 1; } $m = match ($v) { default => branch($n, E::A), E::B => branch($n, E::B), E::B => branch($n, E::A) }; if ($n !== 21 || $m !== E::B) { return 2; } $z = match (0) { 1 => E::A, default => (true ? E::B : E::A) }; if ($z !== E::B) { return 3; } return 7;'];
$fixtures['enum_join_import'] = ['main.phs' => 'namespace App; use Lib\E as Alias; function choose(bool $b): Alias { return $b ? Alias::A : \Lib\E::B; } $v value<Alias> = match (0) { 0 => choose(false), default => Alias::A }; if ($v !== Alias::B) { return 1; } return 7;', 'lib.phs' => 'namespace Lib; enum E: uint64 { case A = 0; case B = 18446744073709551615; }'];
$fixtures['enum_defaults'] = ['main.phs' => 'enum E { case A; case B; } function choose(E $v = E::B): E { return $v; } $a = choose(); $b = choose(E::A); if ($a !== E::B || $b !== E::A) { return 1; } return 7;'];
$fixtures['enum_defaults_import'] = ['main.phs' => 'namespace App; use function Lib\choose; use Lib\E as Alias; $v = choose(); if ($v !== Alias::High) { return 1; } return 7;', 'lib.phs' => 'namespace Lib; function choose(E $v = E::High): E { return $v; } enum E: uint64 { case High = 18446744073709551615; }'];
for ($index = 0; $index < 257; $index++) {
	$many .= 'case C' . $index . ';';
}
$fixtures['wide_unit'] = ['main.phs' => $many . '} $v = Many::C256; return 7;'];
$outputs = [];
foreach ($fixtures as $name => $sources) {
	$compiler = enum_project($sources);
	$compiler->exec_cpp();
	$outputs[$name] = CPP_Output::encode(Model::$cpp_files);
}

foreach ([
	'$v = E::A == F::A;' => 'same enum type',
	'$v = E::A === 0;' => 'same enum type',
	'$v = 0 != E::A;' => 'same enum type',
	'$v = E::A < E::B;' => 'only equality',
	'$v = E::A + E::B;' => 'only equality',
	'switch (E::A) { case 0: break; }' => 'selector enum type',
	'switch (E::A) { case F::A: break; }' => 'selector enum type',
	'switch (0) { case E::A: break; }' => 'requires canonical int',
	'switch (E::A) { case E::A: break; case E::A: break; }' => 'duplicate case value',
	'$a = E::A; switch ($a) { case $a: break; }' => 'constant enum case',
	'return match (E::A) { F::A => 7 };' => 'selector enum type',
	'$a = E::A; return match ($a) { $a => 7 };' => 'constant enum case',
	'$v = true ? E::A : F::A;' => 'matching scalar types or the same enum',
	'$v = false ? E::A : 0;' => 'matching scalar types or the same enum',
	'$v = true ? 0 : E::A;' => 'matching scalar types or the same enum',
	'$v = match (0) { 0 => E::A, default => F::A };' => 'matching scalar types or the same enum',
	'$v = match (0) { 0 => E::A, default => 0 };' => 'matching scalar types or the same enum',
	'$v = match (0) { 0 => 0, default => E::A };' => 'matching scalar types or the same enum',
	'$v = true ? E::A : null;' => 'supported scalar value types',
	'$v = E::A ?: E::B;' => 'condition truthiness',
	'if (E::A) {}' => 'condition truthiness',
	'$v = E::A ?? E::B;' => 'coalesce',
	'$a = E::A; $b = E::B; $ref E =& (true ? $a : $b);' => 'stable storage',
	'$a = E::A; $ref E =& match (0) { 0 => $a };' => 'stable storage',
	'function f(E $v = F::A): E { return $v; }' => 'same enum type',
	'function f(E $v = 0): E { return $v; }' => 'constant enum case',
	'function f(E $v = null): E { return $v; }' => 'constant enum case',
	'function f(E $v = E::Missing): E { return $v; }' => 'unknown enum case',
	'function f(E $v = true ? E::A : E::B): E { return $v; }' => 'constant enum case',
	'function f(E &$v = E::A): E { return $v; }' => 'reference parameter defaults',
] as $source => $diagnostic) {
	$compiler = enum_project(['main.phs' => 'enum E { case A; case B; } enum F { case A; } ' . $source]);
	$compiler->tokenize();
	$compiler->parse();
	enum_failure($compiler, $diagnostic);
}

// Omitted calls consume immutable declaration defaults and recover after failed edits.
$default_source = 'enum E { case A; case B; } function f(E $v = E::A): E { return $v; } $v = f(); return 7;';
$compiler = enum_project(['main.phs' => $default_source]);
$compiler->exec_cpp();
$default_file = Model::sources()[0];
$default_function = $default_file->parsed->root->single_body()->declarations[1];
$old_default = $default_function->require_preparation()->signature->parameters[0]->default_value;
$default_output = CPP_Output::encode(Model::$cpp_files);
enum_edit($compiler, $default_file, "\n" . $default_source);
$compiler->prepare(true);
$compiler->cpp();
enum_check($default_function->require_preparation()->signature->parameters[0]->default_value === $old_default, 'No-op replaced enum default');
enum_edit($compiler, $default_file, str_replace('$v = E::A', '$v = E::B', $default_source));
$compiler->prepare(true);
$compiler->cpp();
$new_default = $default_function->require_preparation()->signature->parameters[0]->default_value;
enum_check(!Parameter_Defaults::same($old_default, $new_default), 'Default case edit did not change signature');
enum_check($old_default->value->value_key() === 'A:0', 'Default edit mutated old snapshot');
enum_check(CPP_Output::encode(Model::$cpp_files) !== $default_output, 'Default edit did not update output');
enum_edit($compiler, $default_file, str_replace('$v = E::A', '$v = E::Missing', $default_source));
enum_failure($compiler, 'unknown enum case');
enum_edit($compiler, $default_file, $default_source);
$compiler->prepare(true);
$compiler->cpp();
enum_check(CPP_Output::encode(Model::$cpp_files) === $default_output, 'Default repair changed clean output');

// Known expected widths/values are independent of the storage selector under test.
$widths = [
	'enum E { case A; case B; }' => [8, false, 'B', '1'],
	'enum E: int { case A = 0; case B = 255; }' => [8, false, 'B', '255'],
	'enum E: int { case A = 256; }' => [16, false, 'A', '256'],
	'enum E: int { case A = -128; case B = 127; }' => [8, true, 'A', '-128'],
	'enum E: int { case A = -129; }' => [16, true, 'A', '-129'],
	'enum E: int { case A = 65536; }' => [32, false, 'A', '65536'],
	'enum E: int { case A = 4294967296; }' => [64, false, 'A', '4294967296'],
	'enum E: int { case A = -2147483649; }' => [64, true, 'A', '-2147483649'],
	'enum E: int64 { case A = 1; }' => [64, true, 'A', '1'],
	'enum E: uint64 { case A = 18446744073709551615; }' => [64, false, 'A', '18446744073709551615'],
];
foreach ($widths as $source_text => $expected) {
	$compiler = enum_project(['main.phs' => $source_text . ' return 7;']);
	$compiler->exec_cpp();
	$entry = object_cast(Model::$global_scope->source_types_named('E')[0], collected_enum::class);
	$facts = $entry->enum_syntax()->require_preparation();
	enum_check(($facts->storage->bit_width() === $expected[0]) && ($facts->storage->signed() === $expected[1]), 'Incorrect storage: ' . $source_text);
	enum_check($facts->cases[$expected[2]]->decimal === $expected[3], 'Incorrect exact case value');
}

foreach ([
	'enum E {}' => 'at least one case',
	'enum E { case A; case A; }' => 'duplicate enum case name',
	'enum E: int { case A = 0; case B = -0; }' => 'duplicate enum case value',
	'enum E { case A = 1; }' => 'cannot have values',
	'enum E: int { case A; }' => 'require integer literals',
	'enum E: int { case A = 1 + 2; }' => 'integer literal',
	'const X = 1; enum E: int { case A = X; }' => 'integer literal',
	'enum E: int { case A = -(-1); }' => 'integer literal',
	'enum E: int { case A = 9223372036854775808; }' => 'backing range',
	'enum E: uint8 { case A = -1; }' => 'backing range',
	'enum E: uint8 { case A = 256; }' => 'backing range',
	'enum E: int8 { case A = -129; }' => 'backing range',
	'enum E: uint64 { case A = 18446744073709551616; }' => 'backing range',
	'enum E: string { case A = "x"; }' => 'integer type',
	'enum E { case A; } enum E { case B; }' => 'duplicate enum/type name',
	'struct E { int8 $x; } enum E { case A; }' => 'duplicate enum/type name',
	'enum E { case A; } $v = E::Missing;' => 'unknown enum case',
	'$v = int::A;' => 'enum owner',
	'enum E { case A; } $v E;' => 'explicit initializer',
	'enum E { case A; } struct Box { E $field; }' => 'enum-valued fields',
	'enum E { case A; } $v E = 0;' => 'matching types',
	'enum E { case A; } enum F { case A; } $v E = F::A;' => 'matching types',
] as $source_text => $message) {
	$compiler = enum_project(['main.phs' => $source_text]);
	$compiler->tokenize();
	$compiler->parse();
	enum_failure($compiler, $message);
}

// Retained consumers depend on the enum declaration, not on namespace case constants.
$main_text = 'namespace App; use Lib\E; function get(): E { switch (E::A) { case E::A: return true ? E::A : E::B; default: return match (0) { 0 => E::B }; } } $v = get(); if ($v !== E::A) { return 1; } return match ($v) { E::A => 7, default => 2 };';
$lib_text = 'namespace Lib; enum E: int { case A = 1; case B = 2; }';
$compiler = enum_project(['main.phs' => $main_text, 'lib.phs' => $lib_text]);
$compiler->exec_cpp();
$lib_source = Model::sources()[1];
$enum_syntax = object_cast($lib_source->parsed->root->single_body()->declarations[0], enum_node::class);
$entry = object_cast($enum_syntax->occurrence(), collected_enum::class);
$work = $entry->preparation;
$facts = $enum_syntax->require_preparation();
$case_syntax = $enum_syntax->cases[0];
$version = $work->version;
$body = Model::sources()[0]->parsed->root->single_body()->declarations[0]->body;
$body_version = $body->work()->version;
$before = CPP_Output::encode(Model::$cpp_files);
enum_edit($compiler, $lib_source, "\n" . $lib_text);
$compiler->prepare(true);
$compiler->cpp();
enum_check(($entry->enum_syntax() === $enum_syntax) && ($enum_syntax->cases[0] === $case_syntax), 'No-op parse replaced enum/case syntax');
enum_check(($enum_syntax->require_preparation() === $facts) && ($work->version === $version), 'No-op rebuilt enum facts');
enum_check(CPP_Output::encode(Model::$cpp_files) === $before, 'No-op changed artifacts');
enum_edit($compiler, $lib_source, 'namespace Lib; enum E: int { case A = 300; case B = 2; }');
$compiler->prepare(true);
$compiler->cpp();
enum_check(($work->version > $version) && ($body->work()->version > $body_version), 'Case/storage edit did not invalidate consumers');
enum_check($enum_syntax->require_preparation()->storage->bit_width() === 16, 'Edited storage width stayed stale');
enum_check($facts->cases['A']->decimal === '1', 'Edit mutated an earlier published snapshot');
enum_edit($compiler, $lib_source, 'namespace Lib; enum E: int { case B = 2; }');
enum_failure($compiler, 'unknown enum case');
enum_edit($compiler, $lib_source, $lib_text);
$compiler->prepare(true);
$compiler->cpp();
enum_check($enum_syntax->require_preparation()->cases['A']->decimal === '1', 'Failed lookup did not recover');
enum_edit($compiler, $lib_source, 'namespace Lib; enum E: uint8 { case A = 300; }');
enum_failure($compiler, 'backing range');
enum_edit($compiler, $lib_source, $lib_text);
$compiler->prepare(true);
$compiler->cpp();
enum_check($work->failed === false, 'Failed enum work did not recover');
Token_Cleanup::file($lib_source->parsed);
enum_edit($compiler, $lib_source, $lib_text);
$compiler->prepare(true);
$compiler->cpp();
enum_check(CPP_Output::encode(Model::$cpp_files) === $before, 'Cleanup/recovery changed output');

// A backing policy edit matters even when the emitted width and case values stay identical.
$version = $work->version;
enum_edit($compiler, $lib_source, 'namespace Lib; enum E: uint8 { case A = 1; case B = 2; }');
$compiler->prepare(true);
$compiler->cpp();
enum_check($work->version > $version, 'Compact versus fixed backing edit was lost');
enum_check($enum_syntax->require_preparation()->backing_type->matches(
	Model::$type_catalog->registry()->use(Model::$type_catalog->canonical('uint8')->type_id())), 'Resolved backing identity was not retained');

// Successful deletion retires the common type association and informs live consumers.
enum_edit($compiler, $lib_source, 'namespace Lib;');
enum_failure($compiler, 'Missing import target');
enum_check($work->change_status === change_state::deleted, 'Enum work was not retired');
enum_check(Model::$type_catalog->source_declarations()->count() === 0, 'Enum source association survived deletion');
enum_edit($compiler, $lib_source, $lib_text);
$compiler->prepare(true);
$compiler->cpp();
enum_check(Model::$type_catalog->source_declarations()->count() === 1, 'Reintroduced enum was not registered');

// Early collection survives an incomplete declaration and gets reused on repair.
$compiler = enum_project(['main.phs' => 'enum Broken: int { case A =']);
$compiler->tokenize();
$parse_failed = false;
try {
	$compiler->parse();
}
catch (\RuntimeException $error) {
	$parse_failed = true;
}
enum_check($parse_failed, 'Incomplete enum unexpectedly parsed');
$partial = Model::$global_scope->source_types_named('Broken')[0];
enum_edit($compiler, Model::sources()[0], 'enum Broken: int { case A = 1; } $v = Broken::A; return 7;');
$compiler->prepare(true);
$compiler->cpp();
enum_check(Model::$global_scope->source_types_named('Broken')[0] === $partial, 'Parser repair replaced partial enum identity');

// Backend traversal must not attach facts or mutate source syntax.
$syntax_before = serialize(Model::syntax_files()[0]->root);
$generated = (new CPP_Generator())->generate_program(Model::$prepared_files, Model::$prepared_entry);
enum_check(serialize(Model::syntax_files()[0]->root) === $syntax_before, 'Enum generation mutated the syntax graph');

if (isset($argv[1])) {
	foreach ($outputs as $name => $payload) {
		file_put_contents($argv[1] . '/' . $name . '.json', $payload);
	}
}
echo "Enums: declarations, exact storage, boundaries, artifacts and retained recovery passed\n";
