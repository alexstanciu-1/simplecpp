<?php

declare(strict_types=1);
// Reuse the baseline helpers and run its PHP contracts; native fixtures below focus on suspended foreach.
require __DIR__ . '/yield.php';
use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, Incremental_Project};
$fixtures = [];
foreach ([flavor::cpp, flavor::php, flavor::js] as $flavor) {
	$v = static fn(string $name): string => ($flavor === flavor::php ? '$' : '') . $name;
	$member = $flavor === flavor::js ? '.' : '->';
	$decl = static fn(string $type, string $name, string $value): string => match ($flavor) {
		flavor::php => '$' . $name . ' ' . $type . ' = ' . $value . ';',
		flavor::cpp => $type . ' ' . $name . '=' . $value . ';',
		flavor::js => 'let ' . $name . ':' . $type . ' = ' . $value . ';',
	};
	$local = static fn(string $name, string $value): string => match ($flavor) {
		flavor::php => '$' . $name . '=' . $value . ';', flavor::cpp => 'auto ' . $name . '=' . $value . ';',
		flavor::js => 'let ' . $name . '=' . $value . ';',
	};
	$signature = static fn(string $name, string $result, string $type = ''): string => match ($flavor) {
		flavor::php => 'function ' . $name . '(' . ($type === '' ? '' : $type . ' $x') . '):' . $result,
		flavor::cpp => $result . ' ' . $name . '(' . ($type === '' ? '' : $type . ' x') . ')',
		flavor::js => 'function ' . $name . '(' . ($type === '' ? '' : 'x:' . $type) . '):' . $result,
	};
	$loop = static fn(string $source, string $body, bool $keyed = false): string => 'foreach(' . $source . ' as '
		. ($keyed ? $v('key') . '=>' : '') . $v('value') . '){' . $body . '}';
	$prepare = static fn(string $text) => (new Source_Frontend())->prepare(new source_file('yield_foreach', $text, $flavor), new entry_target(31));
	$emit = static function (string $name, string $text, string $expected) use (&$fixtures, $prepare, $flavor): void {
		$start = hrtime(true); $entry = $prepare($text); $elapsed = hrtime(true) - $start;
		$before = serialize($entry); $start = hrtime(true); $cpp = (new Cpp_Emitter())->emit($entry);
		$elapsed += hrtime(true) - $start;
		verify(serialize($entry) === $before, 'Lowering mutated preparation');
		$fixtures[$flavor->name . '_' . $name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected), 'compiler_ms' => $elapsed / 1e6];
	};
	foreach (['vector<int>', 'hash<int,string>', 'mixed', 'dynamic'] as $family => $type) {
		$setup = $decl($type, 'items', '[]') . $v('items') . ($family === 0 ? '[]' : '["a"]') . '=10;'
			. $v('items') . ($family === 0 ? '[]' : '["b"]') . '=20;';
		$factory = $signature('make', $type) . '{' . $setup . 'return ' . $v('items') . ';}';
		$body = $loop($v('x'), 'yield (int)' . $v('value') . ';');
		$producer = $signature('stream', 'Iterator<int>', $type) . '{' . $body . '}';
		$main = $loop('stream(make())', 'echo ' . $v('value') . ';') . 'return 8;';
		$emit('family_' . $family, $factory . $producer . $main, '1020');
		$emit('temporary_' . $family, $factory . $signature('stream', 'Iterator<int>', $type) . '{'
			. $loop('make()', 'yield (int)' . $v('value') . ';') . '}' . $main, '1020');
		$keyed = $signature('stream', 'Iterator<int,string>', $type) . '{'
			. $loop($v('x'), 'yield (string)' . $v('key') . '=>(int)' . $v('value') . ';', true) . '}';
		$emit('keyed_' . $family, $factory . $keyed . $loop('stream(make())', 'echo ' . $v('key') . ',' . $v('value') . ';', true) . 'return 8;', $family === 0 ? '010120' : 'a10b20');
		$mutated = $signature('stream', 'Iterator<int>', $type) . '{' . $loop($v('x'),
			'yield (int)' . $v('value') . ';' . ($family < 2 ? $v('x') . $member . 'clear();' : $v('x') . '=empty_items();') . 'continue;') . '}';
		$empty_factory = $signature('empty_items', $type) . '{' . $decl($type, 'empty', '[]') . 'return ' . $v('empty') . ';}';
		$emit('clear_' . $family, $empty_factory . $factory . $mutated . $main, '10');
		$existing = $signature('stream', 'Iterator<int>', $type) . '{' . $decl('int', 'value', '99')
			. $loop($v('x'), 'yield ' . $v('value') . ';break;') . 'yield ' . $v('value') . ';}';
		$emit('existing_' . $family, $factory . $existing . $main, '1010');
		// Move a suspended cursor: borrowed receiver access must use the destination frame.
		$entry = $prepare($factory . $producer . 'return 8;');
		$cpp = str_replace('int main()', 'int source_main()', (new Cpp_Emitter())->emit($entry));
		$cpp .= <<<'CPP'
int main() {
    auto original = App::stream_(App::make_());
    scpp::int64 out = 0;
    if (!original.next(out) || out != 10) return 1;
    auto cursor = static_cast<App::stream_cursor&&>(original);
    if (original.next(out) || !cursor.next(out) || out != 20 || cursor.next(out)) return 2;
    return 8;
}
CPP;
		$fixtures[$flavor->name . '_move_' . $family] = ['cpp' => $cpp, 'stdout_hex' => ''];
		if ($family === 0) {
			$vector_factory = $factory;
			$vector_producer = $producer;
			$vector_main = $main;
		}
	}
	$position = $v('this') . $member . 'position';
	$field = match ($flavor) {
		flavor::php => 'public int $position=0;', flavor::cpp => 'int position=0;', flavor::js => 'position:int=0;'
	};
	$method = match ($flavor) {
		flavor::php => 'public function next(int &$value):bool', flavor::cpp => 'bool next(int &value)', flavor::js => 'next(&value:int):bool'
	};
	$record = 'struct Counter implements Iterator<int>{' . $field . $method . '{if(' . $position . '>=3){'
		. $v('value') . '=99;return false;}' . $v('value') . '=' . $position . ';' . $position . '++;return true;}}' . ($flavor === flavor::cpp ? ';' : '');
	$counter = $signature('make_counter', 'Counter') . '{' . match ($flavor) {
		flavor::php => '$counter Counter;', flavor::cpp => 'Counter counter;', flavor::js => 'let counter:Counter;',
	} . 'return ' . $v('counter') . ';}';
	$custom = $signature('custom', 'Iterator<int>', 'Counter') . '{' . $decl('int', 'value', '7')
		. $loop($v('x'), 'yield ' . $v('value') . ';') . 'yield ' . $v('value') . ';}';
	$emit('custom', $record . $counter . $custom . $loop('custom(make_counter())', 'echo ' . $v('value') . ';') . 'return 8;', '0122');
	$emit('custom_temporary', $record . $counter . str_replace($loop($v('x'), 'yield ' . $v('value') . ';'), $loop('make_counter()', 'yield ' . $v('value') . ';'), $custom)
		. $loop('custom(make_counter())', 'echo ' . $v('value') . ';') . 'return 8;', '0122');
	$nested = $signature('stream', 'Iterator<int>', 'vector<int>') . '{' . $loop($v('x'),
		'foreach(' . $v('x') . ' as ' . $v('inner') . '){yield ' . $v('value') . '*100+' . $v('inner') . ';}') . '}';
	$emit('nested', $vector_factory . $nested . $vector_main, '1010102020102020');
	$box_field = match ($flavor) {
		flavor::php => 'public vector<int> $items;', flavor::cpp => 'vector<int> items;', flavor::js => 'items:vector<int>;',
	};
	$box = 'struct Box {' . $box_field . '}' . ($flavor === flavor::cpp ? ';' : '');
	$path = $v('x') . $member . 'items';
	$owner_stream = $signature('owners', 'Iterator<int>', 'shared<Box>') . '{' . $loop($path,
		'yield ' . $v('value') . ';' . $v('x') . '=null;') . '}';
	$setup = $local('owner', 'new Box()') . $v('owner') . $member . 'items[]=5;' . $v('owner') . $member . 'items[]=6;';
	$emit('shared_field', $box . $owner_stream . $setup . $loop('owners(' . $v('owner') . ')', 'echo ' . $v('value') . ';') . 'return 8;', '56');
	$entry = $prepare($box . $owner_stream . 'return 8;');
	$cpp = str_replace('int main()', 'int source_main()', (new Cpp_Emitter())->emit($entry));
	$cpp .= <<<'CPP'
int main() {
    auto owner = App::owner_Box::make();
    owner->items.append(scpp::int64{5}); owner->items.append(scpp::int64{6});
    App::weak_Box weak(owner);
    scpp::int64 value = 0;
    {
        auto original = App::owners_(owner);
        owner = {};
        if (!original.next(value) || value != 5) return 1;
        auto cursor = static_cast<App::owners_cursor&&>(original);
        if (original.next(value) || !cursor.next(value) || value != 6 || !weak.lock()) return 2;
        if (cursor.next(value) || weak.lock()) return 3;
    }
    owner = App::owner_Box::make(); owner->items.append(scpp::int64{9}); weak = App::weak_Box(owner);
    {
        auto cursor = App::owners_(owner); owner = {};
        if (!cursor.next(value)) return 4;
    }
    if (weak.lock()) return 5;
    return 8;
}
CPP;
	$fixtures[$flavor->name . '_shared_lifetime'] = ['cpp' => $cpp, 'stdout_hex' => ''];
	rejects(fn() => $prepare($signature('bad', 'Iterator<int>', 'mixed') . '{'
		. $loop($v('x') . '["nested"]', 'yield (int)' . $v('value') . ';') . '}return 8;'), 'Suspended foreach');

	$checked = str_replace('Iterator<int>', 'Iterator<byte>', $vector_producer);
	$entry = $prepare(str_replace('=20;', '=300;', $vector_factory) . $checked . 'return 8;');
	$cpp = str_replace('int main()', 'int source_main()', (new Cpp_Emitter())->emit($entry));
	$cpp .= <<<'CPP'
int main() {
    auto cursor = App::stream_(App::make_());
    scpp::uint8 value = 0;
    if (!cursor.next(value) || value != 10) return 1;
    bool caught = false;
    try { cursor.next(value); } catch (...) { caught = true; }
    if (!caught || value != 10 || cursor.next(value)) return 2;
    return 8;
}
CPP;
	$fixtures[$flavor->name . '_checked_failure'] = ['cpp' => $cpp, 'stdout_hex' => ''];
	$key_method = match ($flavor) {
		flavor::php => 'public function next(int &$value,int &$key):bool',
		flavor::cpp => 'bool next(int &value,int &key)', flavor::js => 'next(&value:int,&key:int):bool',
	};
	$key_record = str_replace(['Iterator<int>', $method, $position . '++;return true;'],
		['Iterator<int,int>', $key_method, $v('key') . '=' . $position . '+10;' . $position . '++;return true;'], $record);
	$key_custom = $signature('custom', 'Iterator<int,int>', 'Counter') . '{'
		. $loop($v('x'), 'yield ' . $v('key') . '=>' . $v('value') . ';', true) . '}';
	$emit('custom_keyed', $key_record . $counter . $key_custom . $loop('custom(make_counter())',
		'echo ' . $v('key') . ',' . $v('value') . ';', true) . 'return 8;', '100111122');

	$vector_base = $vector_factory . $vector_producer;
	$custom_base = $record . $counter . $custom;
	$custom_main = $loop('custom(make_counter())', 'echo ' . $v('value') . ';') . 'return 8;';
	$vector_temporary = $vector_factory . str_replace($loop($v('x'), 'yield (int)' . $v('value') . ';'),
		$loop('make()', 'yield (int)' . $v('value') . ';'), $vector_producer);
	$custom_temporary = str_replace($loop($v('x'), 'yield ' . $v('value') . ';'), $loop('make_counter()', 'yield ' . $v('value') . ';'), $custom_base);
	$base_field = match ($flavor) {
		flavor::php => 'public int $number=7;', flavor::cpp => 'int number=7;', flavor::js => 'number:int=7;',
	};
	$classes = 'class Base {' . $base_field . '}' . ($flavor === flavor::cpp ? ';class Child : Base {};' : 'class Child extends Base {}');
	$views = $signature('views', 'Iterator<Base>', 'Child') . '{' . $loop('make()', $decl('Base', 'view', $v('x')) . 'yield ' . $v('x') . ';') . '}';
	$view_library = $classes . $vector_factory . $views;
	$view_main = $decl('Base', 'value', 'new Child()') . $loop('views(new Child())', 'echo ' . $v('value') . $member . 'number;') . 'return 8;';
	$emit('view', $view_library . $view_main, '77');
	foreach ([
		'collection' => [$vector_main, $vector_base, str_replace('=10;', '=11;', $vector_base),
			str_replace('yield (int)' . $v('value'), 'yield "bad"', $vector_base), $vector_temporary, '1020', '1120'],
		'view' => [$view_main, $view_library, str_replace('=7;', '=9;', $view_library),
			str_replace('yield ' . $v('x'), 'yield 1', $view_library), str_replace('Iterator<Base>', 'Iterator<Child>', $view_library), '77', '99'],
		'custom' => [$custom_main, $custom_base, str_replace('>=3', '>=2', $custom_base),
			str_replace('implements Iterator<int>', '', $custom_base), $custom_temporary, '0122', '011'],
	] as $case => [$entry_source, $base, $changed, $invalid, $temporary, $expected, $changed_output]) {

		$directory = sys_get_temp_dir() . '/scpp-yield-foreach-' . bin2hex(random_bytes(8)); mkdir($directory);
		try {
			$provider = $directory . '/library.' . $flavor->name;
			file_put_contents($directory . '/main.' . $flavor->name, $entry_source);
			file_put_contents($directory . '/module.json', json_encode(['entry' => 'main.' . $flavor->name,
				'sources' => ['main.' . $flavor->name, basename($provider)]]));
			$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31)); $stamp = time(); $caller = null;
			foreach ([$base, $changed, $invalid, '', $temporary, $base] as $step => $text) {
				file_put_contents($provider, $text); touch($provider, ++$stamp);
				$fresh = new Incremental_Project($directory, $directory . '/fresh', new entry_target(31));
				if ($step === 2 || $step === 3) {
					rejects(fn() => $project->run(), ''); rejects(fn() => $project->run(), ''); rejects(fn() => $fresh->run(), ''); continue;
				}
				$project->run(); $fresh->run(); $caller ??= $project->session()->entry->entry->ast;
				verify($caller === $project->session()->entry->entry->ast, 'Unchanged caller reparsed');
				$files = artifacts($directory . '/out'); verify($files === artifacts($directory . '/fresh'), 'Suspended traversal incremental differs from fresh');
				$fixtures[$flavor->name . '_' . $case . '_edit_' . $step] = ['files' => $files, 'stdout_hex' => bin2hex($step === 1 ? $changed_output : $expected)];
				verify($project->run()['preparation']['prepared'] === 0, 'No-op prepared bodies');
			}
		} finally {
			remove_tree($directory);
		}

	}

	rejects(fn() => $prepare($vector_factory . $vector_producer . $signature('bad', 'Iterator<int>') . '{'
		. $loop('stream(make())', 'yield ' . $v('value') . ';') . '}return 8;'), 'Nested cursor');
}
if (isset($argv[1])) file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
echo "Suspended foreach collection and custom Iterator contracts passed\n";
