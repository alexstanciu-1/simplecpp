<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function property_mutation_source(string $source): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/property_mutations', '/property_mutations', '/property_mutations');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
	return CPP_Probe::text(Model::$cpp_files);
}

$results = [];
foreach (['class', 'struct'] as $kind) {
	foreach ([
		'instance' => '$c C = new C(); $old = $c->n++; $new = ++$c->n; $sum = $c->n += 4; $c->n--; --$c->n; $c->s .= "2"; $n = $c->n; $s = (int)$c->s; if ($old !== 2) { return 1; } if ($new !== 4) { return 2; } if ($sum !== 8) { return 3; } if ($n !== 6) { return 4; } if ($s !== 12) { return 5; } return 0;',
		'static' => '$old = C::$total++; $new = ++C::$total; $sum = C::$total *= 3; C::$total--; --C::$total; $n = C::$total; if ($old !== 2) { return 1; } if ($new !== 4) { return 2; } if ($sum !== 12) { return 3; } if ($n !== 10) { return 4; } return 0;',
		'qualifier' => 'function produce(int &$calls): C { $calls++; return new C(); } $calls = 0; $old = produce($calls)::$total++; $new = produce($calls)::$total += 4; if ($calls !== 2) { return 1; } if ($old !== 2) { return 2; } if ($new !== 7) { return 3; } return 0;',
		'boxed' => '$c C = new C(); $c->m = 3; $c->d = 5; $a = $c->m++; $b = ++$c->d; $c->m += 2; $c->d *= 2; $m int = $c->m; $d int = $c->d; $old int = $a; $new int = $b; if ($old !== 3) { return 1; } if ($new !== 6) { return 2; } if ($m !== 6) { return 3; } if ($d !== 12) { return 4; } return 0;',
		'failure' => 'C::$total /= 0; return 1;',
		'const_static' => 'function change(const C &$c): int { return ++$c::$total; } $c C; $n = change($c); if ($n !== 3) { return 1; } return 0;',
	] as $name => $body) {
		$output = property_mutation_source($kind . ' C { int $n = 2; string $s = "1"; mixed $m; dynamic $d; static int $total = 2; } ' . $body);
		if ($name === 'failure') {
			foreach (Model::$cpp_files as $artifact) {
				if ($artifact->identity === 'entry') {
					$text = str_replace('int main()', 'int main() try', $artifact->text)
						. 'catch (const scpp::runtime_error &) { return record_C::field_total.native_value() == 2 ? 0 : 2; }' . "\n";
					$output = str_replace($artifact->text, $text, $output);
				}
			}
		}
		if (isset($argv[1])) {
			$path = $argv[1] . '/' . $kind . '_' . $name . '.cpp';
			file_put_contents($path, $output);
			$results[] = ['path' => $path, 'exit_code' => 0];
		}
	}
}
foreach ([
	'class C { const N = 2; } C::N++;',
	'class C { private int $n; } $c = new C(); $c->n++;',
	'class C { bool $b; } $c = new C(); $c->b++;',
	'class C { int $n; } $c = new C(); function effect(): int { return 1; } $c->n += effect();',
	'class C { int $n; } function f(): C { return new C(); } f()->n++;',
	'struct C { int $n; } function f(const C &$c): void { $c->n++; }',
	'class C { int $n; } function f(const C &$c): void { $c->n += 1; }',
	'class C { int $n; } $c weak<C>; $c->n++;',
] as $source) {
	try { property_mutation_source($source); throw new \LogicException('Unsupported property mutation accepted'); }
	catch (\RuntimeException $expected) {}
}
if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT) . "\n"); }
echo "property mutations: PASS\n";
