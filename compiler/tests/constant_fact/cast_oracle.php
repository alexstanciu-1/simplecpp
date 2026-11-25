<?php
declare(strict_types=1);
require_once __DIR__ . '/../../boot.php';
use scpp\compiler\{Incremental_Preparation, Project_Compiler, ast_cast_expression, ast_local_declaration,
	entry_target, value_conversion};

function ensure(bool $ok, string $message): void
{
	if (!$ok) throw new RuntimeException($message);
}

function literal(string $directory, bool $value, int $stamp): void
{
	$path = "$directory/literal.php";
	file_put_contents($path, 'function fromLiteral():int { $count = (int) ' . ($value ? 'true' : 'false') . '; return $count; }');
	touch($path, $stamp);
	clearstatcache(true, $path);
}

/**
 * Read the cast and initializer facts for the requested function.
 * @return array<string,array>
 */
function facts($session, string $name): array
{
	$local = $session->scope->resolve("func:$name")->body()->statements[0];
	ensure($local instanceof ast_local_declaration && $local->initializer instanceof ast_cast_expression, 'Expected a local initialized by a cast');
	$cast = $local->initializer;
	$operand = $cast->operand;
	return ['input' => [$operand->constant_known(), $operand->constant()],
		'ops' => [$cast->conversion(), $local->initialization()],
		'cast' => [$cast->constant_known(), $cast->constant()], 'local' => [$local->constant_known(), $local->constant()]];
}

$app = $argv[1];
$profile = in_array('--calls', $argv, true);
file_put_contents("$app/module.json", '{}');
file_put_contents("$app/main.php", 'return fromLiteral() + fromParameter(false);');
file_put_contents("$app/parameter.php", 'function fromParameter(bool $flag):int { $count = (int) $flag; return $count; }');
$target = new entry_target(31);
$samples = [];
foreach (range(0, $profile ? 0 : 21) as $i) {
	$compiler = new Project_Compiler();
	$session = null;
	foreach (['fresh', 'edit', 'noop'] as $phase) {
		if ($phase !== 'noop') literal($app, $phase === 'edit', 1990000000 + 2 * $i + (int) ($phase === 'edit'));
		$session = $compiler->frontend($app, $session);
		$dirty = [];
		foreach (['literal' => 'fromLiteral', 'parameter' => 'fromParameter'] as $unit => $name) {
			if (!$session->scope->resolve("func:$name")->body()->is_prepared()) $dirty[] = $unit;
		}
		if ($profile) xdebug_start_function_monitor(['scpp\compiler\type_definition->evaluate_constant']);
		$start = hrtime(true);
		$work = Incremental_Preparation::prepare($session, $target);
		$ns = hrtime(true) - $start;
		if ($profile) {
			$calls[$phase] = count(xdebug_get_monitored_functions(true));
			xdebug_stop_function_monitor();
		}
		if ($i > 0) $samples[$phase][] = $ns;
		$rows[$phase] = ['work' => $work, 'dirty' => $dirty, 'literal' => facts($session, 'fromLiteral'), 'parameter' => facts($session, 'fromParameter')];
	}
}
// A clean preparation of the edited source must produce the same cast and local facts.
$fresh = (new Project_Compiler())->frontend($app);
Incremental_Preparation::prepare($fresh, $target);
foreach (['literal' => 'fromLiteral', 'parameter' => 'fromParameter'] as $unit => $name) {
	ensure(facts($fresh, $name) === $rows['edit'][$unit], 'Fresh and repaired facts differ');
}
echo json_encode(['samples_ns' => $samples, 'phases' => $rows, 'calls' => $calls ?? null,
	'identity' => value_conversion::identity, 'boolean_integer' => value_conversion::boolean_integer], JSON_THROW_ON_ERROR);
