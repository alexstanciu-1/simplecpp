<?php
// Host-only coverage probe; no production model or semantic traversal is introduced.
namespace scpp\compiler;
require_once dirname(__DIR__, 4) . '/compiler/my-try/boot.php';
$c=new Compiler(); $c->init([$argv[1]]); $c->select_entry('main','main.phs'); $c->exec_cpp();
$owners=[]; foreach(Model::$cpp_output_program->fragments as $owner) { $owners[]=$owner; }
$options=new cpp_lowering_options(); $options->prelower_templates=true; $options->concrete_type_substitution=true;
$render=new \ReflectionMethod(CPP_Generator::class,'render');
$engine_property=new \ReflectionProperty(CPP_Generator::class,'specializations');
$rows=[];
foreach($owners as $owner) {
 $program=new cpp_program(); $generator=new CPP_Generator($program,$options);
 $engine_property->setValue($generator,new CPP_Specializations(false,true));
 $name=get_class($owner);
 $declaration=$owner->declaration();
 if($declaration instanceof collected_name) { $name=$declaration->qualified_name(); }
 if($owner instanceof source_callable_body_work) { $name=$owner->callable_definition()->qualified_name(); }
 $row=['owner'=>$name,'kind'=>$owner->kind()->name];
 try {
  $render->invoke($generator,$owner);
  $fragment=$program->fragments[$owner];
  $text=$fragment->text . $fragment->prototype_text;
  $row['ok']=true; $row['headers']=array_keys($fragment->headers);
  $row['template_lines']=array_values(array_filter(explode("\n",$text),fn($s)=>preg_match('/(?:scpp::\w+|std::function)\s*</',$s)));
 } catch(\Throwable $e) { $row['ok']=false; $row['error']=$e->getMessage(); $row['trace']=$e->getTraceAsString(); }
 $rows[]=$row;
}
echo json_encode($rows,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),"\n";
