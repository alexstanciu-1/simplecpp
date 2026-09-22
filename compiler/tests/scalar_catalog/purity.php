<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/bootstrap.php';
function check(bool $ok,string $why):void{if(!$ok)throw new LogicException($why);}
$content=file_get_contents($argv[1]);$catalog=\load_runtime\Catalog_Syntax::parse($content);$before=serialize($catalog);
$reader=new \load_runtime\Language_Types($catalog);
check($reader->read($argv[1])===$catalog,'Unchanged bytes retain authoritative identities');
$changed=$reader->read($argv[2]);
check($changed!==$catalog && $changed->entry_return_type!==$catalog->entry_return_type,'Changed defaults get new definitions');
check(serialize($catalog)===$before,'Changed read preserves previous catalog');
try{$reader->read($argv[3]);throw new RuntimeException('Expected invalid catalog');}catch(InvalidArgumentException $e){}
check(serialize($catalog)===$before,'Invalid read preserves previous catalog');
check($reader->read($argv[1])===$catalog,'Repair reuses prior exact snapshot');
$rows=[];for($i=0;$i<$catalog->size();++$i)$rows[]=$catalog->definition_at($i);
$copy=new \type_model\Type_Catalog('p','k','language_values',$rows,$catalog->integer_literal_type,$catalog->entry_return_type,$catalog->boolean_type);
$rows=[];check($copy->size()===7 && $copy->definition_at(1)===$catalog->definition_at(1),'Catalog copies membership and shares immutable definitions');
$policy=$catalog->entry_return_type->lifetime->policy();$policy->copy=0;
check(serialize($catalog)===$before,'Derived policy views cannot mutate definitions');
// Exact qualified identities, including byte-length-sensitive namespace spellings.
$data=json_decode($content,true);$data['types'][1]['namespace']='é:a';
$data['literal_types']['integer']['namespace']='é:a';$data['entry_return_type']['namespace']='é:a';
$qualified=\load_runtime\Catalog_Syntax::parse(json_encode($data,JSON_THROW_ON_ERROR));
check($qualified->find_type('int','é:a')===$qualified->entry_return_type && $qualified->find_type('int','')===null,'Qualified lookup preserves exact UTF-8 namespace');
echo "Scalar catalog purity: 8 assertions passed\n";
