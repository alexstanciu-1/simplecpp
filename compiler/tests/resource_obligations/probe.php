<?php
declare(strict_types=1);
namespace resource_test;
final class Probe {
    private static function check(bool $ok): void { echo $ok ? "true\n" : "false\n"; }
    private static function definition(\type_model\Resource_Obligations $ownership, int $shape, int $copy, int $assignment): \type_model\Named_Definition {
        $p=new \type_model\Lifetime_Policy(); $p->copy=$copy; $p->assignment=$assignment;
        $ops /** vector<\type_model\Lifecycle_Operation> */ = [];
        if ($copy===2) { $ops[]=\type_model\Lifecycle_Operation::runtime('p','copy','copy','ccc',\type_model\LIFECYCLE_COPY); }
        if ($assignment===2) { $ops[]=\type_model\Lifecycle_Operation::runtime('p','assign','assign','ccc',\type_model\LIFECYCLE_ASSIGN); }
        $rep=\type_model\Representation::opaque(8,8);
        if ($shape===1) { $rep=\type_model\Representation::structure(0,2); }
        return new \type_model\Named_Definition('owner','',$rep,new \type_model\Lifetime_Contract($p,$ops),null,'',false,false,true,$ownership);
    }
    public static function run(): void {
        $names /** vector<string> */ = ['acquire','release','transfer','inspect','mutate','observe'];
        foreach ($names as $index=>$name) {
            $kind=$index+1; Probe::check(\type_model\Allocation_Effects::parse($name)===$kind);
            Probe::check(\type_model\Allocation_Effects::name($kind)===$name);
        }
        $transfer=new \type_model\Allocation_Effect(\type_model\ALLOCATION_TRANSFER,0,1);
        Probe::check($transfer->owner===0); Probe::check($transfer->destination===1);
        $observe=new \type_model\Allocation_Effect(\type_model\ALLOCATION_OBSERVE,0);
        Probe::check($observe->destination===null);
        $paths /** vector<vector<int>> */ = [[0,1],[1,0]];
        $fields=new \type_model\Resource_Obligations(\type_model\RESOURCE_NONE,$paths);
        $paths[0][0]=9; $returned=$fields->path_at(0); $returned[0]=8;
        Probe::check($fields->path_count()===2); Probe::check($fields->path_at(0)[0]===0); Probe::check($fields->has_owners());
        $empty /** vector<vector<int>> */ = []; $direct=new \type_model\Resource_Obligations(\type_model\RESOURCE_ALLOCATION,$empty);
        $none=new \type_model\Resource_Obligations(\type_model\RESOURCE_NONE,$empty);
        Probe::check(!$none->has_owners()); Probe::check($direct->has_owners());
        $owned=Probe::definition($direct,0,0,0); Probe::check($owned->ownership===$direct);
        $record=Probe::definition($fields,1,2,2); Probe::check($record->ownership===$fields);
        for ($case_index=0;$case_index<17;$case_index++) {
            $rejected=false;
            try {
                if ($case_index===0) { $invalid_effect=new \type_model\Allocation_Effect(0,0); }
                elseif ($case_index===1) { $invalid_effect=new \type_model\Allocation_Effect(7,0); }
                elseif ($case_index===2) { $invalid_effect=new \type_model\Allocation_Effect(1,-1); }
                elseif ($case_index===3) { $invalid_effect=new \type_model\Allocation_Effect(3,0); }
                elseif ($case_index===4) { $invalid_effect=new \type_model\Allocation_Effect(1,0,1); }
                elseif ($case_index===5) { $invalid_effect=new \type_model\Allocation_Effect(3,0,0); }
                elseif ($case_index===6) { $invalid_effect=new \type_model\Allocation_Effect(3,0,-1); }
                elseif ($case_index===7) { $bad /** vector<vector<int>> */ = [[]]; $invalid_paths=new \type_model\Resource_Obligations(0,$bad); }
                elseif ($case_index===8) { $negative /** vector<vector<int>> */ = [[-1]]; $invalid_paths=new \type_model\Resource_Obligations(0,$negative); }
                elseif ($case_index===9) { $duplicates /** vector<vector<int>> */ = [[0,1],[0,1]]; $invalid_paths=new \type_model\Resource_Obligations(0,$duplicates); }
                elseif ($case_index===10) { $invalid_kind=new \type_model\Resource_Obligations(2,$empty); }
                elseif ($case_index===11) { Probe::definition($direct,1,0,0); }
                elseif ($case_index===12) { Probe::definition($direct,0,1,0); }
                elseif ($case_index===13) { Probe::definition($direct,0,0,1); }
                elseif ($case_index===14) { Probe::definition($fields,0,0,0); }
                elseif ($case_index===15) { Probe::definition($fields,1,1,0); }
                else { Probe::definition($fields,1,0,1); }
            } catch (\InvalidArgumentException $error) { $rejected=true; }
            Probe::check($rejected);
        }
    }
}
