<?php
// Run the retained and migrated classes in separate PHP processes: PHP class names are case-insensitive.
$retained=!class_exists('type_model\\Native_Record_Layout');
if ($retained) { require __DIR__.'/../../reference/pre-rewrite/src/04_analyze/type_model/data/records.php'; }
$results=[];
foreach ([0,1,2,3,4,7,8,16,32] as $alignment) {
    foreach ([0,1,8,15,16,24,32] as $size) {
        foreach ([[],[-1],[0],[0,0],[8,0],[0,8],[0,16]] as $offsets) {
            try {
                $row=new \type_model\Native_Record_Layout('target','data',$size,$alignment,$offsets);
                $actual=[];
                foreach ($offsets as $i=>$offset) { $actual[]=$retained ? $row->offsets[$i] : $row->field_offset($i); }
                $results[]=$actual;
            } catch (InvalidArgumentException $e) { $results[]=false; }
        }
    }
}
echo json_encode($results),"\n";
