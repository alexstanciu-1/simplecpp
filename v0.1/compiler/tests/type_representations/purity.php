<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/bootstrap.php';
function check(bool $ok,string $why): void {if(!$ok)throw new LogicException($why);}
$modes=[\type_model\PASS_VALUE,\type_model\PASS_BORROW_CONST];
$signature=\type_model\Representation::signature(2,0,2,$modes,\type_model\RESULT_VALUE);
$before=serialize($signature);$modes[0]=\type_model\PASS_BYTE_SPAN;
check($signature->parameter_passing(0)===\type_model\PASS_VALUE,'Input vector mutation cannot change signature');
try{$signature->parameter_passing(2);throw new RuntimeException('Expected index failure');}catch(InvalidArgumentException $e){}
check(serialize($signature)===$before,'Failed accessor preserves representation');
try{$signature->opaque_size();throw new RuntimeException('Expected shape failure');}catch(LogicException $e){}
check(serialize($signature)===$before,'Wrong shape accessor preserves representation');
// Host boundary checks additionally guard against an accidental floating-point rewrite.
$large=\type_model\Representation::opaque(4611686018427387904,4611686018427387904);
check($large->opaque_alignment()===4611686018427387904,'Large exact integer alignment');
try{\type_model\Representation::opaque(PHP_INT_MAX,PHP_INT_MAX);throw new RuntimeException('Expected non-power-of-two failure');}catch(InvalidArgumentException $e){}
check(serialize($signature)===$before,'Unrelated construction failure preserves existing shape');
echo "Representation purity: 5 assertions passed\n";
