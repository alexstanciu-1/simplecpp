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
$cases = [
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
