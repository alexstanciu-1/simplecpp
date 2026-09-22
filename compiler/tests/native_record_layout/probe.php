<?php
declare(strict_types=1);
namespace layout_test;
final class Probe {
    private static function check(bool $ok): void { echo $ok ? "true\n" : "false\n"; }
    public static function run(): void {
        $offsets /** vector<int> */ = [0,8,20];
        $layout=new \type_model\Native_Record_Layout('target','layout',24,8,$offsets);
        $offsets[0]=1;
        Probe::check($layout->target_triple==='target'); Probe::check($layout->data_layout==='layout');
        Probe::check($layout->size===24); Probe::check($layout->alignment===8); Probe::check($layout->field_count()===3);
        Probe::check($layout->field_offset(0)===0); Probe::check($layout->field_offset(2)===20);
        for ($case_index=0;$case_index<11;$case_index++) {
            $target='t'; $data='d'; $size=16; $alignment=8; $values /** vector<int> */ = [0,8]; $rejected=false;
            if ($case_index===0) { $target=''; } elseif ($case_index===1) { $data=''; }
            elseif ($case_index===2) { $size=0; } elseif ($case_index===3) { $alignment=0; }
            elseif ($case_index===4) { $alignment=3; } elseif ($case_index===5) { $size=15; }
            elseif ($case_index===6) { $values=[]; } elseif ($case_index===7) { $values[0]=-1; }
            elseif ($case_index===8) { $values[1]=0; } elseif ($case_index===9) { $values[1]=16; }
            try {
                $candidate=new \type_model\Native_Record_Layout($target,$data,$size,$alignment,$values);
                if ($case_index===10) { $candidate->field_offset(2); }
            } catch (\InvalidArgumentException $error) { $rejected=true; }
            Probe::check($rejected);
        }
    }
}
