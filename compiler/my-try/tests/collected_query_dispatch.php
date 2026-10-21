<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

// Host-only proof: check actual records without constructing unrelated syntax graphs.
$names = ['collected_function', 'collected_field', 'collected_parameter',
	'collected_variable', 'collected_call_reference', 'collected_variable_write'];
$queries = ['is_retained', 'is_exported', 'preparation_work_owner'];
$work = (new \ReflectionClass(source_callable_signature_work::class))->newInstanceWithoutConstructor();
foreach ($names as $name) {
	$record = (new \ReflectionClass(__NAMESPACE__ . '\\' . $name))->newInstanceWithoutConstructor();
	if ($record->is_retained() || $record->is_exported() || $record->preparation_work_owner() !== null) {
		throw new \LogicException('Changed default query behavior: ' . $name);
	}
	if ($record instanceof collected_declaration) {
		$record->retained_symbol = true;
		$record->exported = true;
		if (!$record->is_retained() || !$record->is_exported()) {
			throw new \LogicException('Lost declaration query dispatch: ' . $name);
		}
	}
	if ($record instanceof collected_definition) {
		$record->preparation = $work;
		if ($record->preparation_work_owner() !== $work) {
			throw new \LogicException('Lost preparation owner identity');
		}
	}
}

$flags_only = ($argv[2] ?? '') === 'flags-only';
if ($flags_only) { $queries = ['is_retained', 'is_exported']; }

// Project the real query declarations/bodies into a portable dispatch proof. Only
// unrelated syntax/collection members are omitted; never mirror these methods here.
$source = "<?php\nnamespace scpp\\compiler;\nclass preparation_owner {}\nclass contract_work extends preparation_owner {}\n";
$classes = ['collected_name', 'collected_declaration', 'collected_definition',
	'collected_source_callable', 'collected_reference', ...$names];
$fields = [
	'collected_declaration' => ['retained_symbol', 'exported'],
	'collected_definition' => ['preparation'],
];
foreach ($classes as $name) {
	$class = new \ReflectionClass(__NAMESPACE__ . '\\' . $name);
	$lines = file($class->getFileName());
	$parent = $class->getParentClass();
	$source .= ($class->isAbstract() ? 'abstract ' : '') . 'class ' . $name
		. ($parent ? ' extends ' . $parent->getShortName() : '') . " {\n";
	foreach ($fields[$name] ?? [] as $field) {
		$found = false;
		foreach (array_slice($lines, $class->getStartLine() - 1, $class->getEndLine() - $class->getStartLine() + 1) as $line) {
			if (preg_match('/^\s*public [^;]+\$' . $field . '\s*=/', $line)) {
				$source .= $line;
				$found = true;
			}
		}
		if (!$found) { throw new \LogicException('Missing projected field: ' . $field); }
	}
	foreach ($queries as $query) {
		$method = $class->getMethod($query);
		if ($method->getDeclaringClass()->getName() !== $class->getName()) { continue; }
		if ($name === 'collected_name' && !$method->isAbstract()) {
			throw new \LogicException('Root query must establish virtual dispatch');
		}
		$source .= implode('', array_slice($lines, $method->getStartLine() - 1,
			$method->getEndLine() - $method->getStartLine() + 1));
	}
	$source .= "}\n";
}
$source .= <<<'PHP'
class Query_Probe {
public static function report(collected_name $record): void {
 echo $record->is_retained() ? '1' : '0', $record->is_exported() ? '1' : '0';
PHP;
if (!$flags_only) {
	$source .= <<<'PHP'
 $owner = $record->preparation_work_owner();
 if ($owner) { echo '1'; } else { echo '0'; }
PHP;
}
$source .= <<<'PHP'
 echo ':';
}
}
$definition = new collected_function();
Query_Probe::report($definition);
$definition->retained_symbol = true; $definition->exported = true;
$definition->preparation = new contract_work(); Query_Probe::report($definition);
$field = new collected_field(); Query_Probe::report($field);
$field->retained_symbol = true; $field->exported = true; Query_Probe::report($field);
$parameter = new collected_parameter(); Query_Probe::report($parameter);
$variable = new collected_variable(); Query_Probe::report($variable);
$reference = new collected_call_reference(); Query_Probe::report($reference);
$write = new collected_variable_write(); Query_Probe::report($write);
echo "\n";
PHP;
file_put_contents($argv[1], $source);
echo "Actual collected query behavior and projected declarations passed\n";
