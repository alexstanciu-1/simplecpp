<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

$function = function_node::fresh_node();
$method = method_node::fresh_node();
$closure = closure_node::fresh_node();
$other = closure_node::fresh_node();
if (($function->signature_parts() === $method->signature_parts())
	|| ($closure->signature_parts() === $other->signature_parts())
	|| ($closure->signature_scope() === $other->signature_scope())
	|| ($closure->body_scope() === $other->body_scope())
	|| ($function->retired_bodies === $method->retired_bodies)
	|| ($closure->captures === $other->captures)) {
	throw new \LogicException('Callable factories shared fresh owned state');
}
if (!$function->signature_scope()->is_function()
	|| !$method->signature_scope()->is_function()
	|| !$closure->signature_scope()->is_function()
	|| !$closure->body_scope()->is_function()
	|| ($closure->body_scope()->parent_scope() !== $closure->signature_scope())
	|| ($closure->signature_scope()->parent_scope() !== null)) {
	throw new \LogicException('Callable construction lost scope configuration');
}
$signature = new callable_signature_syntax();
$signature_scope = new scope();
$body_scope = new scope();
$supplied = new closure_node($signature, $signature_scope, $body_scope);
if (($supplied->signature_parts() !== $signature)
	|| ($supplied->signature_scope() !== $signature_scope)
	|| ($supplied->body_scope() !== $body_scope)) {
	throw new \LogicException('Callable construction replaced its explicit inputs');
}
echo "Callable construction freshness, input identity and scope wiring passed\n";
