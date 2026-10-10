<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function handle_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Compile the unfamiliar provider through ordinary preparation and lowering. */
function handle_compile(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/handles', '/handles', '/handles');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	$compiler = new Compiler();
	$compiler->exec_cpp();
	$text = CPP_Probe::text(Model::$cpp_files);
	$compiler->exec_cpp();
	handle_check($text === CPP_Probe::text(Model::$cpp_files), 'Handle no-op mismatch');
	return $text;
}

$directory = $argv[1];
$text = file_get_contents(__DIR__ . '/runtime_handles.json');
$input = new runtime_catalog_input();
$core = Runtime_Catalog_Loader::load(dirname(__DIR__, 3) . '/runtime/metadata/modules', ['core']);
$input->modules->add('core', $core->modules['core']);
$input->modules->add('handles', Runtime_Catalog_Reader::decode($text, 'handles.json'));
Compiler_Lifecycle::configure_runtime($input);
// Host fixture mutation tests schema validation without a second metadata decoder.
$schema = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
$schema['types'][0]['storage'] = 'borrowed';
$failed = false;
try {
	Runtime_Catalog_Reader::decode(json_encode($schema), 'invalid.json');
}
catch (\Throwable $error) {
	$failed = str_contains($error->getMessage(), 'Unsupported runtime storage');
}
handle_check($failed, 'Unknown storage policy accepted');
foreach (['duplicate', 'owner', 'property_duplicate', 'property_type', 'property_binding'] as $invalid_kind)
{
	$candidate_schema = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
	if ($invalid_kind === 'duplicate') {
		$candidate_schema['constants'][] = $candidate_schema['constants'][0];
	}
	elseif ($invalid_kind === 'owner') {
		$candidate_schema['constants'][0]['owner'] = 'int';
	}
	elseif ($invalid_kind === 'property_duplicate') {
		$candidate_schema['properties'][] = $candidate_schema['properties'][0];
	}
	elseif ($invalid_kind === 'property_type') {
		$candidate_schema['properties'][0]['type'] = 'void';
	}
	else {
		$candidate_schema['cpp_properties'][0]['symbol'] = 'not::a_field';
	}
	$candidate = new runtime_catalog_input();
	$candidate->modules->add('core', $core->modules['core']);
	$candidate->modules->add('handles', Runtime_Catalog_Reader::decode(json_encode($candidate_schema), 'bad-members.json'));
	$failed = false;
	try {
		Compiler_Lifecycle::configure_runtime($candidate);
	}
	catch (\RuntimeException $error) {
		$failed = true;
	}
	handle_check($failed && (Model::$runtime_metadata === $input), 'Invalid member constant configuration was published');
}
$cases = [
	'properties' => <<<'PHS'
struct Plain { public int $value = 3; }
function update_plain(int &$value): void { $value = 9; }
$plain = new Plain();
$plain->value = 7;
update_plain($plain->value);
$a = ticket::open(7);
$copy = $a;
$a->value = 23;
ticket::$total = 31;
$empty ticket = null;
$empty::$total = 43;
echo $copy->value, "|", $a->read, "|", $a->read(), "|", ticket::read, "|", ticket::$total, "|", $plain->value;
return 0;
PHS,
	'constants' => <<<'PHS'
const COPY = ticket::LIMIT;
function qualifier(int &$calls): ticket {
    $calls++;
    $empty ticket = null;
    return $empty;
}
$calls = 0;
echo COPY, "|", ticket::read, "|", LIMIT, "|", qualifier($calls)::LIMIT, "|", $calls;
return 0;
PHS,
	'members' => <<<'PHS'
function replacement(ticket &$slot): int {
    $slot = ticket::open(99);
    return 23;
}
$a = ticket::open(7);
$copy = $a;
$a->change(replacement($a));
echo $copy->read(), "|", $a->read(), "|", $a->echo_value("yes"), "|", $a->echo_value(42), "|";
$empty ticket = null;
$b = $empty::open(31);
echo $b->read(), "|", $b->read_other(ticket::open(51));
return 0;
PHS,
	'identity' => <<<'PHS'
$a = open_ticket(7);
$b = $a;
ticket_write($b, 9);
echo ticket_read($a), "|";
ticket_clear($a);
if (isset($a)) { return 1; }
if (!$b) { return 2; }
echo ticket_read($b), "|";
$c ticket = null;
$c = $b;
$alias =& $c;
ticket_clear($alias);
if ($c !== null) { return 3; }
echo ticket_read($b);
return 0;
PHS,
	'result' => <<<'PHS'
function wrap(ticket $value): result<ticket> { return $value; }
$a = open_ticket(11);
$r = wrap($a);
$b ticket = $r;
ticket_write($b, 13);
echo ticket_read($a), "|";
$copy = $r;
$out ticket = null;
$error error;
if (!take($out, $error, $copy)) { return 1; }
echo ticket_read($out);
return 0;
PHS,
	'container' => <<<'PHS'
$a = open_ticket(17);
$values vector<ticket> = [$a];
$copy = $values;
$b = $copy[0];
ticket_write($b, 19);
echo ticket_read($a);
return 0;
PHS,
];
foreach ($cases as $name => $source) {
	file_put_contents($directory . '/' . $name . '.cpp', handle_compile($source));
}
$null_source = 'function argument(): int { echo "unexpected"; return 1; } $a ticket = null; $a->change(argument()); return 91;';
$null_cpp = str_replace('int main(int argc, char** argv)', 'int observed_entry(int argc = 0, char** argv = nullptr)',
	handle_compile($null_source));
$null_cpp .= "\nint main() { try { observed_entry(); } catch (const scpp::runtime_error&) { return 0; } return 92; }\n";
file_put_contents($directory . '/null_receiver.cpp', $null_cpp);
foreach ([
	'$a = new ticket();',
	'$a = ticket::open(1); $a->read = 2;',
	'$a = ticket::open(1); $alias =& $a->value;',
	'function update(int &$v): void { $v = 2; } $a = ticket::open(1); update($a->value);',
	'$a = ticket::open(1); $a->value = "wrong";',
	'$a = ticket::open(1); echo $a->total;',
	'echo ticket::$value;',
	'$a = ticket::open(1); echo $a->missing;',
	'echo ticket::MISSING;',
	'echo read;',
	'ticket::LIMIT = 2;',
	'$a = ticket::read();',
	'$a = ticket::open(1); $a->open(2);',
	'$a = ticket::open(1); read();',
	'$a = ticket::open(1); $a->missing();',
	'$a value<ticket>;',
	'$a shared<ticket>;',
	'$a unique<ticket>;',
	'$a weak<ticket>;',
	'$a = open_ticket(1); ticket_clear(open_ticket(2));',
] as $source)
{
	$failed = false;
	try {
		handle_compile($source);
	}
	catch (\Throwable $error) {
		$failed = true;
	}
	handle_check($failed && Model::$cpp_files->is_empty(), 'Opaque payload operation admitted: ' . $source);
}
handle_check(handle_compile($cases['identity']) !== '', 'Handle recovery failed');
$old = Model::$type_catalog->definition('ticket');
handle_check(!$old->declares_capability(generic_contract::copyable_value),
	'Handle copying incorrectly declared payload copying');
handle_check(Model::$type_catalog->registry()->has_capability(Model::$type_catalog->registry()->use(
	Model::$type_catalog->canonical('ticket')->type_id()), generic_contract::copyable_value),
	'Registered handle is not copyable');
Compiler_Lifecycle::reset_syntax();
handle_check((Model::$runtime_metadata === $input) && (Model::$type_catalog->definition('ticket') !== $old),
	'Handle reset did not rebuild identities from retained input');
echo "Runtime handles passed\n";
