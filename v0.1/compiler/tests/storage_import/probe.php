<?php
declare(strict_types=1);
namespace storage_import_test;
final class Probe {
    public static function run(string $text): void {
        $cases=json_read($text);
        for ($index=0;$index<$cases->size();$index++) {
            $fixture=$cases->at($index); $descriptor_mode=$fixture->member('descriptor_mode')->integer();
            $policy=new \type_model\Lifetime_Policy(); $policy->construction=2;
            $operations /** vector<\type_model\Lifecycle_Operation> */ = [\type_model\Lifecycle_Operation::runtime('provider','ctor','ctor','ccc',1)];
            if ($descriptor_mode===1) { $policy->construction=0; $empty /** vector<\type_model\Lifecycle_Operation> */ = []; $operations=$empty; }
            if ($descriptor_mode===2) { $policy->copy=1; }
            if ($descriptor_mode===3) { $policy->cleanup=1; $operations[]=\type_model\Lifecycle_Operation::runtime('provider','dtor','dtor','ccc',2); }
            $namespace_name=''; if ($descriptor_mode===4) { $namespace_name='ns'; }
            $descriptor=new \type_model\Named_Definition('Storage',$namespace_name,\type_model\Representation::opaque(16,8),new \type_model\Lifetime_Contract($policy,$operations),null,'',false,false,false);
            $plain_policy=new \type_model\Lifetime_Policy(); $plain_operations /** vector<\type_model\Lifecycle_Operation> */ = [];
            $counter=new \type_model\Named_Definition('Counter','',\type_model\Representation::integer(64),new \type_model\Lifetime_Contract($plain_policy,$plain_operations),true,'',false,false,false);
            $void_definition=new \type_model\Named_Definition('Void','',\type_model\Representation::void_type(),null,null,'',false,false,false);
            $types /** hash<\load_runtime\Runtime_Type> */ = [];
            $types['descriptor']=new \load_runtime\Runtime_Type('descriptor',new \load_runtime\Runtime_Storage(4,16,8),null,null,$descriptor);
            $types['counter']=new \load_runtime\Runtime_Type('counter',new \load_runtime\Runtime_Storage(0,8,8),64,$fixture->member('counter_mode')->integer()!==1,$counter);
            if ($fixture->member('counter_mode')->integer()===2) { $types['counter']=new \load_runtime\Runtime_Type('counter',new \load_runtime\Runtime_Storage(0,8,8),64,true,null); }
            $types['other_counter']=new \load_runtime\Runtime_Type('other_counter',new \load_runtime\Runtime_Storage(0,8,8),64,true,$counter);
            $types['address']=new \load_runtime\Runtime_Type('address',new \load_runtime\Runtime_Storage(1,8,8),null,null,null);
            if ($fixture->member('void_mode')->integer()===0) { $types['void']=new \load_runtime\Runtime_Type('void',new \load_runtime\Runtime_Storage(3,0,1),null,null,$void_definition); }
            $accepted=true; $correct=true;
            try {
                $rows=\load_runtime\Package_Syntax::rows($fixture->member('rows'),'type');
                $raw_operations=\load_runtime\Package_Syntax::rows($fixture->member('operations'),'operation');
                $families=\load_runtime\Storage_Import::storage_families($rows,$types,$raw_operations,'provider');
                if (!$rows[0]->has('storage_family')) { $correct=q_count($families)===0; }
                else {
                    $correct=q_count($families)===1; $family=$families[0];
                    $correct=$correct && ($family->descriptor===$descriptor) && ($family->counter===$counter) && ($family->void_type===$void_definition) && ($family->provider==='provider') && ($family->id==='descriptor');
                    $roles /** vector<string> */ = ['allocate','next','commit','at','pop','count','release','transfer'];
                    $counts /** vector<int> */ = [4,1,1,2,1,1,1,2]; $results /** vector<int> */ = [0,2,0,2,0,1,0,0];
                    for ($slot=0;$slot<8;$slot++) {
                        $primitive=$family->primitive_for($roles[$slot]); $result=$primitive->result; $kind=0;
                        if ($result!==null) { $kind=$result->kind; }
                        $correct=$correct && ($primitive->link_name==='primitive_'.$roles[$slot]) && ($primitive->parameter_count()===$counts[$slot]) && ($kind===$results[$slot]);
                        $first=$primitive->parameter_at(0); $read_only=($slot===3)||($slot===5);
                        $correct=$correct && ($first->kind===2) && ($first->mutable===!$read_only);
                    }
                    for ($role=0;$role<6;$role++) { $correct=$correct && ($family->operation_name($role)==='storage_'.\type_model\Storage_Roles::name($role)); }
                }
            } catch (\RuntimeException $error) { $accepted=false; }
            echo (($accepted===$fixture->member('accept')->boolean()) && $correct) ? "true\n" : "false\n";
        }
    }
}
