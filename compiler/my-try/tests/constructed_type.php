<?php

namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';

function constructed_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Invalid applications must fail before publishing a canonical specialization. */
function constructed_rejects(string $path, string $source, string $message): void
{
	file_put_contents($path, $source);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([dirname($path)]);
	$compiler->tokenize();
	$compiler->parse();
	$failed = false;
	$observed = 'no exception';
	try {
		$compiler->prepare();
	}
	catch (\Exception $error) {
		$observed = $error->getMessage();
		$failed = str_contains($error->getMessage(), $message);
	}
	constructed_check($failed, 'Invalid constructed type did not report its owning validation: ' . $observed);
	constructed_check(Model::$type_catalog->registry()->application_count() === 0,
		'Rejected constructed type published a canonical application');
}

$directory = sys_get_temp_dir() . '/scpp_constructed_type_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
try
{
	file_put_contents($path, '$first vector<int>; $second vector<int>; return 0;');
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->tokenize();
	$compiler->parse();
	constructed_check(Model::$type_catalog->registry()->application_count() === 0,
		'Parsing eagerly materialized a runtime template application');

	$syntax = Model::syntax_files()[0];
	$statements = $syntax->root->body->statements;
	$first = object_cast($statements[0], variable_declaration_node::class);
	$second = object_cast($statements[1], variable_declaration_node::class);
	$first_syntax = object_cast($first->type_syntax, template_application_type_node::class);
	$second_syntax = object_cast($second->type_syntax, template_application_type_node::class);
	constructed_check(($first_syntax->definition->name === 'vector')
		&& (q_count($first_syntax->arguments) === 1)
		&& ($first_syntax->arguments[0] instanceof named_type_node),
		'Template application syntax lost its definition or ordered argument');
	$children /** vector<ast_node> */ = [];
	foreach ($first_syntax->children() as $child) {
		$children[] = $child;
	}
	constructed_check(($children === [$first_syntax->definition, $first_syntax->arguments[0]])
		&& ($first_syntax->definition->occurrence()->name === 'vector')
		&& (object_cast($first_syntax->arguments[0], named_type_node::class)->occurrence()->name === 'int'),
		'Constructed-type traversal or collected name occurrences lost source order');

	$compiler->prepare();
	$first_use = $first_syntax->require_preparation();
	$second_use = $second_syntax->require_preparation();
	constructed_check(($first_use !== $second_use) && $first_use->matches($second_use),
		'Source occurrences did not retain lightweight uses of one canonical identity');
	$application = Type_Preparation::canonical($first_use);
	constructed_check($application instanceof applied_template_type,
		'Prepared constructed type did not resolve to an applied-template identity');
	$vector_int = object_cast($application, applied_template_type::class);
	$vector_definition = Model::$type_catalog->definition('vector');
	$arguments = $vector_int->arguments();
	constructed_check(($vector_int->definition() === $vector_definition)
		&& (q_count($arguments) === 1)
		&& $arguments[0]->matches(Language_Types::integer(Model::$language_scope))
		&& (Model::$type_catalog->registry()->application_count() === 1),
		'Canonical vector<int> identity lost its definition, argument or exact reuse');

	$compiler->cpp();
	$text = Model::$cpp_files[0]->text;
	constructed_check(str_contains($text, '#include "scpp/vector_t.hpp"')
		&& str_contains($text, 'scpp::vector_t<scpp::int_t<>> local_first;')
		&& str_contains($text, 'scpp::vector_t<scpp::int_t<>> local_second;'),
		'C++ binding did not render the canonical vector and argument identities');

	Compiler_Lifecycle::reset_preparation();
	constructed_check(($first_syntax->preparation() === null) && ($second_syntax->preparation() === null),
		'Preparation cleanup retained constructed-type occurrence facts');
	$compiler->prepare();
	constructed_check(($first_syntax->require_preparation()->type_id() === $vector_int->type_id())
		&& (Model::$type_catalog->registry()->application_count() === 1),
		'Re-preparation failed to reuse the demanded canonical application');

	constructed_rejects($path, '$bad vector<int, int>; return 0;',
		'Template type argument count does not match definition arity');
	constructed_rejects($path, '$bad vector<bool>; return 0;',
		'constructed-type proof currently supports vector<int> only');
	constructed_rejects($path, '$bad vector<vector<int>>; return 0;',
		'constructed-type proof currently supports vector<int> only');
	constructed_rejects($path, '$bad vector_t<int>; return 0;',
		'needs one resolved template type for vector_t');
	constructed_rejects($path, '$bad vector; return 0;',
		'template type requires explicit arguments');

	echo "Constructed type: vector<int> lookup, validation, interning, reuse, occurrence facts and C++ binding passed\n";
}
finally
{
	if (file_exists($path)) {
		unlink($path);
	}
	if (is_dir($directory)) {
		rmdir($directory);
	}
}
