<?php
// Invoke the preserved expression owner without pretending full parser/session migration.
$root=dirname(__DIR__,3);
require $root.'/tools/php_portability/runtime/bootstrap.php';
require $root.'/compiler/tests/tokenizer/reference_support.php';
$ref=$root.'/compiler/reference/pre-rewrite/src/';
foreach(['02_tokenize/structures.php','02_tokenize/tokenize.php','03_parse/data/nodes.php','03_parse/data/tree.php',
 '03_parse/data/structures.php','03_parse/utilities/binary_syntax.php','03_parse/handlers/statements.php',
 '03_parse/handlers/control_statements.php','03_parse/handlers/declarations.php','03_parse/handlers/metaprogramming.php',
 '03_parse/handlers/expressions.php','03_parse/parse_file.php'] as $file) require $ref.$file;
$out=[];
foreach(json_decode(file_get_contents($argv[1]),true) as [$text,$type]) {
 try {
  $tokens=\tokenize\File_Tokenizer::tokenize(new \read_sources\Source_Buffer(7,'expression.phs',0,$text));
  $parser=new \parse\File_Parser($tokens);
  (new ReflectionProperty($parser,'angle_ends'))->setValue($parser,\parse\Binary_Syntax::angle_ends($tokens));
  $root_id=(new ReflectionMethod($parser,'expression'))->invoke($parser,$type?\parse\expression_context::type:\parse\expression_context::value);
  (new ReflectionMethod($parser,'expect'))->invoke($parser,\tokenize\token_kind::end_of_file,'end of expression');
  $tree=(new ReflectionProperty($parser,'tree'))->getValue($parser);
  $queue=[$root_id];$rows=[];
  for($i=0;$i<count($queue);++$i){
   $node=$tree->nodes[$queue[$i]-1];$count=0;
   for($child=$node->first_child_id;$child!==0;$child=$tree->nodes[$child-1]->next_sibling_id){$queue[]=$child;++$count;}
   $rows[]=[$node->kind->value,$node->start,$node->length,$count];
  }
  $out[]=['rows'=>$rows];
 } catch(\diagnostics\Source_Error $e){$out[]=['error'=>[$e->start,$e->length]];}
}
echo json_encode($out,JSON_THROW_ON_ERROR);
