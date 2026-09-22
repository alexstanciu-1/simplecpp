<?php
declare(strict_types=1);
namespace callable_contracts_test;
final class Probe {
    public static function reference(int $mode): \type_model\Type_Reference {
        if ($mode === 0) { return \type_model\Type_Reference::named('T','ns'); }
        if ($mode === 1) { return \type_model\Type_Reference::named('U','ns'); }
        if ($mode === 2) { return \type_model\Type_Reference::named('T','other'); }
        if ($mode === 3) { return \type_model\Type_Reference::provided('p','T'); }
        if ($mode === 4) { return \type_model\Type_Reference::provided('q','T'); }
        if ($mode === 5) { return \type_model\Type_Reference::provided('p','U'); }
        if ($mode === 6) { return \type_model\Type_Reference::parameter('owner',0); }
        if ($mode === 7) { return \type_model\Type_Reference::parameter('owner',1); }
        if ($mode === 8) { return \type_model\Type_Reference::parameter('other',0); }
        $arguments /** vector<\type_model\Type_Reference> */ = [\type_model\Type_Reference::named('T','ns'),\type_model\Type_Reference::parameter('owner',0)];
        if ($mode === 10) { $arguments[1] = \type_model\Type_Reference::parameter('owner',1); }
        if ($mode === 11) { $arguments[0] = \type_model\Type_Reference::parameter('owner',0); $arguments[1] = \type_model\Type_Reference::named('T','ns'); }
        if ($mode === 12) { $arguments[] = \type_model\Type_Reference::named('T','ns'); }
        $key = 'Family'; if ($mode === 13) { $key = 'OtherFamily'; }
        $nested /** vector<\type_model\Type_Reference> */ = [\type_model\Type_Reference::family($key,$arguments)];
        return \type_model\Type_Reference::family('Outer',$nested);
    }
    public static function make(int $mode): \type_model\Runtime_Callable {
        $provider = 'p'; $id = 'f'; $name = 'F'; $namespace_name = 'ns'; $link = 'link'; $convention = 'ccc';
        if ($mode === 1) { $provider = 'q'; } if ($mode === 2) { $id = 'g'; }
        if ($mode === 3) { $name = 'G'; } if ($mode === 4) { $namespace_name = 'other'; }
        if ($mode === 5) { $link = 'other_link'; } if ($mode === 6) { $convention = 'fastcc'; }
        $type = Probe::reference(9); $result_type = Probe::reference(0);
        if ($mode === 7) { $type = Probe::reference(10); }
        if ($mode === 8) { $result_type = Probe::reference(1); }
        $bits = 64; $extension = 0; $length_bits = 64; $length_extension = 0;
        if ($mode === 9) { $bits = 32; } if ($mode === 10) { $extension = 1; }
        if ($mode === 11) { $length_bits = 32; } if ($mode === 12) { $length_extension = 2; }
        $is_mutable = $mode === 13; $passing = 1; if ($is_mutable) { $passing = 2; }
        $parameters /** vector<\type_model\Semantic_Parameter> */ = [new \type_model\Semantic_Parameter($type,0),new \type_model\Semantic_Parameter($type,$passing),new \type_model\Semantic_Parameter($type,3)];
        $physical /** vector<\type_model\Runtime_Abi_Position> */ = [\type_model\Runtime_Abi_Position::integer($bits,$extension),\type_model\Runtime_Abi_Position::borrow($is_mutable),\type_model\Runtime_Abi_Position::byte_span(\type_model\Runtime_Abi_Position::integer($length_bits,$length_extension))];
        if ($mode === 14) { $parameters[] = new \type_model\Semantic_Parameter($type,0); $physical[] = \type_model\Runtime_Abi_Position::integer(64,0); }
        $production = 1; $transport = 0;
        if ($mode === 15) { $production = 0; }
        if ($mode === 16) { $production = 2; $transport = 1; }
        $signature = new \type_model\Semantic_Signature($parameters,new \type_model\Semantic_Result($result_type,$production));
        if (($mode > 18) && ($mode < 23)) {
            $effect = new \type_model\Allocation_Effect(4,0);
            if ($mode === 20) { $effect = new \type_model\Allocation_Effect(4,1); }
            if ($mode === 21) { $effect = new \type_model\Allocation_Effect(3,0,1); }
            if ($mode === 22) { $effect = new \type_model\Allocation_Effect(3,0,2); }
            $signature = new \type_model\Semantic_Signature($parameters,new \type_model\Semantic_Result($result_type,$production),$effect);
        }
        $result_bits = 64; $result_extension = 0;
        if ($mode === 17) { $result_bits = 32; } if ($mode === 18) { $result_extension = 2; }
        $abi = new \type_model\Runtime_Callable_Abi($link,$convention,\type_model\Runtime_Abi_Position::integer($result_bits,$result_extension),$physical,$transport);
        if ($production !== 1) { $abi = new \type_model\Runtime_Callable_Abi($link,$convention,null,$physical,$transport); }
        if ($mode === 23) { return new \type_model\Runtime_Callable($provider,$id,$name,$namespace_name,$signature,$abi,0,false,1); }
        if ($mode === 24) { return new \type_model\Runtime_Callable($provider,$id,$name,$namespace_name,$signature,$abi,null,false,1); }
        if ($mode === 25) { return new \type_model\Runtime_Callable($provider,$id,$name,$namespace_name,$signature,$abi,1,false,null); }
        return new \type_model\Runtime_Callable($provider,$id,$name,$namespace_name,$signature,$abi,1,$mode === 26,1);
    }
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($index = 0; $index < $cases->size(); $index++) {
            $fixture = $cases->at($index); $left_mode = $fixture->member('left')->integer(); $right_mode = $fixture->member('right')->integer();
            $equal = $fixture->member('equal')->boolean(); $matches = true;
            if ($fixture->member('kind')->text() === 'reference') {
                $left = Probe::reference($left_mode); $right = Probe::reference($right_mode);
                $matches = (\type_model\Callable_Contracts::reference($left,$right) === $equal) && ($left !== $right);
            } else {
                $a = Probe::make($left_mode); $b = Probe::make($right_mode);
                $matches = (\type_model\Callable_Contracts::same($a,$b) === $equal) && ($a !== $b);
                $previous /** vector<\type_model\Runtime_Callable> */ = [$a]; $current /** vector<\type_model\Runtime_Callable> */ = [$b];
                $retained = \load_runtime\Callable_Retention::retain($current,$previous);
                $wanted = $b; if ($equal) { $wanted = $a; }
                if (($retained[0] !== $wanted) || ($current[0] !== $b) || ($previous[0] !== $a)) { $matches = false; }
                $extra = Probe::make(2);
                // A new coverage member and order must survive even when another member reuses old identity.
                if ($right_mode !== 2) {
                    $current[] = $extra;
                    $expanded = \load_runtime\Callable_Retention::retain($current,$previous);
                    if ((q_count($expanded) !== 2) || ($expanded[0] !== $wanted)) { $matches = false; }
                    if ($left_mode === 2) { if ($expanded[1] !== $a) { $matches = false; } }
                    else { if ($expanded[1] !== $extra) { $matches = false; } }
                }
            }
            echo $matches ? "true\n" : "false\n";
        }
    }
}
