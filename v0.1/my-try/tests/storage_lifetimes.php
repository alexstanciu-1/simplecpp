<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function lifetime_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

$root = new local_environment(null);
$early = new prepared_binding(); $root->add('early', $early);
$block = new local_environment($root);
$inner = new prepared_binding(); $block->add('inner', $inner);
$inner_copy = new prepared_binding(); $block->add('copy', $inner_copy);
$late = new prepared_binding(); $root->add('late', $late);
$sibling = new local_environment($root);
$sibling_local = new prepared_binding(); $sibling->add('sibling', $sibling_local);
$early_life = $root->lifetime_of($early); $inner_life = $block->lifetime_of($inner);
$copy_life = $block->lifetime_of($inner_copy); $late_life = $root->lifetime_of($late);
lifetime_check(Storage_Lifetimes::outlives($early_life, $inner_life), 'Outer earlier local must outlive block');
lifetime_check(Storage_Lifetimes::outlives($inner_life, $copy_life), 'Earlier block local must outlive copy');
lifetime_check(!Storage_Lifetimes::outlives($inner_life, $late_life), 'Block local escaped into outer later storage');
lifetime_check(!Storage_Lifetimes::outlives($late_life, $inner_life), 'Later outer local predates entered region');
lifetime_check(!Storage_Lifetimes::outlives($inner_life, $sibling->lifetime_of($sibling_local)), 'Sibling regions overlap');
lifetime_check(!Storage_Lifetimes::outlives($copy_life, $inner_life), 'Same-region establishment order was lost');
lifetime_check($block->lifetime_of($early) === $early_life, 'Inherited lookup copied the lifetime identity');

// Independent invocation roots may rely on explicit environment guarantees, not ancestry.
$invocation = new local_environment(null); $local = new prepared_binding(); $invocation->add('local', $local);
$context = new preparation_context(); $context->locals = $invocation;
$context->invocation_lifetime = $invocation->region; $context->borrowed_lifetimes = new Storage();
$origins = new Storage(); $origins->append($inner_life);
$rejected = false;
try { Storage_Lifetimes::require_destination($origins, $invocation->lifetime_of($local), $context); }
catch (\RuntimeException $error) { $rejected = true; }
lifetime_check($rejected, 'Unrelated invocation silently assumed a lifetime');
$context->borrowed_lifetimes->append($inner_life);
Storage_Lifetimes::require_destination($origins, $invocation->lifetime_of($local), $context);
$rejected = false;
try { Storage_Lifetimes::require_destination($origins, $late_life, $context); }
catch (\RuntimeException $error) { $rejected = true; }
lifetime_check($rejected, 'Invocation guarantee authorized external storage escape');
echo "Storage lifetime regions: PASS\n";
