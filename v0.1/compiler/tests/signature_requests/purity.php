<?php
declare(strict_types=1);
require __DIR__.'/probe.php';
$count=0;
function verify(bool $ok): void {global $count;if(!$ok){throw new LogicException('Signature purity assertion '.$count);}$count++;}
function rejects(callable $call): void {try{$call();}catch(LogicException $error){verify(true);return;}catch(OutOfBoundsException $error){verify(true);return;}throw new LogicException('Missing rejection');}
foreach(['scalar','copy','template','provider','family','storage'] as $mode){
    $f=\signature_requests_test\Fixture::prepare($mode,0);$before=serialize($f);
    $request=\resolve_types\Signature_Resolver::resolve($f->symbols,$f->reader,$f->input,$f->entry,$f->prepared);
    verify(serialize($f)===$before);
    verify(\resolve_types\Callable_Inputs::is_current($f->input,$f->symbols,$f->instances->view()));
    $all=\resolve_types\Callable_Inputs::all($f->symbols,$f->instances->view());$found=false;
    foreach($all as $input){if($input->callable_id===$f->input->callable_id){$found=$input->owner===$f->input->owner && $input->instance===$f->input->instance;}}verify($found);
    if($mode==='scalar'){
        $parameters=[$request->parameter_at(0)];$passing=[\type_model\PASS_VALUE];$copy=new \resolve_types\Signature_Request($f->input,1,$request->definition,$parameters,$passing);$parameters=[];$passing=[];verify($copy->parameter_count()===1);verify($copy->passing_at(0)===\type_model\PASS_VALUE);
        $default=new \resolve_types\Signature_Request($f->input,1,$request->definition,[$request->parameter_at(0)],[]);verify($default->passing_at(0)===\type_model\PASS_VALUE);
        rejects(fn()=>new \resolve_types\Signature_Request($f->input,1,$request->definition,[],[0]));
        rejects(fn()=>new \resolve_types\Signature_Request($f->input,1,$request->definition,[$request->parameter_at(0)],[99]));
        rejects(fn()=>$copy->parameter_at(1));rejects(fn()=>$copy->passing_at(-1));
    }
    if($mode==='template'){rejects(fn()=>new \resolve_types\Callable_Input($f->entry->symbol,$f->input->instance));}
}
echo 'Host signature invariants: ',$count," passed\n";
