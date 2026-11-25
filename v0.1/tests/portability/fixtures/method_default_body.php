<?php
namespace scpp\compiler;

// Only transfer preparation is exercised; unused context/type fields stay unset.
$default_context = new preparation_context();
$default_context->break_target = new while_node();
$default_body = new block_node();
$default_nodes /** Storage<statement_node> */ = $default_body->statements;
$first_break = new break_node();
$default_nodes->append($first_break);
$omitted = Body_Preparation::prepare_statements($default_body, $default_context);
if ($omitted->can_fall_through || !$omitted->can_break) {
    throw new \LogicException('omitted start must process first statement');
}
$explicit_zero = Body_Preparation::prepare_statements($default_body, $default_context, 0);
if ($explicit_zero->can_fall_through || !$explicit_zero->can_break) {
    throw new \LogicException('explicit zero must process first statement');
}
if ($first_break->preparation() === null) { throw new \LogicException('first statement facts missing'); }

$skip_body = new block_node();
$skip_nodes /** Storage<statement_node> */ = $skip_body->statements;
$invalid_continue = new continue_node();
$later_break = new break_node();
$skip_nodes->append($invalid_continue);
$skip_nodes->append($later_break);
$skipped = Body_Preparation::prepare_statements($skip_body, $default_context, 1);
if ($skipped->can_fall_through || !$skipped->can_break) { throw new \LogicException('skip lost later completion'); }
if ($invalid_continue->preparation() !== null) { throw new \LogicException('skip prepared first statement'); }
if ($later_break->preparation() === null) { throw new \LogicException('skip lost later facts'); }
$omitted_failed = false;
try { Body_Preparation::prepare_statements($skip_body, $default_context); }
catch (\RuntimeException $error) { $omitted_failed = true; }
if (!$omitted_failed) { throw new \LogicException('omitted start skipped invalid first statement'); }
$zero_failed = false;
try { Body_Preparation::prepare_statements($skip_body, $default_context, 0); }
catch (\RuntimeException $error) { $zero_failed = true; }
if (!$zero_failed) { throw new \LogicException('explicit zero skipped invalid first statement'); }
