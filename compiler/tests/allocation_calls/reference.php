<?php
declare(strict_types=1);
foreach(['resource_states.php','data/ownership.php','resource_locations.php','resource_effects.php','resource_aliasing.php'] as $file) { require __DIR__.'/../../reference/pre-rewrite/src/04_analyze/analyze_lifetimes/'.$file; }
require __DIR__.'/../../reference/pre-rewrite/src/04_analyze/type_model/data/resources.php';
final class Bridge {
    use \analyze_lifetimes\Resource_Effects;
    use \analyze_lifetimes\Resource_Aliasing;
    public int $failed=0;
    public function __construct(private array $locations,private array $parameters,private array $operands) {}
    private function fail(int $node,string $message): never { $this->failed=$node;throw new RuntimeException($message); }
    private function operand(int $call,int $position): \analyze_lifetimes\resource_location {return $this->operands[$position];}
    private function node(\analyze_lifetimes\resource_location $location): int {return 17;}
    public function run($effect,$flow,$borrows,$observations): void {$this->effect(1,$effect,$flow,$borrows,$observations);}
}
$out=[];
foreach(json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR) as $case) {
    $left=new \analyze_lifetimes\resource_location(1,[0]);$right=new \analyze_lifetimes\resource_location(2,[0]);
    $locations=['1:0'=>$left,'2:0'=>$right];$parameters=$case['parameter']?['1:0'=>true,'2:0'=>true]:[];
    $flow=new \analyze_lifetimes\resource_flow_state(['1:0'=>$case['state'],'2:0'=>$case['destination']],$case['preceding']?['2:0'=>true]:[]);
    $observations=$case['validate']?new \analyze_lifetimes\ownership_observations(array_fill_keys(array_keys($parameters),3)):null;
    $kind=\type_model\allocation_effect_kind::from($case['kind']);
    $effect=new \type_model\allocation_effect($kind,2,$case['kind']==='transfer'?0:null);
    $borrows=match($case['borrow']){0=>[],1=>[new \analyze_lifetimes\resource_location(1)],2=>[new \analyze_lifetimes\resource_location(2)],3=>[new \analyze_lifetimes\resource_location(1,[1])]};
    $bridge=new Bridge($locations,$parameters,[2=>$left,0=>$case['alias']?$left:$right]);$reason='';
    try{$bridge->run($effect,$flow,$borrows,$observations);}catch(RuntimeException $e){$reason=$e->getMessage();}
    $out[]=[$flow->states['1:0'],$flow->states['2:0'],$observations?->required['1:0']??-1,$observations?->required['2:0']??-1,count($flow->mutations),count($observations?->mutated??[]),count($observations?->accessed??[]),array_keys($observations?->distinct??[]),$reason,$bridge->failed];
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
