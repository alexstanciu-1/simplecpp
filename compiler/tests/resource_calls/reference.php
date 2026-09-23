<?php
declare(strict_types=1);
foreach(['resource_states.php','data/ownership.php','resource_locations.php','resource_effects.php','resource_aliasing.php'] as $file) { require __DIR__.'/../../reference/pre-rewrite/src/04_analyze/analyze_lifetimes/'.$file; }
final class Bridge {
    use \analyze_lifetimes\Resource_Effects;
    use \analyze_lifetimes\Resource_Aliasing;
    public int $failed=0;
    public function __construct(private array $locations,private array $parameters) {}
    private function fail(int $node,string $message): never { $this->failed=$node;throw new RuntimeException($message); }
    public function run($summary,$operands,$flow,$borrows,$observations,$fields): void {
        if($fields){$this->apply_fields($operands[0],$summary->parameters[0],$flow,$borrows,$observations,17);}
        else {$this->apply_summary($summary,$operands,$flow,$borrows,$observations,17);}
    }
}
function transition(string $kind): \analyze_lifetimes\resource_transition {
    return match($kind) {
        'release'=>new \analyze_lifetimes\resource_transition(2,5,true,true),
        'acquire'=>new \analyze_lifetimes\resource_transition(1,10,true,true),
        'mutate'=>new \analyze_lifetimes\resource_transition(2,9,true,true),
        'inspect'=>new \analyze_lifetimes\resource_transition(2,9,false,true),
        'observe'=>new \analyze_lifetimes\resource_transition(3,9,false,true),
        'unused'=>new \analyze_lifetimes\resource_transition(3,9,false,false),
    };
}
$out=[];
foreach(json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR) as $case) {
    $left=new \analyze_lifetimes\resource_location(1,[0]);$right=new \analyze_lifetimes\resource_location(2,[0]);
    $locations=['1:0'=>$left,'2:0'=>$right];$parameters=$case['parameter']?['1:0'=>true,'2:0'=>true]:[];
    $flow=new \analyze_lifetimes\resource_flow_state(['1:0'=>$case['state'],'2:0'=>10],$case['preceding']?['2:0'=>true]:[]);
    $observations=$case['validate']?new \analyze_lifetimes\ownership_observations(array_fill_keys(array_keys($parameters),3)):null;
    $summary=new \analyze_lifetimes\ownership_summary([0=>[''=>transition($case['first'])],1=>[''=>transition($case['second'])]],$case['distinct']?['0:|1:'=>['0:','1:']]:[]);
    $borrows=match($case['borrow']){0=>[],1=>[new \analyze_lifetimes\resource_location(1)],2=>[new \analyze_lifetimes\resource_location(2)],3=>[new \analyze_lifetimes\resource_location(1,[1])]};
    $bridge=new Bridge($locations,$parameters);$reason='';
    try{$bridge->run($summary,[$left,$case['alias']?$left:$right],$flow,$borrows,$observations,$case['fields']);}catch(RuntimeException $e){$reason=$e->getMessage();}
    $out[]=[$flow->states['1:0'],$flow->states['2:0'],$observations?->required['1:0']??-1,$observations?->required['2:0']??-1,count($flow->mutations),count($observations?->mutated??[]),count($observations?->accessed??[]),array_keys($observations?->distinct??[]),$reason,$bridge->failed];
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
