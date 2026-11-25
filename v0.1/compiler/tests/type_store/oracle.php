<?php
declare(strict_types=1);
$root=dirname(__DIR__,2).'/reference/pre-rewrite/src/';
require $root.'compile/step.php';
require $root.'04_analyze/type_model/data/semantic_calls.php';
require $root.'04_analyze/type_model/data/representations.php';
require $root.'04_analyze/type_model/result_contracts.php';
require $root.'04_analyze/type_model/data/store.php';
$types=new \type_model\Type_Store(new \type_model\type_context('c','p','t'));
$a=$types->declare_type('a','fixture');$b=$types->declare_type('b','fixture');$int=$types->intern_integer(32);
$types->set_representation($a,$int);$types->set_representation($b,$int);
$fields=[new \type_model\type_member($a,'x'),new \type_model\type_member($b,'y',false)];
$structure=$types->intern_structure($fields);$shape=$types->representation_by_id($structure)->payload;
$signature=$types->intern_signature($a,[$a,$b]);$call=$types->representation_by_id($signature)->payload;
$before=$types->to_json();$candidate=clone $types;$candidate->set_representation($b,$candidate->intern_integer(64));
$preserved=[$a!==$b,$types->representation_for_type($a)===$types->representation_for_type($b),
    $types->intern_structure($fields)===$structure,$shape->count,$types->member_at($shape->first+1)->name,
    $types->member_at($shape->first+1)->writable,$call->return_type,count($call->parameter_passing),
    $before===$types->to_json(),$candidate->lineage===$types->lineage,$candidate->type_by_id($a)===$types->type_by_id($a),
    $candidate->type_by_id($b)!==$types->type_by_id($b),$types->representation_for_type($b)->payload->bit_width];
// Record the retained key defect separately from the preserved contract expectations.
$old=$types->intern_signature($a,[]);$types->set_representation($a,$structure);$new=$types->intern_signature($a,[]);
echo json_encode(['preserved'=>$preserved,'retained_signature_key_omits_production'=>$old===$new],JSON_THROW_ON_ERROR),"\n";
