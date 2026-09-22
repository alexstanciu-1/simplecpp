<?php
$root=dirname(__DIR__,3).'/compiler/reference/pre-rewrite/src/';
foreach(['compile/step.php','04_analyze/type_model/data/semantic_calls.php','04_analyze/type_model/data/representations.php',
 '04_analyze/type_model/data/lifecycle.php','04_analyze/type_model/data/definitions.php','04_analyze/type_model/data/catalog.php',
 '01_prepare_inputs/load_runtime/handlers/type_definitions.php','01_prepare_inputs/load_runtime/utilities/catalog_syntax.php'] as $file)require $root.$file;
$out=[];
foreach(array_slice($argv,1) as $path){
    try {$catalog=\load_runtime\Catalog_Syntax::parse(file_get_contents($path));$rows=[];
        foreach($catalog->definitions() as $d){$rows[]=[$d->name,$d->namespace_name,$d->representation->kind->value,$d->signed,$d->integer_family,
            $d->addition?->value,$d->comparison?->value,$d->struct_field,$d->lifetime?->copy->value,$d->lifetime?->construction->value];}
        $out[]=['valid'=>true,'entry'=>$catalog->entry_return_type->name,'integer'=>$catalog->integer_literal_type->name,'boolean'=>$catalog->boolean_type?->name,'rows'=>$rows];
    }catch(Throwable $e){$out[]=['valid'=>false];}
}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";
