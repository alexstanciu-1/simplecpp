<?php
declare(strict_types=1);
require __DIR__ . '/../../compiler/bootstrap.php';
require __DIR__ . '/oracles/project_manifest_original.php';
function manifest_outcome(object $manifest): array {
    try { return ['json', $manifest->to_json()]; }
    catch (Exception $e) { return ['error',get_class($e),$e->getMessage(),$e->getCode(),get_class($e->getPrevious()),$e->getPrevious()->getMessage(),$e->getPrevious()->getCode()]; }
}
$values=['', '/a', 'é😀', "\"\\/\n\t\0", "\xff", "\xed\xa0\x80"];
$cases=0;
foreach ($values as $path) { foreach ([null,...$values] as $content) { foreach (['', '/root'] as $directory) {
 $original=new read_manifest\Baseline_Project_Manifest();$portable=new read_manifest\Project_Manifest();
 foreach ([$original,$portable] as $manifest) {
  $manifest->path=$path;$manifest->content=$content;$manifest->directory=$directory;
  $manifest->source_folder_paths=['src', 'é'];$manifest->source_file_paths=($cases%2===0)?[]:['single.phs'];$manifest->entry_path='src/a.phs';
 }
 $before=serialize($portable);
 if(manifest_outcome($original)!==manifest_outcome($portable) || serialize($portable)!==$before) {throw new RuntimeException('Manifest mismatch: '.$cases);}
 ++$cases;
} } }
foreach(['directory','entry_path','source_folder_paths','source_file_paths'] as $field) {
 $a=new read_manifest\Baseline_Project_Manifest();$b=new read_manifest\Project_Manifest();
 $value=str_starts_with($field,'source_')?["\xff"]:"\xff";$a->$field=$value;$b->$field=$value;
 if(manifest_outcome($a)!==manifest_outcome($b)) {throw new RuntimeException('Manifest error mismatch: '.$field);}
 ++$cases;
}
$controls='';for($i=0;$i<128;++$i){$controls.=chr($i);}
foreach([$controls,'é😀',"\u{2028}\u{2029}"] as $text) {
 if(scpp\json_quote($text)!==json_encode($text,JSON_THROW_ON_ERROR)) {throw new RuntimeException('Scalar quote mismatch');}
}
echo $cases," manifest snapshots match original JSON and error chains without mutation\n";
