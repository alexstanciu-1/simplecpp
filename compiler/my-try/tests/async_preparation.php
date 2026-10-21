<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function async_check(bool $value, string $message): void
{
	if (!$value) { throw new \LogicException($message); }
}

$directory = $argv[1];
mkdir($directory);
$path = $directory . '/main.phs';
$valid = 'async function child(int $n):int{await async_sleep_ms(0);return $n;}'
	. 'async function parent():int{return await child(42);}'
	. 'async function empty_task():void{await async_sleep_ms(0);}'
	. 'function sync():int{return await parent();}'
	. 'echo async_wait(parent());await empty_task();';
file_put_contents($path, $valid);
$compiler = new Compiler();
$compiler->init([$directory]);
$compiler->sync([$path]);
$compiler->prepare();
$parent = object_cast(Model::$global_scope->functions_named('parent')[0], collected_function::class);
$signature = $parent->signature();
async_check($signature->async_value_type !== null, 'Missing async value contract');
async_check(Type_Queries::canonical($signature->return_type)->definition() instanceof async_task_type_definition, 'Async call must return a task');
$body = $parent->syntax()->require_body();
$returned = object_cast($body->statements[0], return_node::class);
$awaited = object_cast($returned->expression, await_expression_node::class);
async_check($awaited->require_await_preparation()->operation === await_operation::suspend, 'Nested await must suspend');
$synchronous = object_cast(Model::$global_scope->functions_named('sync')[0], collected_function::class);
$sync_return = object_cast($synchronous->syntax()->require_body()->statements[0], return_node::class);
$sync_await = object_cast($sync_return->expression, await_expression_node::class);
async_check($sync_await->require_await_preparation()->operation === await_operation::synchronous_wait, 'Synchronous await must wait');
$invalid = [
	'async function child(int &$n):int{return $n;} $n=1;echo await child($n);',
	'async function &child(int &$n):int{return $n;}',
	'async function child():int{return 1;} $task=child();',
	'async function child():int{return 1;} echo child();',
	'await 42;',
	'await async_sleep_ms(1);',
	'async function child():void{async_sleep_ms(1);}',
	'async function child():int{return "bad";}',
	'async function child():int{}',
	'async_wait(1);',
	'async function child():void{await async_sleep_ms();}',
];
foreach ($invalid as $source) {
	file_put_contents($path, $source);
	$failed = false;
	try { $compiler->sync([$path]); $compiler->prepare(); }
	catch (\RuntimeException $error) { $failed = true; echo $error->getMessage(), "\n"; }
	async_check($failed, 'Invalid async source admitted: ' . $source);
	file_put_contents($path, $valid);
	$compiler->sync([$path]);
	$compiler->prepare();
	$compiler->sync([]);
	$compiler->prepare();
}
// Normal lexical shadowing still controls compiler helper selection.
file_put_contents($path, 'function async_wait(int $n):int{return $n;} echo async_wait(7);');
$compiler->sync([$path]);
$compiler->prepare();
echo "Async preparation, rejection and retained repair passed\n";
