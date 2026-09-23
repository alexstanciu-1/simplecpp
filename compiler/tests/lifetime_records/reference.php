<?php
declare(strict_types=1);
require __DIR__.'/../../reference/pre-rewrite/src/04_analyze/analyze_lifetimes/data/structures.php';
$out=[];
foreach(json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR) as $case) {
    extract($case,EXTR_OVERWRITE);
    try {
        if($kind==='value') { $row=new \analyze_lifetimes\value_lifetime($a,$b,\analyze_lifetimes\lifetime_end::from($name),$c);$out[]=['value',$row->value_id,$row->statement_id,$row->end->value,$row->consumer_id]; }
        elseif($kind==='cleanup') { $row=new \analyze_lifetimes\cleanup_obligation(\analyze_lifetimes\cleanup_subject::from($name),$a,$b,$c);$out[]=['cleanup',$row->subject->value,$row->subject_id,$row->after_statement,$row->block_id]; }
        elseif($kind==='local') { $row=new \analyze_lifetimes\local_lifetime($a,$b,$c,\analyze_lifetimes\local_end::from($name),$d);$out[]=['local',$row->local_id,$row->initialized_statement_id,$row->end_after_statement,$row->end->value,$row->block_id]; }
        else { $row=new \analyze_lifetimes\active_local($a,$b);$out[]=['active',$row->local_id,$row->initialized_statement_id]; }
    } catch(\LogicException $error) { $out[]=false; }
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
