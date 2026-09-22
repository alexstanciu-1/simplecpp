<?php
declare(strict_types=1);
$count=0;
function check(bool $ok): void { global $count; if (!$ok) { throw new RuntimeException('Type-store invariant failed at '.$count); } $count++; }
function rejected_unchanged(\type_model\Type_Store $store,callable $operation): void {
    $before=serialize($store);
    try {$operation();}catch(InvalidArgumentException|LogicException $e){check(serialize($store)===$before);return;}
    throw new RuntimeException('Expected type-store rejection');
}
$context=new \type_model\Type_Context('c','p','t');$store=\type_model\Type_Store::fresh($context);
$id=$store->declare_type('x','');$store->set_representation($id,$store->intern_integer(32));
$before=serialize($store);$candidate=$store->fork();
$candidate->intern_structure([new \type_model\Type_Member($id,'field',true)]);
$candidate->declare_type('new','');$candidate->set_representation($id,$candidate->intern_float('ieee_binary64'));
check(serialize($store)===$before);check($store->member_count()===0);check($store->find_type('new','')===0);
check($candidate->lineage===$store->lineage);check($candidate->context===$store->context);
rejected_unchanged($store,fn()=>$store->intern_structure([new \type_model\Type_Member($id,'same',true),new \type_model\Type_Member($id,'same',false)]));
rejected_unchanged($store,fn()=>$store->intern_structure([new \type_model\Type_Member($id,'valid',true),new \type_model\Type_Member(999,'bad',true)]));
rejected_unchanged($store,fn()=>$store->intern_signature($id,[$id,999],[]));
rejected_unchanged($store,fn()=>$store->intern_signature($id,[$id],[99]));
rejected_unchanged($store,fn()=>$store->intern_signature($id,[$id],[0,0]));
rejected_unchanged($store,fn()=>$store->declare_type('x',''));
rejected_unchanged($store,fn()=>$store->intern_opaque(32,3));
rejected_unchanged($store,fn()=>$store->set_representation($id,999));
$catalog=\load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
$materialized=\type_model\Type_Store::fresh($context);$int=\resolve_types\Type_Cache::materialize($materialized,$catalog->entry_return_type);
$other=\load_runtime\Catalog_Syntax::parse(fs_read_text('inputs/catalog.json'));
rejected_unchanged($materialized,fn()=>$materialized->bind_definition($int,$other->entry_return_type));
$shape=$materialized->intern_integer(32);rejected_unchanged($materialized,fn()=>$materialized->set_representation($int,$shape));
$base=serialize($materialized);$fork=$materialized->fork();$fork->invalidate_definition($int);\resolve_types\Type_Cache::materialize($fork,$other->entry_return_type);
check(serialize($materialized)===$base);check($materialized->definition_for_type($int)===$catalog->entry_return_type);
check($fork->definition_for_type($int)===$other->entry_return_type);
try{$store->type_by_id($id)->representation_id=999;throw new RuntimeException('Expected readonly row');}catch(Error $e){check(true);}
check(serialize($store)===$before);
echo 'Host type-store invariants: ',$count," passed\n";
