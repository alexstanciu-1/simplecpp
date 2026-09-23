<?php
require dirname(__DIR__,2).'/reference/pre-rewrite/src/04_analyze/check_bodies/data/structures.php';
$cases=json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR);
$kinds=\check_bodies\statement_kind::cases();$writes=\check_bodies\local_write_kind::cases();$returns=\check_bodies\return_kind::cases();
foreach($cases as $row){
    if($row['mode']==='value'){
        $bytes='';for($i=0;$i<256;$i++){$bytes.=chr($i);}
        $literal=new \check_bodies\byte_literal($bytes);
        if($literal->jsonSerialize()!==['hex'=>$row['hex']]){throw new \LogicException('Binary view differs');}
        continue;
    }
    $failed=false;
    try{$s=new \check_bodies\typed_statement(23,$kinds[$row['kind']-1],$row['value'],3,4,$row['scope'],
        $row['target']?new \check_bodies\place(7):null,$writes[$row['write']-1],$returns[$row['ret']-1]);}
    catch(\LogicException $error){$failed=true;}
    if($failed===$row['valid']){throw new \LogicException('Statement acceptance differs');}
}
echo count($cases)-9," retained statement combinations and binary literal view passed\n";
