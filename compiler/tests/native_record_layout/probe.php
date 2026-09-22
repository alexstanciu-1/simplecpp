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
        for ($case=0;$case<11;$case++) {
            $target='t'; $data='d'; $size=16; $alignment=8; $values /** vector<int> */ = [0,8]; $rejected=false;
            if ($case===0) { $target=''; } elseif ($case===1) { $data=''; }
            elseif ($case===2) { $size=0; } elseif ($case===3) { $alignment=0; }
            elseif ($case===4) { $alignment=3; } elseif ($case===5) { $size=15; }
            elseif ($case===6) { $values=[]; } elseif ($case===7) { $values[0]=-1; }
            elseif ($case===8) { $values[1]=0; } elseif ($case===9) { $values[1]=16; }
            try {
                $candidate=new \type_model\Native_Record_Layout($target,$data,$size,$alignment,$values);
                if ($case===10) { $candidate->field_offset(2); }
            } catch (\InvalidArgumentException $error) { $rejected=true; }
            Probe::check($rejected);
        }
    }
}
