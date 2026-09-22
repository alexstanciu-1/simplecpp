<?php
// Independent retained file parser oracle; no semantic/session stages are loaded.
$root=dirname(__DIR__,3);
require $root.'/tools/php_portability/runtime/bootstrap.php';
require $root.'/compiler/tests/tokenizer/reference_support.php';
$ref=$root.'/compiler/reference/pre-rewrite/src/';
foreach(['02_tokenize/structures.php','02_tokenize/tokenize.php','03_parse/data/nodes.php','03_parse/data/tree.php',
 '03_parse/data/structures.php','03_parse/data/result.php','03_parse/utilities/binary_syntax.php','03_parse/handlers/statements.php',
 '03_parse/handlers/control_statements.php','03_parse/handlers/declarations.php','03_parse/handlers/metaprogramming.php',
 '03_parse/handlers/expressions.php','03_parse/parse_file.php'] as $file) require $ref.$file;
foreach(['data/role_views','utilities/metaprogramming_syntax','utilities/struct_member_cursor','utilities/syntax_access','utilities/syntax_comparer'] as $name) require $ref.'03_parse/'.$name.'.php';
$roles=json_decode(file_get_contents(__DIR__.'/roles.json'),true);
function parse_text(string $text): \parse\File_Frontend {
 return (new \parse\File_Parser(\tokenize\File_Tokenizer::tokenize(new \read_sources\Source_Buffer(7,'proof',0,$text))))->parse();
}
function node(\parse\Syntax_Tree $tree,int $id): ?array {
 if($id===0)return null;$n=$tree->nodes[$id-1];return [$n->kind->value,$n->start,$n->length];
}
$out=[];
foreach(json_decode(file_get_contents($argv[1]),true) as $text){
 $file=parse_text($text);$tree=$file->syntax;$before=serialize($file);$queue=[$tree->root_node_id];$rows=[];
 for($i=0;$i<count($queue);++$i){
  $id=$queue[$i];$n=$tree->nodes[$id-1];$kind=$n->kind->name;$row=[];
  if(isset($roles[strtoupper($kind)])){
   [$method,$fields]=$roles[strtoupper($kind)];$view=\parse\Syntax_Access::$method($tree,$id);
   foreach($fields as $field)$row[]=$field==='reference'?($view->reference?->value??0):node($tree,$view->$field);
  }
  if($kind==='call_expression')$row=[node($tree,\parse\Syntax_Access::call_target($tree,$id)),node($tree,\parse\Syntax_Access::first_argument($tree,$id))];
  if($kind==='parameter_list')$row=[node($tree,\parse\Syntax_Access::first_parameter($tree,$id))];
  if(in_array($kind,['return_statement','expression_statement']))$row=[node($tree,\parse\Syntax_Access::statement_expression($tree,$id))];
  if($kind==='method_declaration')$row=[\parse\Syntax_Access::const_receiver($tree,$id),node($tree,\parse\Syntax_Access::underlying_declaration($tree,$id))];
  if(in_array($kind,['constexpr_declaration','consteval_declaration']))$row=[node($tree,\parse\Syntax_Access::evaluated_function($tree,$id))];
  if(in_array($kind,['field_expression','index_expression']))$row=[node($tree,\parse\Syntax_Access::place_root($tree,$id))];
  $rows[]=$row;
  for($child=$n->first_child_id;$child!==0;$child=$tree->nodes[$child-1]->next_sibling_id)$queue[]=$child;
 }
 if(serialize($file)!==$before)throw new LogicException('Oracle mutation');$out[]=$rows;
}
foreach(json_decode(file_get_contents($argv[2]),true) as [$left,$right,$definition]) {
 $a=parse_text($left);$b=parse_text($right);
 $out[]=\parse\Syntax_Comparer::equal($a,$definition?$a->defined_entities[0]:$a->syntax->root_node_id,$b,$definition?$b->defined_entities[0]:$b->syntax->root_node_id);
}
echo json_encode($out,JSON_THROW_ON_ERROR);
