<?php
declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
use scpp\compiler\{Stringable_Contract, Source_Frontend, entry_target, flavor, source_file, type_registry};

$code = 'class Label implements Stringable { public override function to_string():string {return "L";} }
class Lookalike { public function to_string():string {return "L";} } return 0;';
$file = (new Source_Frontend())->prepare(new source_file('main', $code, flavor::php), new entry_target(31))->file;
$scope = $file->scope->parent;
$nominal = $scope->stringable->methods['to_string'];
foreach (['Label' => $nominal, 'Lookalike' => null] as $name => $expected) {
	if (Stringable_Contract::target($file->declarations['class:' . $name]->type_definition(), $scope) !== $expected) {
		throw new RuntimeException("Wrong nominal formatting target for $name");
	}
}
foreach (['string', 'int', 'bool', 'byte', 'float', 'text_builder', 'string_parts_builder'] as $name) {
	$type = type_registry::resolve($name);
	$target = Stringable_Contract::target($type, $scope);
	if ($target === null || $target !== $type->runtime_method('to_string') || $target->return_type_def() !== type_registry::string()) {
		throw new RuntimeException("Wrong native formatting target for $name");
	}
}
if (Stringable_Contract::target(type_registry::specialize('vector', type_registry::integer()), $scope) !== null) {
	throw new RuntimeException('Collection formatting must await the collection contract');
}
