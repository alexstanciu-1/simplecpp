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
	$source = <<<'PHS'
struct Envelope { value<int> $item; vector<value<int>> $items; }
function carry(value<int> $item): value<int> { return $item; }
$first vector<int>;
$second vector<int>;
$nested vector<vector<hash<int>>>;
$maybe nullable<string>;
$shared_value shared<int>;
$weak_value weak<int>;
$unique_value unique<int>;
$by_value value<int>;
$nested_value vector<value<int>>;
return 0;
PHS;
	file_put_contents($path, $source);
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
	$by_value = object_cast($statements[7], variable_declaration_node::class);
	$by_value_syntax = object_cast($by_value->type_syntax, type_use_modifier_node::class);
	$modifier_children /** vector<ast_node> */ = [];
	foreach ($by_value_syntax->children() as $child) {
		$modifier_children[] = $child;
	}
	constructed_check(($by_value_syntax->name === 'value')
		&& ($by_value_syntax->modifier === type_use_modifier_kind::by_value)
		&& ($modifier_children === [$by_value_syntax->operand]),
		'value<T> did not retain one truthful modifier node and operand');

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
		&& (Model::$type_catalog->registry()->application_count() === 8),
		'Canonical vector<int> identity lost its definition, argument or exact reuse');
	$by_value_use = $by_value_syntax->require_preparation();
	constructed_check(!$by_value_use->by_value()
		&& ($by_value_use->type_id() === Language_Types::integer(Model::$language_scope)->type_id()),
		'Redundant value<T> did not normalize to its operand type');
	$nested_value = object_cast($statements[8], variable_declaration_node::class);
	$nested_value_type = object_cast(Type_Preparation::canonical(
		$nested_value->type_syntax->require_preparation()), applied_template_type::class);
	constructed_check($nested_value_type === $vector_int,
		'Redundant nested modifier split the canonical application');

	$compiler->cpp();
	$text = Model::$cpp_files[0]->text;
	constructed_check(str_contains($text, '#include "scpp/vector_t.hpp"')
		&& str_contains($text, 'scpp::vector_t<scpp::int_t<>> local_first;')
		&& str_contains($text, 'scpp::vector_t<scpp::int_t<>> local_second;')
		&& str_contains($text, '#include "scpp/hash_t.hpp"')
		&& str_contains($text, '#include "scpp/nullable.hpp"')
		&& str_contains($text, '#include "scpp/shared_p.hpp"')
		&& str_contains($text, '#include "scpp/weak_p.hpp"')
		&& str_contains($text, '#include "scpp/unique_p.hpp"')
		&& !str_contains($text, '#include "scpp/value_p.hpp"')
		&& (substr_count($text, '#include "scpp/int_t.hpp"') === 1)
		&& str_contains($text, 'scpp::vector_t<scpp::int_t<>> local_nestedU_value;')
		&& str_contains($text, 'scpp::int_t<> field_item;')
		&& str_contains($text, 'scpp::vector_t<scpp::int_t<>> field_items;')
		&& str_contains($text, 'scpp::int_t<> function_carry('),
		'C++ binding did not recursively render constructed types, modifiers and headers');
	constructed_check(str_contains($text, "\treturn local_item;\n"),
		'value<T> return emission incorrectly applied the scalar integer conversion');

	Compiler_Lifecycle::reset_preparation();
	constructed_check(($first_syntax->preparation() === null) && ($second_syntax->preparation() === null),
		'Preparation cleanup retained constructed-type occurrence facts');
	$compiler->prepare();
	constructed_check(($first_syntax->require_preparation()->type_id() === $vector_int->type_id())
		&& (Model::$type_catalog->registry()->application_count() === 8),
		'Re-preparation failed to reuse the demanded canonical application');

	// Spelling-only edits must retain semantic applications and generated bytes.
	file_put_contents($path, str_replace('value<int>', 'int', $source));
	$compiler->update_cpp([$path]);
	$plain_statement = object_cast(Model::syntax_files()[0]->root->body->statements[7], variable_declaration_node::class);
	constructed_check(($plain_statement->type_syntax instanceof named_type_node)
		&& (Model::$cpp_files[0]->text === $text)
		&& (Model::$type_catalog->registry()->application_count() === 8),
		'Redundant modifier removal changed semantic identity or C++ output');
	file_put_contents($path, $source);
	$compiler->update_cpp([$path]);
	$explicit_statement = object_cast(Model::syntax_files()[0]->root->body->statements[7], variable_declaration_node::class);
	constructed_check(($explicit_statement->type_syntax instanceof type_use_modifier_node)
		&& (Model::$cpp_files[0]->text === $text)
		&& (Model::$type_catalog->registry()->application_count() === 8),
		'Redundant modifier restoration lost AST spelling or canonical reuse');

	constructed_rejects($path, '$bad value<void>; return 0;',
		'void is not a storage type');
	constructed_rejects($path, '$bad vector<int, int>; return 0;',
		'Template type argument count does not match definition arity');
	constructed_rejects($path, '$bad vector<void>; return 0;',
		'does not declare required capability value_storable');
	constructed_rejects($path, '$bad value<value<string>>; return 0;',
		'value type modifier cannot be repeated');
	constructed_rejects($path, '$bad vector_t<int>; return 0;',
		'needs one resolved template type for vector_t');
	constructed_rejects($path, '$bad vector; return 0;',
		'template type requires explicit arguments');

	echo "Constructed type: runtime families, contracts, nesting, value modifiers, exact reuse and recursive C++ bindings passed\n";
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
