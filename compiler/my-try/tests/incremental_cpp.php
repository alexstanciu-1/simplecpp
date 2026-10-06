<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function cpp_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

function cpp_function(string $name): function_node
{
	return object_cast(Model::$global_scope->functions_named($name)[0]->syntax(), function_node::class);
}

$directory = sys_get_temp_dir() . '/scpp_cpp_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
$initial = 'struct Box { int32 $value; } function target(): int { return 1; } function caller(): int { return target(); } return caller();';
try
{
	file_put_contents($path, $initial);
	$compiler = new Compiler();
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$target = cpp_function('target');
	$caller = cpp_function('caller');
	$target_owner = $target->occurrence()->preparation;
	$target_body = $target->body->work();
	$caller_body = $caller->body->work();
	$source = Model::collected_files()[0];
	$signature = Model::$cpp_output_program->fragments[$target_owner];
	$body = Model::$cpp_output_program->fragments[$target_body];
	$caller_fragment = Model::$cpp_output_program->fragments[$caller_body];
	$entry = Model::$cpp_output_program->fragments[$source->root->single_body()->body->work()];
	$text = CPP_Probe::text(Model::$cpp_files);
	$compiler->exec_cpp();
	cpp_check(CPP_Probe::text(Model::$cpp_files) === $text && Model::$cpp_output_program->fragments[$target_body] === $body, 'No-op generation rerendered body');

	// Changed body, unchanged signature and consumers; preparation hands off work without settling it.
	file_put_contents($path, str_replace('return 1;', 'return 22;', $initial));
	$compiler->sync([$path]);
	$compiler->prepare();
	cpp_check(isset($source->preparation_changes[$target_body]), 'Preparation lost pending generation work');
	$handoff = $source->preparation_changes;
	$handoff_count = count($handoff);
	$work_version = $target_body->version;
	$dependencies = $target_body->dependencies;
	$lookups = $target_body->lookups;
	cpp_check(Preparation_Status::complete($target_body), 'Completed work query failed');
	$box_definition = Model::$global_scope->source_types_named('Box')[0];
	$box_nominal = object_cast(Model::$global_scope->types_named('Box')[0], nominal_type_definition::class);
	$box_canonical = Model::$type_catalog->registry()->canonical($box_nominal);
	$box_type = Model::$type_catalog->registry()->use($box_canonical->type_id());
	cpp_check(Type_Queries::source_record($box_type) === $box_definition, 'Read-only source-record query lost identity');
	cpp_check(Type_Queries::canonical($box_type)->type_id() === $box_type->type_id(), 'Read-only canonical query lost identity');
	cpp_check($target_body->version === $work_version && $target_body->dependencies === $dependencies && $target_body->lookups === $lookups, 'Read-only queries changed work');

	// Standalone output may be inspected repeatedly without consuming another caller's handoff.
	$standalone_generator = new CPP_Generator(Model::$cpp_output_program);
	$standalone = $standalone_generator->generate($source->prepared);
	$standalone_fragment = Model::$cpp_output_program->fragments[$target_body];
	$again = $standalone_generator->generate($source->prepared);
	cpp_check(CPP_Output::encode($standalone) === CPP_Output::encode($again) && Model::$cpp_output_program->fragments[$target_body] === $standalone_fragment, 'Standalone no-op rerendered work');
	cpp_check($source->preparation_changes === $handoff && count($handoff) === $handoff_count && Model::$cpp_files->is_empty(), 'Standalone generation acknowledged or published shared output');

	// A late assembly failure (after fragments settled) still withholds acknowledgment.
	$box_fragment = Model::$cpp_output_program->fragments[$box_definition->preparation];
	$box_records = $box_fragment->records;
	$box_fragment->records = new Storage();
	$box_fragment->records->append($box_definition);
	$assembly_failed = false;
	try { $compiler->cpp(); }
	catch (\RuntimeException $error) { $assembly_failed = str_contains($error->getMessage(), 'Cyclic C++ record assembly'); }
	cpp_check($assembly_failed && Model::$cpp_files->is_empty() && $source->preparation_changes === $handoff && count($handoff) === $handoff_count, 'Assembly failure consumed handoff or published output');
	$box_fragment->records = $box_records;
	$compiler->cpp();
	cpp_check(CPP_Output::encode(Model::$cpp_files) === CPP_Output::encode($standalone), 'Coordinator publication changed standalone output');
	cpp_check(count($source->preparation_changes) === 0, 'Successful generation did not consume handoff');
	cpp_check(Model::$cpp_output_program->fragments[$target_body] !== $body, 'Changed body reused stale text');
	cpp_check(Model::$cpp_output_program->fragments[$target_owner] === $signature && Model::$cpp_output_program->fragments[$caller_body] === $caller_fragment && Model::$cpp_output_program->fragments[$source->root->single_body()->body->work()] === $entry, 'Body edit rerendered unchanged fragments');

	// Insertion moves tokens but cannot rename existing declarations or perturb cached temporaries.
	$changed = 'function before(): int { return target(); } ' . str_replace('return 1;', 'return 22;', $initial);
	file_put_contents($path, $changed);
	$compiler->update_cpp([$path]);
	cpp_check(Model::$cpp_output_program->fragments[$caller_body] === $caller_fragment && Model::$cpp_output_program->fragments[$target_owner] === $signature, 'Token movement invalidated stable fragments');
	$incremental = CPP_Probe::text(Model::$cpp_files);
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	cpp_check(CPP_Probe::text(Model::$cpp_files) === $incremental, 'Fresh and incremental assembly differ');

	// Dirty rendering failure retains pending work and withholds completed output until retry.
	file_put_contents($path, str_replace('return 22;', 'return 33;', $changed));
	$compiler->sync([$path]);
	$compiler->prepare();
	$source = Model::collected_files()[0];
	$target_body_node = cpp_function('target')->body;
	$body_owner = $target_body_node->work();
	$target_statements = $target_body_node->statements;
	$target_return = object_cast($target_statements[0], return_node::class);
	$target_literal = object_cast($target_return->expression, integer_literal_node::class);
	$valid_literal_type = $target_literal->require_integer_literal_preparation()->type;
	$target_literal->require_integer_literal_preparation()->type = new canonical_type_use(Type_Identity::maximum());
	$failed = false;
	try {
		$compiler->cpp();
	}
	catch (\RuntimeException $error) {
		$failed = true;
	}
	cpp_check($failed && Model::$cpp_files->is_empty() && isset($source->preparation_changes[$body_owner]), 'Failed generation lost dirty work or published output');
	cpp_check(Model::$cpp_output_program->fragments[$body_owner]->change_status === change_state::changed, 'Failed fragment was settled');
	$target_literal->require_integer_literal_preparation()->type = $valid_literal_type;
	$compiler->cpp();
	cpp_check(Model::$cpp_output_program->fragments[$body_owner]->change_status === change_state::unchanged && count($source->preparation_changes) === 0, 'Retry did not settle generation');

	// Deleted declarations and their bodies leave both assembly and retained fragment storage.
	$deleted = cpp_function('before');
	$deleted_signature = $deleted->occurrence()->preparation;
	$deleted_body = $deleted->body->work();
	file_put_contents($path, str_replace('return 1;', 'return 33;', $initial));
	$compiler->update_cpp([$path]);
	cpp_check(!isset(Model::$cpp_output_program->fragments[$deleted_signature]) && !isset(Model::$cpp_output_program->fragments[$deleted_body]) && !str_contains(CPP_Probe::text(Model::$cpp_files), 'function_before'), 'Deleted declaration retained C++ fragments');

	// Signature changes invalidate affected bodies without replacing unrelated record fragments.
	$caller_owner = cpp_function('caller')->body->work();
	$caller_before = Model::$cpp_output_program->fragments[$caller_owner];
	$box_owner = Model::$global_scope->source_types_named('Box')[0]->preparation;
	$box_before = Model::$cpp_output_program->fragments[$box_owner];
	file_put_contents($path, str_replace('target(): int', 'target(): uint8', str_replace('return 1;', 'return 33;', $initial)));
	$compiler->update_cpp([$path]);
	cpp_check(Model::$cpp_output_program->fragments[$caller_owner] !== $caller_before, 'Signature change left stale consumer fragment');
	cpp_check(Model::$cpp_output_program->fragments[$box_owner] === $box_before, 'Signature change rerendered unrelated record');

	// Record dependency snapshots retain typed identities and duplicate field dependencies.
	file_put_contents($path, 'struct Outer { Inner $first; Inner $second; } struct Inner { int32 $value; } return 0;');
	$compiler->update_cpp([$path]);
	$outer_definition = Model::$global_scope->source_types_named('Outer')[0];
	$inner_definition = Model::$global_scope->source_types_named('Inner')[0];
	$outer_fragment = Model::$cpp_output_program->fragments[$outer_definition->preparation];
	cpp_check(count($outer_fragment->records) === 2 && $outer_fragment->records[0] === $inner_definition && $outer_fragment->records[1] === $inner_definition, 'Record snapshot lost identity or duplicate dependencies');
	$outer_header = CPP_Output::artifact(Model::$cpp_files, 'type:Outer');
	$inner_header = CPP_Output::artifact(Model::$cpp_files, 'type:Inner');
	cpp_check(str_contains($outer_header->text, '#include "' . $inner_header->file_name . '"')
		&& !str_contains($outer_header->text, 'struct record_Inner'), 'Record dependency was not represented by its own header');
	file_put_contents($path, 'struct Outer { int32 $first; int32 $second; } return 0;');
	$compiler->update_cpp([$path]);
	cpp_check(count(Model::$cpp_output_program->fragments[$outer_definition->preparation]->records) === 0 && !str_contains(CPP_Probe::text(Model::$cpp_files), 'record_Inner'), 'Changed record retained a deleted dependency');

	// An included runtime header disappears once its last emitting fragment is removed.
	file_put_contents($path, 'function floating(): float { return 1.5; } return 0;');
	$compiler->update_cpp([$path]);
	cpp_check(str_contains(CPP_Probe::text(Model::$cpp_files), 'scpp/float_t.hpp'), 'Missing fragment include');
	file_put_contents($path, 'return 0;');
	$compiler->update_cpp([$path]);
	cpp_check(!str_contains(CPP_Probe::text(Model::$cpp_files), 'scpp/float_t.hpp'), 'Deleted fragment retained stale include');

	// Defaults belong to signatures; callers depend on their presence and value.
	$defaults = 'function value(int $x = 7): int { return $x; } function caller(): int { return value(); } return caller();';
	file_put_contents($path, $defaults);
	$compiler->update_cpp([$path]);
	$default_function = cpp_function('value');
	$default_parameter = $default_function->parameters[0];
	$default_children = iterator_to_array($default_parameter->children());
	cpp_check(count($default_children) === 2 && $default_children[1] === $default_parameter->default_value, 'Parameter inspection omitted default syntax');
	$default_owner = $default_function->occurrence()->preparation;
	$default_caller = cpp_function('caller')->body->work();
	$default_fragment = Model::$cpp_output_program->fragments[$default_owner];
	$default_caller_fragment = Model::$cpp_output_program->fragments[$default_caller];
	cpp_check(str_contains($default_fragment->prototype_text, ' = ') && !str_contains($default_fragment->text, ' = '), 'Defaults must appear only in the prototype');
	$compiler->exec_cpp();
	cpp_check(Model::$cpp_output_program->fragments[$default_owner] === $default_fragment, 'No-op default rerendered signature');
	file_put_contents($path, str_replace('= 7', '= 9', $defaults));
	$compiler->update_cpp([$path]);
	cpp_check(cpp_function('value')->parameters[0] === $default_parameter, 'Default edit replaced parameter identity');
	cpp_check(Model::$cpp_output_program->fragments[$default_owner] !== $default_fragment && Model::$cpp_output_program->fragments[$default_caller] !== $default_caller_fragment, 'Default edit left stale signature or caller');
	$default_text = CPP_Probe::text(Model::$cpp_files);
	file_put_contents($path, ' ' . str_replace('= 7', '= 9', $defaults));
	$compiler->update_cpp([$path]);
	cpp_check(CPP_Probe::text(Model::$cpp_files) === $default_text, 'Default token movement changed output');
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	cpp_check(CPP_Probe::text(Model::$cpp_files) === $default_text, 'Fresh and incremental default output differ');
	foreach ([str_replace(' = 7', '', $defaults), str_replace('= 7', '= "bad"', $defaults)] as $invalid_default) {
		file_put_contents($path, $invalid_default);
		$failed_default = false;
		try { $compiler->update_cpp([$path]); }
		catch (\RuntimeException $error) { $failed_default = true; }
		cpp_check($failed_default && Model::$cpp_files->is_empty(), 'Invalid or removed default published stale output');
		file_put_contents($path, $defaults);
		$compiler->update_cpp([$path]);
		cpp_check(!Model::$cpp_files->is_empty(), 'Default repair failed to recover');
	}
	// A vector parameter and a variadic int parameter share storage, but not call contracts.
	$ordinary_vector = 'function pack(vector<int> $items): vector<int> { return $items; } function caller(): vector<int> { $items vector<int>; return pack($items); } $items = caller(); return 0;';
	file_put_contents($path, $ordinary_vector);
	$compiler->update_cpp([$path]);
	$pack_parameter = cpp_function('pack')->parameters[0];
	$vector_type = $pack_parameter->require_preparation()->storage_type();
	$variadic_source = str_replace('pack(vector<int> $items)', 'pack(int ...$items)', $ordinary_vector);
	file_put_contents($path, $variadic_source);
	$pack_failed = false;
	try { $compiler->update_cpp([$path]); }
	catch (\RuntimeException $error) { $pack_failed = true; }
	cpp_check($pack_failed && Model::$cpp_files->is_empty(), 'Variadic mode edit failed to invalidate vector caller');
	cpp_check(cpp_function('pack')->parameters[0] === $pack_parameter && $pack_parameter->require_preparation()->storage_type()->matches($vector_type), 'Variadic storage lost canonical vector identity');
	cpp_check($pack_parameter->variadic && $pack_parameter->require_preparation()->contract->element_type !== null, 'Variadic element facts missing');
	file_put_contents($path, str_replace('pack($items)', 'pack()', $variadic_source));
	$compiler->update_cpp([$path]);
	$pack_output = CPP_Probe::text(Model::$cpp_files);
	$pack_owner = cpp_function('pack')->occurrence()->preparation;
	$pack_fragment = Model::$cpp_output_program->fragments[$pack_owner];
	$compiler->exec_cpp();
	cpp_check(Model::$cpp_output_program->fragments[$pack_owner] === $pack_fragment, 'No-op variadic signature rerendered');
	Compiler_Lifecycle::reset();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	cpp_check(CPP_Probe::text(Model::$cpp_files) === $pack_output, 'Variadic repair differs from fresh output');

	// Every scalar snapshot participates in signature equivalence, including signed floats.
	foreach ([['string', '"7"', '"9"'], ['float', '-1.5', '-2.5'], ['bool', 'true', 'false']] as [$default_type, $old_default, $new_default]) {
		$scalar_source = 'function value(' . $default_type . ' $x = ' . $old_default . '): ' . $default_type . ' { return $x; } return 0;';
		file_put_contents($path, $scalar_source);
		$compiler->update_cpp([$path]);
		$scalar_owner = cpp_function('value')->occurrence()->preparation;
		$scalar_fragment = Model::$cpp_output_program->fragments[$scalar_owner];
		file_put_contents($path, str_replace('= ' . $old_default, '= ' . $new_default, $scalar_source));
		$compiler->update_cpp([$path]);
		cpp_check(Model::$cpp_output_program->fragments[$scalar_owner] !== $scalar_fragment, 'Scalar default value edit kept stale signature');
		$scalar_incremental = CPP_Probe::text(Model::$cpp_files);
		Compiler_Lifecycle::reset();
		$compiler->init([$directory]);
		$compiler->exec_cpp();
		cpp_check(CPP_Probe::text(Model::$cpp_files) === $scalar_incremental, 'Scalar default fresh output differs');
	}
}
finally {
	unlink($path);
	rmdir($directory);
}
echo "Incremental C++: cached fragments, body/signature independence, stable names, fresh equivalence, failure retry and deletion passed\n";
