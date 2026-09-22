<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/bootstrap.php';
function check(bool $ok,string $why):void {if(!$ok)throw new LogicException($why);}
$op=\type_model\Lifecycle_Operation::runtime('provider','copy','link','ccc',3);
$policy=new \type_model\Lifetime_Policy();$policy->copy=2;
$ops=[$op];$contract=new \type_model\Lifetime_Contract($policy,$ops);$before=serialize($contract);
$policy->copy=0;$ops=[];
check($contract->operation(3)===$op,'Contract retains authoritative operation identity');
check(serialize($contract)===$before,'Policy/list edits preserve accepted contract');
$members=[new \type_model\Lifecycle_Member(1,0,$op,3)];
$source=\type_model\Lifecycle_Operation::source(2,'source',3,$members,0,'ccc',0);$before=serialize($source);
$members=[];$order=$source->order();$order->member_kind=0;
check(serialize($source)===$before,'Member-list and order-view edits preserve source plan');
try {$source->member_at(-1);throw new RuntimeException('Expected bounds rejection');}catch(InvalidArgumentException $e){}
check(serialize($source)===$before,'Failed access preserves source plan');
echo "Lifetime purity: 4 assertions passed\n";
