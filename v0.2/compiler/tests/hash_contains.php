<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter,
	Function_Preparation, Preparation, key_presence_type, runtime_function, diagnostic, diagnostic_group};

function verify(bool $ok, string $message): void
{
	if (!$ok) throw new RuntimeException($message);
}

foreach (flavor::cases() as $flavor) {
	$code = match ($flavor) {
		flavor::php => '$h hash<int,string> = []; $a=hash_contains($h,"x"); $b=hash_contains($h,"y");return 8;',
		flavor::cpp => 'hash<int,string> h = []; auto a=hash_contains(h,"x"); auto b=hash_contains(h,"y");return 8;',
		flavor::js => 'let h:hash<int,string> = []; let a=hash_contains(h,"x"); let b=hash_contains(h,"y");return 8;',
	};
	$entry = (new Source_Frontend())->prepare(new source_file('presence', $code, $flavor), new entry_target(31));
	$first = $entry->body()->statements[1]->initializer;
	$second = $entry->body()->statements[2]->initializer;
	$type = $first->arguments[0]->type_def();
	verify($type instanceof key_presence_type, 'Published type supplies capability');
	$binding = $first->function_def();
	verify($binding instanceof runtime_function && $binding === $second->function_def()
		&& $binding === $type->presence_function(), 'Calls share one immutable canonical binding');
	verify($binding->receiver_method && $binding->symbol === 'has', 'Use published member dispatch');
	verify($binding->parameters[0] === $type && $binding->parameters[1] === $first->arguments[1]->type_def(), 'Exact argument contracts');
	verify($binding->is_const_reference(0) && $binding->is_const_reference(1) && !$binding->is_reference(0), 'Const borrowing modes');
	verify($entry->body()->expression_visits === 8, 'Prepare each call, receiver and key only once: ' . $entry->body()->expression_visits);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering must not mutate canonical bindings or syntax');
	verify(substr_count($cpp, '.has(') === 2 && !str_contains($cpp, '.at(') && !str_contains($cpp, '.find('), 'Presence never reads the value');
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
			(new Source_Frontend())->prepare(new source_file('strict-key', $prefix . $key . ');return 8;', $flavor), new entry_target(31));
			throw new RuntimeException('A hash key must not receive an implicit conversion');
		} catch (diagnostic|diagnostic_group $error) {
			verify(str_contains($error->getMessage(), 'hash_contains requires int64'), $error->getMessage());
		}
	}

}
echo "Hash presence binding reuse, exact const modes, single preparation and pure lowering passed\n";
