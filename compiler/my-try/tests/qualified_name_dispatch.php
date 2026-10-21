<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

// Real parser/collection scopes prove the unchanged naming algorithm.
Compiler_Lifecycle::reset();
$module = new module('/qualified-names', '/qualified-names', '/qualified-names');
$file = new file();
$file->path = 'main.phs';
$file->content = 'namespace { function top(): void {} } namespace A\B { '
	. 'function fun(): void {} const LIMIT = 3; class Box { public function run(): void {} } '
	. 'interface I {} enum E: int { case one = 1; } trait T {} }';
Source_Registry::add($module, $file);
Model::$modules->add($module->name, $module);
$compiler = new Compiler();
$compiler->sync([]);
$actual = [];
foreach (Model::collected_files() as $collection) {
	foreach ($collection->entries as $entry) {
		if ($entry instanceof collected_definition) { $actual[$entry->name] = $entry->qualified_name(); }
	}
}
$expected = ['top' => 'top', 'fun' => 'A\B\fun', 'LIMIT' => 'A\B\LIMIT',
	'Box' => 'A\B\Box', 'run' => 'A\B\Box::run', 'I' => 'A\B\I',
	'E' => 'A\B\E', 'T' => 'A\B\T'];
foreach ($expected as $name => $qualified) {
	if (($actual[$name] ?? '') !== $qualified) {
		throw new \LogicException('Qualified name changed: ' . $name . ': ' . ($actual[$name] ?? 'missing'));
	}
}

// Project actual dispatch methods. Scope lookup/accessors are controlled fixture
// inputs here; the real lookup algorithm is checked above. Unrelated nullable
// comparisons, preparation-owner returns and syntax members stay outside this proof.
$source = "<?php\n";
$classes = [
	'collected_definition' => '',
	'collected_source_callable' => '',
	'collected_function' => '',
	'collected_constant' => '',
	'collected_type_definition' => '',
	'collected_member_owner' => '',
	'collected_record' => '',
	'collected_enum' => '',
	'collected_interface' => '',
	'collected_trait' => '',
	'collected_method' => '',
];
$classes['collected_definition'] = 'public string $name = ""; public string $prefix = "";
protected function scope_qualified_name(): string { return $this->prefix . $this->name; }';
$classes['collected_method'] = 'private collected_member_owner $owner;
public function __construct(collected_member_owner $owner) { $this->owner = $owner; }
public function declaring_type(): collected_member_owner { return $this->owner; }';
foreach ($classes as $name => $members) {
	$class = new \ReflectionClass(__NAMESPACE__ . '\\' . $name);
	$parent = $class->getParentClass();
	$source .= ($class->isAbstract() ? 'abstract ' : '') . 'class ' . $name
		. ($name === 'collected_definition' ? '' : ' extends ' . $parent->getShortName()) . " {\n" . $members . "\n";
	$method = $class->getMethod('qualified_name');
	if ($method->getDeclaringClass()->getName() === $class->getName()) {
		$lines = file($method->getFileName());
		$source .= implode('', array_slice($lines, $method->getStartLine() - 1,
			$method->getEndLine() - $method->getStartLine() + 1));
	}
	$source .= "}\n";
}
$source .= <<<'PHP'
class Name_Probe {
 public static function report(collected_definition $definition): void { echo $definition->qualified_name(), "\n"; }
}
$function = new collected_function(); $function->name = 'top'; Name_Probe::report($function);
$function->prefix = 'A\\B\\'; $function->name = 'fun'; Name_Probe::report($function);
$constant = new collected_constant(); $constant->prefix = 'A\\B\\'; $constant->name = 'LIMIT'; Name_Probe::report($constant);
$record = new collected_record(); $record->prefix = 'A\\B\\'; $record->name = 'Box'; Name_Probe::report($record);
$method = new collected_method($record); $method->name = 'run'; Name_Probe::report($method);
$enum_record = new collected_enum(); $enum_record->prefix = 'A\\B\\'; $enum_record->name = 'E'; Name_Probe::report($enum_record);
$interface_record = new collected_interface(); $interface_record->prefix = 'A\\B\\'; $interface_record->name = 'I'; Name_Probe::report($interface_record);
$trait_record = new collected_trait(); $trait_record->prefix = 'A\\B\\'; $trait_record->name = 'T'; Name_Probe::report($trait_record);
PHP;
file_put_contents($argv[1], $source);
echo "Actual collected names and dispatch projection passed\n";
