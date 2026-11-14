<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/compilation.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Incremental_Project, type_availability, Program_Preparation, Symbol_Publication};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

/** Require semantic diagnostics before native compilation. */
function rejects(Closure $action, string $message): void
{
	try {
		$action();
	}
	catch (diagnostic|diagnostic_group $error) {
		verify(str_contains($error->getMessage(), $message), $error->getMessage());
		return;
	}
	throw new RuntimeException('Expected diagnostic: ' . $message);
}

/** Read only emitted native artifacts for incremental/fresh equivalence. @return string[] */
function artifacts(string $directory): array /** array<string,string> */
{
	$files = [];
	foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $item) {
		$path = $item->getPathname();
		if (in_array(pathinfo($path, PATHINFO_EXTENSION), ['cpp', 'hpp'], true)) {
			$files[substr($path, strlen($directory) + 1)] = file_get_contents($path);
		}
	}
	ksort($files);
	return $files;
}

/** Remove the isolated proof tree, including emitted bundles. */
function remove_tree(string $path): void
{
	foreach (new DirectoryIterator($path) as $item) {
		if (!$item->isDot()) {
			$item->isDir() ? remove_tree($item->getPathname()) : unlink($item->getPathname());
		}
	}
	rmdir($path);
}

$fixtures = [];
foreach (flavor::cases() as $flavor) {
	$sep = $flavor === flavor::js ? '.' : '::';
	$member = $flavor === flavor::js ? '.' : '->';
	$v = $flavor === flavor::php ? '$v' : 'v';
	$definition = static function (int $default = 7, bool $missing = false, bool $duplicate = false) use ($flavor): string {
		$methods = match ($flavor) {
			flavor::php => 'public function label():string{return enum_name($this);} public function copy():Kind{return $this;}'
				. 'public function number(int $n=' . $default . '):int{return enum_value($this)+$n;}',
			flavor::cpp => 'string label(){return enum_name(this);} Kind copy(){return this;}'
				. 'int number(int n=' . $default . '){return enum_value(this)+n;}',
			flavor::js => 'label():string{return enum_name(this);} copy():Kind{return this;}'
				. 'number(n:int=' . $default . '):int{return enum_value(this)+n;}',
		};
		if ($missing) $methods = str_replace('number(', 'other(', $methods);
		if ($duplicate) $methods .= match ($flavor) {
			flavor::php => 'public function label():string{return "duplicate";}',
			flavor::cpp => 'string label(){return "duplicate";}',
			flavor::js => 'label():string{return "duplicate";}',
		};
		return ($flavor === flavor::php ? 'enum Kind {case First=1;case Last=300;' : 'enum Kind {First=1,Last=300;') . $methods . '}';
	};
	$decl = match ($flavor) {
		flavor::php => '$v Kind=Kind::Last;', flavor::cpp => 'Kind v=Kind::Last;', flavor::js => 'let v:Kind=Kind.Last;',
	};
	$main = $decl . 'echo ' . $v . $member . 'label(),":",enum_name(' . $v . $member . 'copy()),":",'
		. $v . $member . 'number(),":",' . $v . $member . 'number(9);return 8;';
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('enum-methods', $source, $flavor), new entry_target(31));
	$start = hrtime(true);
	$cpp = (new Cpp_Emitter())->emit($prepare($definition() . $main));
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('Last:Last:307:309'), 'compiler_ms' => (hrtime(true)-$start)/1e6];
	rejects(fn() => $prepare($definition(7, false, true) . $main), 'Duplicate');
	rejects(fn() => $prepare($definition() . 'Kind' . $sep . 'label();return 8;'), 'Instance method requires a receiver');
	$replacement = match ($flavor) {
		flavor::php => 'enum Token {case First=1;case Last=2; public function change(Token &$target):Token{$target=self::First;return $this;} public function name():string{return enum_name($this);}} $t Token=Token::Last;echo enum_name($t->change($t)),":",$t->name();return 8;',
		flavor::cpp => 'enum Token {First=1,Last=2; Token change(Token &target){target=Self::First;return this;} string name(){return enum_name(this);}} Token t=Token::Last;echo enum_name(t->change(t)),":",t->name();return 8;',
		flavor::js => 'enum Token {First=1,Last=2; change(&target:Token):Token{target=class.First;return this;} name():string{return enum_name(this);}} let t:Token=Token.Last;echo enum_name(t.change(t)),":",t.name();return 8;',
	};
	$fixtures[$flavor->name . '_replace'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($replacement)), 'stdout_hex' => bin2hex('Last:First')];
	$mutator = match ($flavor) {
		flavor::php => 'function replace(Kind &$slot):int{$slot=Kind::First;return 9;}',
		flavor::cpp => 'int replace(Kind &slot){slot=Kind::First;return 9;}',
		flavor::js => 'function replace(&slot:Kind):int{slot=Kind.First;return 9;}',
	};
	$selected = $definition() . $mutator . $decl . 'echo ' . $v . $member . 'number(replace(' . $v . ')),":",enum_name(' . $v . ');return 8;';
	$fixtures[$flavor->name . '_argument_replaces_receiver'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($selected)), 'stdout_hex' => bin2hex('309:First')];
	$text = match ($flavor) {
		flavor::php => 'enum Text:string {case Word="word"; public function label(string $suffix="!"):string{return enum_value($this).$suffix;} public function relay():string{return $this->label();}} $t Text=Text::Word;echo $t->relay();return 8;',
		flavor::cpp => 'enum Text:string {Word="word"; string label(string suffix="!"){return enum_value(this)+suffix;} string relay(){return this->label();}} Text t=Text::Word;echo t->relay();return 8;',
		flavor::js => 'enum Text:string {Word="word"; label(suffix:string="!"):string{return enum_value(this)+suffix;} relay():string{return this.label();}} let t:Text=Text.Word;echo t.relay();return 8;',
	};
	$fixtures[$flavor->name . '_string'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($text)), 'stdout_hex' => bin2hex('word!')];
	$restricted = match ($flavor) {
		flavor::php => 'enum Bad {case A=1;private function hidden():void{}}',
		flavor::cpp => 'enum Bad {A=1;private: void hidden(){}}',
		flavor::js => 'enum Bad {A=1;static hidden():void{}}',
	};
	rejects(fn() => $prepare($restricted . 'return 8;'), 'Enum methods must be public instance methods');
	rejects(fn() => $prepare($definition() . $decl . $v . $member . 'number(1,2);return 8;'), 'Argument count mismatch');
	$directory = sys_get_temp_dir() . '/scpp-enum-methods-' . bin2hex(random_bytes(8)); mkdir($directory);
	try {
		$main_path = $directory . '/main.' . $flavor->name;
		$provider = $directory . '/kind.' . $flavor->name;
		file_put_contents($directory . '/module.json', json_encode(['entry' => basename($main_path), 'sources' => [basename($main_path),basename($provider)]]));
		file_put_contents($main_path, $main);
		$project = new Incremental_Project($directory, $directory . '/out', new entry_target(31));
		$stamp = time(); $caller = null;
		foreach ([[7,false,false],[12,false,false],[12,true,false],[9,false,true],[9,false,false]] as $index => [$default,$missing,$duplicate]) {
			file_put_contents($provider, $definition($default,$missing,$duplicate)); touch($provider,++$stamp);
			$fresh = new Incremental_Project($directory,$directory.'/fresh',new entry_target(31));
			if ($missing || $duplicate) {
				$error = $missing ? 'Unknown method' : 'Duplicate';
				rejects(fn() => $project->run(),$error); rejects(fn() => $project->run(),$error); rejects(fn() => $fresh->run(),$error);
				continue;
			}
			$project->run(); $fresh->run(); $caller ??= $project->session()->entry->entry->ast;
			verify($caller === $project->session()->entry->entry->ast, 'Caller reparsed');
			$files = artifacts($directory.'/out'); verify($files === artifacts($directory.'/fresh'), 'Fresh output differs');
			$fixtures[$flavor->name.'_edit_'.$index] = ['files'=>$files,'stdout_hex'=>bin2hex('Last:Last:' . (300+$default) . ':309')];
			verify($project->run()['preparation']['prepared']===0, 'No-op prepares bodies');
		}
	} finally {
		remove_tree($directory);
	}
}
if (isset($argv[1])) file_put_contents($argv[1],json_encode($fixtures,JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT));
echo "Enum methods: binders, receiver values and incremental/fresh recovery passed\n";
