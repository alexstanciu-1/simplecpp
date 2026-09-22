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
$out=[];
foreach(json_decode(file_get_contents($argv[1]),true) as $text) {
 try {
  $tokens=\tokenize\File_Tokenizer::tokenize(new \read_sources\Source_Buffer(7,'expression.phs',0,$text));
  $parser=new \parse\File_Parser($tokens);
  $before=serialize($tokens);
  $result=$parser->parse();
  if(serialize($tokens)!==$before || $result->tokens!==$tokens) throw new LogicException('Reference token mutation');
  $tree=$result->syntax; $root_id=$tree->root_node_id;
  $queue=[$root_id];$rows=[];
  for($i=0;$i<count($queue);++$i){
   $node=$tree->nodes[$queue[$i]-1];$count=0;
   for($child=$node->first_child_id;$child!==0;$child=$tree->nodes[$child-1]->next_sibling_id){$queue[]=$child;++$count;}
   $rows[]=[$node->kind->value,$node->start,$node->length,$count];
  }
  $out[]=['rows'=>$rows,'definitions'=>count($result->defined_entities)];
 } catch(\diagnostics\Source_Error $e){$out[]=['error'=>[$e->start,$e->length]];}
}
echo json_encode($out,JSON_THROW_ON_ERROR);
