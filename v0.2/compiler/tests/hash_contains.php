<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter,
	Function_Preparation, Preparation, key_presence_type, key_lookup_type, runtime_function, diagnostic, diagnostic_group};

function verify(bool $ok, string $message): void
{
	if (!$ok) throw new RuntimeException($message);
}

/** Both query capabilities reuse the same prepared runtime-call protocol. */
function prove_query(flavor $flavor, string $query, string $method, string $result): void
{
	$code = match ($flavor) {
		flavor::php => '$h hash<int,string> = []; $a=hash_contains($h,"x"); $b=hash_contains($h,"y");return 8;',
		flavor::cpp => 'hash<int,string> h = []; auto a=hash_contains(h,"x"); auto b=hash_contains(h,"y");return 8;',
		flavor::js => 'let h:hash<int,string> = []; let a=hash_contains(h,"x"); let b=hash_contains(h,"y");return 8;',
	};
	$code = str_replace('hash_contains', $query, $code);
	$entry = (new Source_Frontend())->prepare(new source_file('presence', $code, $flavor), new entry_target(31));
	$first = $entry->body()->statements[1]->initializer;
	$second = $entry->body()->statements[2]->initializer;
	$type = $first->arguments[0]->type_def();
	verify($query === 'hash_contains' ? $type instanceof key_presence_type : $type instanceof key_lookup_type, 'Published type supplies capability');
	$canonical = $query === 'hash_contains' ? $type->presence_function() : $type->lookup_function();
	$binding = $first->function_def();
	verify($binding instanceof runtime_function && $binding === $second->function_def()
		&& $binding === $canonical, 'Calls share one immutable canonical binding');
	verify($binding->receiver_method && $binding->symbol === $method && $binding->return_type_def()->name === $result && $first->type_def() === $binding->return_type_def(), 'Use published member dispatch');
	verify($binding->parameters[0] === $type && $binding->parameters[1] === $first->arguments[1]->type_def(), 'Exact argument contracts');
	verify($binding->is_const_reference(0) && $binding->is_const_reference(1) && !$binding->is_reference(0), 'Const borrowing modes');
	verify($entry->body()->expression_visits === 8, 'Prepare each call, receiver and key only once: ' . $entry->body()->expression_visits);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering must not mutate canonical bindings or syntax');
	verify(substr_count($cpp, '.' . $method . '(') === 2 && !str_contains($cpp, '.at(')
		&& !str_contains($cpp, $method === 'has' ? '.find(' : '.has('), 'Emit one published query, never probe then read');
	Function_Preparation::reset_body($entry->body());
	$again = (new Preparation())->prepare($entry->file, new entry_target(31));
	verify($again->body()->expression_visits === 8 && (new Cpp_Emitter())->emit($again) === $cpp, 'Reset/reprepare equivalence');
	foreach (['true', '1.0', '(byte)1'] as $key) {
		$prefix = match ($flavor) {
			flavor::php => '$h hash<int,int> = []; hash_contains($h,',
			flavor::cpp => 'hash<int,int> h = []; hash_contains(h,',
			flavor::js => 'let h:hash<int,int> = []; hash_contains(h,',
		};
		try {
			(new Source_Frontend())->prepare(new source_file('strict-key', str_replace('hash_contains', $query, $prefix) . $key . ');return 8;', $flavor), new entry_target(31));
			throw new RuntimeException('A hash key must not receive an implicit conversion');
		} catch (diagnostic|diagnostic_group $error) {
			verify(str_contains($error->getMessage(), $query . ' requires int64'), $error->getMessage());
		}
	}

}
foreach (flavor::cases() as $flavor) {
	prove_query($flavor, 'hash_contains', 'has', 'bool_t');
	prove_query($flavor, 'hash_find', 'find', 'nullable<int64>');
}
echo "Hash query binding reuse, exact const modes, single preparation and pure lowering passed\n";
