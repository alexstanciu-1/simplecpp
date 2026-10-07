<?php

/* Model-only proof: interface ownership is not record execution or inherited lookup. */
namespace scpp\compiler;

require_once dirname(__DIR__) . '/boot.php';

function member_owner_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

Compiler_Lifecycle::reset();
$source = new file();
$source->path = 'members.phs';
$source->content = 'interface Contract {}';
$tokens = (new Tokenizer($source))->tokenize();
$local_scope = new scope();
$local_scope->set_parent(Model::$global_scope);
$root = new file_node($local_scope);
$collection = new collected_file($tokens);
$collector = new Symbol_Collector($collection, $tokens, $local_scope, null, 1);
$node = new type_declaration_node();
$node->name = 'Contract';
$node->declaration_kind = type_declaration_kind::interface_type;
$node->set_span(0, $tokens->end_token);
$root->single_body()->declarations->append($node);
$node->collect($collector, $local_scope, 1);
$owner = $node->occurrence();
member_owner_check($owner instanceof collected_interface, 'Interface lost its distinct identity');
member_owner_check($owner instanceof collected_member_owner, 'Interface cannot own members');
member_owner_check(!($owner instanceof collected_record), 'Interface masquerades as a record');
$definition = $local_scope->types_named('Contract')[0];
$registry = Model::$type_catalog->registry();
$use = $registry->use($registry->canonical($definition)->type_id());
member_owner_check($definition->nominal_kind() === nominal_type_kind::interface_type, 'Interface identity became a class/struct');
member_owner_check($definition->capabilities() === [], 'Identity registration granted capabilities');
member_owner_check(Type_Queries::source_declaration($use) === $owner, 'Interface lost source association');
member_owner_check(Type_Queries::source_record($use) === null, 'Interface acquired record layout');
member_owner_check($node->preparation() === null, 'Collection manufactured layout facts');

$method = new method_node();
$method->name = 'read';
$method->set_span(0, 1);
$method->set_bodyless_declaration();
$node->methods->append($method);
$collector->collect_method($method, $owner, 0);
member_owner_check($method->occurrence()->declaring_type() === $owner, 'Method lost original declaring owner');
member_owner_check($method->occurrence()->qualified_name() === 'Contract::read', 'Method naming requires concrete storage');
member_owner_check($owner->syntax()->member_scope()->methods_named('read') === [$method->occurrence()], 'Method registration duplicated identity');
$constant = new member_constant_node();
$constant->name = 'read';
$constant->set_span(0, 1);
$constant->initializer = new integer_literal_node();
$constant->initializer->set_span(0, 1);
$node->constants->append($constant);
$collector->collect_constant($constant, $node->member_scope(), 0, $owner);
member_owner_check(weakref_get($constant->occurrence()->member_owner) === $owner, 'Constant lost shared owner role');
member_owner_check($local_scope->constants_named('read') === [], 'Member constant leaked into namespace');

$collector->finish($root);
$next = new Symbol_Collector($collection, $tokens, $local_scope, null, 2);
member_owner_check($next->previous_member_owner($local_scope, 'Contract', type_declaration_kind::interface_type) === $node, 'Interface identity cannot reconcile');
member_owner_check($next->previous_member_owner($local_scope, 'Contract', type_declaration_kind::class_type) === null, 'Kind change reused interface identity');
$node->collect($next, $local_scope, 1);
member_owner_check($node->occurrence() === $owner, 'Reuse replaced interface identity');
member_owner_check(count($local_scope->types_named('Contract')) === 1, 'Reuse duplicated registration');

$context = new preparation_context();
member_owner_check(Member_Preparation::accessible(member_visibility::public_access, $owner, $context), 'Public access rejected');
member_owner_check(!Member_Preparation::accessible(member_visibility::private_access, $owner, $context), 'Private access escaped its owner');
$context->member_owner = $owner;
member_owner_check(Member_Preparation::accessible(member_visibility::private_access, $owner, $context), 'Private access cannot use common owner');
foreach ([member_visibility::public_access, member_visibility::protected_access, member_visibility::private_access] as $visibility) {
	$modifier = match ($visibility) {
		member_visibility::public_access => declaration_modifier::public_access,
		member_visibility::protected_access => declaration_modifier::protected_access,
		member_visibility::private_access => declaration_modifier::private_access,
	};
	member_owner_check(Member_Preparation::visibility([$modifier]) === $visibility, 'Visibility was collapsed');
}
member_owner_check(CPP_Declarations::visibility(member_visibility::protected_access) === "protected:\n", 'Backend cannot spell settled protected access');
Member_Preparation::validate_modifiers([declaration_modifier::protected_access], true);
$worker = new Preparation_Worker(Model::$global_scope);
$worker->select_record($node);
member_owner_check($owner->preparation_work_owner() === null && $owner->contract_work !== null,
	'Interface contract selection manufactured record layout work');
echo "Member-owner identity, registration, visibility and execution gates passed\n";
