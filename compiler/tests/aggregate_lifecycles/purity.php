<?php
// Host-only mutation/domain checks supplement the shared native outcome fixture.
$types=\type_model\Type_Store::fresh(new \type_model\Type_Context('c','p','t'));
$id=$types->declare_type('owner','');
$members=[]; $bodies=new \resolve_types\Lifecycle_Bodies();
$before=serialize([$types,$members,$bodies]);
\resolve_types\Lifecycle_Composition::derive($types,$id,$members,0,$bodies);
if ($before!==serialize([$types,$members,$bodies])) { throw new RuntimeException('Composition mutated inputs'); }
$count=1;
$reject=function(callable $f) use (&$count): void { try { $f(); } catch (Throwable $e) { ++$count; return; } throw new RuntimeException('Expected rejection'); };
$reject(fn()=>\resolve_types\Lifecycle_Composition::derive($types,999,[],0,$bodies));
$pending=$types->reference_type('pending','');
$reject(fn()=>\resolve_types\Lifecycle_Composition::derive($types,$pending,[],0,$bodies));
$reject(fn()=>\resolve_types\Lifecycle_Composition::derive($types,$id,[999],0,$bodies));
$void=$types->declare_type('void',''); $shape=$types->intern_void(); $types->set_representation($void,$shape);
$types->bind_definition($void,new \type_model\Named_Definition('void','',$types->representation_by_id($shape),null,null,'',false,false,false));
$reject(fn()=>\resolve_types\Lifecycle_Composition::derive($types,$id,[$void],0,$bodies));
$p=new \type_model\Lifetime_Policy(); $p->construction=1; $p->copy=1; $p->assignment=1; $p->expiring=1;
$life=new \type_model\Lifetime_Contract($p,[]);
$reject(fn()=>new \type_model\Named_Definition('float','',\type_model\Representation::floating('ieee_binary32'),$life,null,'',false,false,true));
$reject(fn()=>new \type_model\Named_Definition('opaque','',\type_model\Representation::opaque(8,8),$life,null,'',true,false,true));
$reject(fn()=>new \type_model\Named_Definition('opaque','',\type_model\Representation::opaque(8,8),null,null,'',false,false,true));
echo json_encode(['host_checks'=>$count]),"\n";
