<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function member_fact_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

$node = new static_member_reference_node();
$case_facts = new prepared_enum_case_reference();
$property = new prepared_static_property();
$constant = new prepared_constant_reference();
foreach ([['set_enum_case_preparation', 'enum_case_preparation', $case_facts],
	['set_property_preparation', 'property_preparation', $property],
	['set_constant_preparation', 'constant_preparation', $constant]] as [$setter, $getter, $facts]) {
	$node->$setter($facts);
	member_fact_check($node->$getter() === $facts && $node->preparation() === $facts
		&& $node->require_preparation() === $facts, 'Publication lost fact identity');
	$generic = $node->require_preparation();
	$generic->suspends = true;
	member_fact_check($facts->suspends, 'Generic epilogue mutated a copy');
	foreach (['enum_case_preparation', 'property_preparation', 'constant_preparation'] as $reader) {
		if ($reader === $getter) { continue; }
		member_fact_check($node->{'optional_' . $reader}() === null, 'Stale alternative retained');
		$rejected = false;
		try { $node->$reader(); } catch (\LogicException $error) { $rejected = true; }
		member_fact_check($rejected, 'Wrong-alternative read accepted');
	}
}
// Exercise actual maintenance dispatch, not only a direct call to clear_preparation.
Preparation_Cleanup::tree($node);
member_fact_check($node->preparation() === null, 'Cleanup retained a generic fact');
foreach (['enum_case_preparation', 'property_preparation', 'constant_preparation'] as $reader) {
	member_fact_check($node->{'optional_' . $reader}() === null, 'Cleanup retained a concrete fact');
}
$rejected = false;
try { $node->require_preparation(); } catch (\LogicException $error) { $rejected = true; }
member_fact_check($rejected, 'Unprepared read accepted');
$node->set_enum_case_preparation($case_facts);
member_fact_check($node->enum_case_preparation() === $case_facts && $case_facts->suspends,
	'Cleanup invalidated externally retained facts');
echo "Static member facts: identity, alternatives, maintenance cleanup and reprepare passed\n";
