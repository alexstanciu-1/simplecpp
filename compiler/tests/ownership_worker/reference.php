<?php
declare(strict_types=1);
$base=__DIR__.'/../../reference/pre-rewrite/src/04_analyze/';
foreach(['type_model/data/lifecycle.php','type_model/data/definitions.php','analyze_lifetimes/resource_states.php','analyze_lifetimes/data/ownership.php','analyze_lifetimes/resource_locations.php','analyze_lifetimes/ownership_worker.php'] as $file){require $base.$file;}
// Only name and resource paths are read by this retained worker. No type construction is proved by this bridge.
$reflection=new ReflectionClass(\type_model\named_type_definition::class);$definition=$reflection->newInstanceWithoutConstructor();
$reflection->getProperty('name')->setValue($definition,'Nested');$reflection->getProperty('resource_paths')->setValue($definition,[[0,0]]);
function summary(int $required,int $result,string $path,bool $source): \analyze_lifetimes\ownership_summary {
    $parameters=[0=>[$path=>new \analyze_lifetimes\resource_transition($required,$result,true,true)]];$pairs=[];
    if($source){$parameters[1]=[$path=>new \analyze_lifetimes\resource_transition(2,9,false,true)];$pairs['0:'.$path.'|1:'.$path]=['0:'.$path,'1:'.$path];}
    return new \analyze_lifetimes\ownership_summary($parameters,$pairs);
}
$out=[];
foreach(json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR) as $case){
    $children=[];$dependencies=[];
    if($case['child']){$children[0]='child';$dependencies['child']=summary($case['required'],$case['result'],'0',$case['source']);}
    if($case['body']){$dependencies['body:7']=summary(1,10,'0.0',false);}
    $subject=new \analyze_lifetimes\ownership_lifecycle(1,$definition,\type_model\lifecycle_operation_kind::from($case['role']),$children,$case['body']?7:null);
    try{$result=\analyze_lifetimes\Ownership_Worker::prepare(new \analyze_lifetimes\ownership_task('lifecycle',$subject,$dependencies));$rows=[];
        foreach($result->summary->parameters as $position=>$fields){foreach($fields as $path=>$transition){$rows[]=[$position,(string)$path,$transition->required,$transition->result,$transition->mutates,$transition->accessed];}}
        $out[]=[true,$rows,array_keys($result->summary->distinct)];
    }catch(RuntimeException $error){$out[]=[false,$error->getMessage()];}
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
