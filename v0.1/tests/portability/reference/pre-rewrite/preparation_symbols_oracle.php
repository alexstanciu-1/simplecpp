<?php
declare(strict_types=1);
require __DIR__.'/../../compiler/src-runtime-preparation/bootstrap.php';
require __DIR__.'/oracles/preparation_symbols_original.php';
function symbol_outcome(string $class,array $items): array {
    try {return ['value',$class::name($items)];}
    catch(InvalidArgumentException $e){return ['error',$e->getMessage()];}
}
$atoms=['','_','__','_X_','_x2E_','.','a_X_','_Xa','a','a_','a.b','é'];
$cases=[[],[1=> 'x'],['a'=> 'x'],[false],['x', 3],[null],['x',new stdClass()]];
foreach($atoms as $a){foreach($atoms as $b){$cases[]=[$a,$b];}}
$bytes='';for($i=0;$i<256;++$i){$bytes.=chr($i);$cases[]=[chr($i)];}$cases[]=[$bytes];
mt_srand(20260924);
for($trial=0;$trial<1000;++$trial){$items=[];for($j=0,$n=mt_rand(1,8);$j<$n;++$j){$part='';for($k=0,$m=mt_rand(0,30);$k<$m;++$k){$part.=chr(mt_rand(0,255));}$items[]=$part;}$cases[]=$items;}
foreach($cases as $id=>$items){
 $before=serialize($items);
 if(symbol_outcome(runtime_preparation\Baseline_Symbols::class,$items)!==symbol_outcome(runtime_preparation\Symbols::class,$items) || serialize($items)!==$before){throw new RuntimeException('Symbol mismatch: '.$id);}
}
foreach($atoms as $prefix){foreach($atoms as $suffix){if(runtime_preparation\Symbols::append($prefix,$suffix)!==runtime_preparation\Baseline_Symbols::append($prefix,$suffix)){throw new RuntimeException('Append mismatch');}}}
echo count($cases)," symbol identities and 144 append cases match frozen original, including rejection diagnostics\n";
