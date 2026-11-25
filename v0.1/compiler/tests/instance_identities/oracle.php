<?php
$retained=!class_exists('instantiate\\Instance_Identities');
if ($retained) { require __DIR__.'/../../reference/pre-rewrite/src/04_analyze/instantiate/identities.php'; }
$types=\type_model\Type_Store::fresh(new \type_model\Type_Context('c','p','t'));
$p=new \type_model\Lifetime_Policy(); $p->copy=1; $p->construction=1; $p->assignment=1; $p->expiring=1;
$life=new \type_model\Lifetime_Contract($p,[]); $definitions=[];
foreach (['word','other'] as $name) { $definitions[]=new \type_model\Named_Definition($name,'',\type_model\Representation::integer(32),$life,true,'',false,false,true); }
$keys=[]; $next=1; $ids=$retained ? null : new \instantiate\Instance_Identities($types->lineage); $out=[];
foreach ([1,2,1] as $declaration) {
    foreach ([null,'','0','00','1',':',';','1:t;','é',"\0"] as $value) {
        foreach ($definitions as $definition) {
            $arguments=[new \instantiate\Template_Argument($definition,$value)];
            $out[]=$retained ? \instantiate\Instance_Identities::allocate($types,$declaration,$arguments,$keys,$next) : $ids->allocate($types,$declaration,$arguments);
        }
    }
}
echo json_encode($out),"\n";
