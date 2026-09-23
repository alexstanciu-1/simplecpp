<?php
declare(strict_types=1);
$count=0;
function verify(bool $ok): void {global $count;if(!$ok){throw new LogicException('Type record invariant '.$count);}$count++;}
foreach(['scalar','template','entry'] as $mode){
    $f=\signature_requests_test\Fixture::prepare($mode,0);$before=serialize($f);
    $names=$f->reader->annotations->bindings($f->input->owner);
    $ids=[];for($i=0;$i<$names->locals_count();$i++){$ids[]=7+$i;}
    $locals=new \resolve_types\Local_Types($names,$ids,$f->input->instance);
    verify($locals->names===$names);verify($locals->instance===$f->input->instance);
    verify($locals->size()===$names->locals_count());verify(serialize($f)===$before);
    if($ids!==[]){$ids[0]=99;verify($locals->type_for(1)===7);}
    foreach([-1,0,$locals->size()+1] as $bad){$caught=false;try{$locals->type_for($bad);}catch(OutOfBoundsException $e){$caught=true;}verify($caught);}
}
echo 'Host type record invariants: ',$count," passed\n";
