<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

/** Model proof deliberately supplies namespace scopes; source namespace syntax is a later checkpoint. */
function namespace_identity_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

Compiler_Lifecycle::reset();
$registry = Model::$namespaces;
$global = $registry->register('');
$alpha = $registry->register('A');
$beta = $registry->register('B');
$nested = $registry->register('A\Nested');
namespace_identity_check($global->published_scope() === Model::$global_scope, 'Global scope has a second owner');
namespace_identity_check($registry->register('A') === $alpha, 'Namespace interning changed identity');
namespace_identity_check($nested->published_scope()->parent_scope() === Model::$language_scope, 'Namespace ancestry leaked into lexical lookup');
namespace_identity_check($registry->find('Missing') === null, 'Read-only lookup created a namespace');
foreach (["\\A", "A\\", "A\\\\B", '3bad'] as $invalid)
{
	$failed = false;
	try {
		$registry->register($invalid);
	}
	catch (\InvalidArgumentException $error) {
		$failed = true;
	}
	namespace_identity_check($failed, 'Invalid canonical namespace accepted');
}

$module = new module('/namespace-proof', '/namespace-proof', '/namespace-proof');
Model::$modules->add($module->name, $module);
$prepared = new Storage();
$parsed = [];
foreach ([
	['main.phs', $alpha, 'struct Box { int $value; } function answer(): int { return 7; } $box Box; return answer();'],
	['other.phs', $beta, 'struct Box { string $value; } function answer(): int { return 11; }'],
] as [$path, $identity, $text])
{
	$file = new file();
	$file->path = $path;
	$file->content = $text;
	Source_Registry::add($module, $file);
	$tokens = (new Tokenizer($file))->tokenize('/namespace-proof/' . $path);
	$local = new scope();
	$local->set_parent($identity->published_scope());
	$syntax = (new Parser($tokens, $local))->parse();
	Scope_Publication::publish($local, $identity->published_scope());
	$parsed[] = $syntax;
	$prepared->append((new File_Preparation($syntax->collection, Model::$language_scope))->prepare());
}
$alpha_box = Scope_Lookup::types($alpha->published_scope(), 'Box')[0];
$beta_box = Scope_Lookup::types($beta->published_scope(), 'Box')[0];
namespace_identity_check($alpha_box->name() === 'A\Box' && $beta_box->name() === 'B\Box', 'Canonical type names lost their namespace');
namespace_identity_check($alpha_box->definition_id() !== $beta_box->definition_id(), 'Same short names merged types');
namespace_identity_check(count(Scope_Lookup::functions($nested->published_scope(), 'answer')) === 0, 'Parent namespace fallback leaked');
namespace_identity_check(count(Scope_Lookup::functions(Model::$global_scope, 'answer')) === 0, 'Namespace members leaked globally');
namespace_identity_check(count(Scope_Lookup::types($nested->published_scope(), 'int')) === 1, 'Builtins lost language availability');
$artifacts = (new CPP_Generator(null, new cpp_lowering_options(false)))->generate_program($prepared, $prepared[0]);
$identities = [];
foreach ($artifacts as $artifact) {
	namespace_identity_check(!isset($identities[$artifact->identity]), 'Duplicate backend identity');
	$identities[$artifact->identity] = true;
}
foreach (['type:A\Box', 'type:B\Box', 'callable:A\answer', 'callable:B\answer'] as $expected) {
	namespace_identity_check(isset($identities[$expected]), 'Missing qualified artifact ' . $expected);
}
namespace_identity_check(CPP_Generator::source_name('record', 'A\Box') !== CPP_Generator::source_name('record', 'AUqBox'), 'Backend separator encoding collides with identifiers');
if (isset($argv[1])) {
	file_put_contents($argv[1], CPP_Output::encode($artifacts));
}
$old_registry = Model::$namespaces;
Compiler_Lifecycle::reset();
namespace_identity_check(Model::$namespaces !== $old_registry, 'Full reset retained namespace registry');
namespace_identity_check(Model::$namespaces->find('A') === null, 'Full reset retained namespace definitions');
echo "namespace identity checks passed\n";
