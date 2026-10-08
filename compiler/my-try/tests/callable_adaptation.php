<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

$cases = [
	['D $x', 'B', 'B $x', 'D', true],
	['D $x', 'B', 'D $x', 'D', true],
	['D $x', 'B', 'B $x', 'B', true],
	['B $x', 'B', 'D $x', 'D', false],
	['D $x', 'D', 'B $x', 'B', false],
	['D &$x', 'B', 'B &$x', 'B', false],
	['D ...$x', 'B', 'B ...$x', 'B', false],
	['D $x, int $n = 1', 'B', 'B $other, int $n = 2', 'D', true],
	['D $x, int $n = 1', 'B', 'B $x, int $n', 'D', false],
	['int $x', 'int', 'float $x', 'int', false],
	['int $x', 'int', 'int $x', 'float', false],
	['weak<D> $x', 'weak<B>', 'weak<B> $x', 'weak<D>', false],
	['unique<D> $x', 'unique<B>', 'unique<B> $x', 'unique<D>', false],
	['vector<D> $x', 'B', 'vector<B> $x', 'D', false],
	['S $x', 'S', 'T $x', 'T', false],
	['shared<T> $x', 'shared<S>', 'shared<S> $x', 'shared<T>', true],
	['D $x', 'I', 'I $x', 'D', true],
	['J $x', 'I', 'I $x', 'J', true],
	['I $x', 'I', 'K $x', 'K', false],
	['int &$x', 'int', 'int &$x', 'int', true],
	['const int &$x', 'int', 'int &$x', 'int', false],
	['', 'void', '', 'void', true],
];
foreach ($cases as [$required_parameters, $required_return, $provided_parameters, $provided_return, $expected]) {
	Compiler_Lifecycle::reset();
	$module = new module('/adaptation', '/adaptation', '/adaptation');
	$file = new file(); $file->path = 'main.phs';
	$file->content = 'interface I {} interface J extends I {} interface K {} class B implements I {} class D extends B {} struct S {} struct T extends S {} '
		. 'interface Required { function f(' . $required_parameters . '): ' . $required_return . '; } '
		. 'interface Provided { function f(' . $provided_parameters . '): ' . $provided_return . '; } return 9;';
	Source_Registry::add($module, $file); Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
	$signatures = []; $owners = [];
	foreach (Model::collected_files() as $source) {
		foreach ($source->entries as $entry) {
			if ($entry instanceof collected_member_owner) { $owners[$entry->name] = $entry; }
			if ($entry instanceof collected_method) { $signatures[$entry->declaring_type()->name] = $entry->signature(); }
		}
	}
	$context = new preparation_context();
	$context->worker = new Preparation_Worker(Model::$language_scope);
	$context->owner = $source->root->single_body()->body->work();
	$query = new Conversion_Relationships($context);
	$adaptation = Callable_Adaptation::between($signatures['Required'], $signatures['Provided'], $query);
	if (($adaptation !== null) !== $expected) { throw new \LogicException('Incorrect directional callable adaptation: ' . $file->content); }
	if ($adaptation !== null) {
		if (!$adaptation->is_identity()) {
			if (Callable_Adaptation::exact($signatures['Required'], $signatures['Provided'])) { throw new \LogicException('Exact gate admitted variance'); }
			if (count($context->owner->dependencies) === 0) { throw new \LogicException('Variance did not observe ancestry'); }
		}
		foreach ($adaptation->parameters as $index => $conversion) {
			if (!$conversion->source_type->matches($signatures['Required']->parameters[$index]->type)
				|| !$conversion->target_type->matches($signatures['Provided']->parameters[$index]->type)) { throw new \LogicException('Parameter direction reversed'); }
		}
		if (!$adaptation->result->source_type->matches($signatures['Provided']->return_type)
			|| !$adaptation->result->target_type->matches($signatures['Required']->return_type)) { throw new \LogicException('Result direction reversed'); }
	}
	if ($expected && $required_parameters === 'D $x' && $provided_parameters === 'B $x') {
		if (!isset($context->owner->dependencies[$owners['D']->relationship_work])) { throw new \LogicException('Missing specific ancestry dependency'); }
	}
	if ($required_parameters === 'int &$x' && $provided_parameters === 'int &$x') {
		$signatures['Required']->reference_return = new reference_return_contract(0);
		$signatures['Provided']->reference_return = new reference_return_contract(0);
		if (!Callable_Adaptation::exact($signatures['Required'], $signatures['Provided'])) { throw new \LogicException('Exact reference origin rejected'); }
		$signatures['Provided']->reference_return = null;
		if (Callable_Adaptation::between($signatures['Required'], $signatures['Provided'], $query) !== null) { throw new \LogicException('Reference/value return mismatch accepted'); }
	}
}
Compiler_Lifecycle::reset();
echo "Callable adaptation model: 22 cases passed\n";
