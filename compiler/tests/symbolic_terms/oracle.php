<?php
$retained=!class_exists('check_templates\\Type_Term');
if ($retained) {
    require __DIR__.'/../../reference/pre-rewrite/src/04_analyze/check_templates/data/structures.php';
    require __DIR__.'/../../reference/pre-rewrite/src/04_analyze/check_templates/terms.php';
}
function term_source(int $id) { global $retained; return $retained ? new \check_templates\type_term(\check_templates\term_kind::named,$id) : \check_templates\Type_Term::source($id); }
function term_parameter(int $owner,int $slot) { global $retained; return $retained ? new \check_templates\type_term(\check_templates\term_kind::parameter,"$owner:$slot") : \check_templates\Type_Term::parameter($owner,$slot); }
function term_constant(string $key) { global $retained; return $retained ? new \check_templates\type_term(\check_templates\term_kind::constant,$key) : \check_templates\Type_Term::constant($key); }
function term_application(int $id,array $args) { global $retained; return $retained ? new \check_templates\type_term(\check_templates\term_kind::application,$id,$args) : \check_templates\Type_Term::application($id,$args); }
function term_array($element) { global $retained; return $retained ? new \check_templates\type_term(\check_templates\term_kind::array_type,'array',[$element]) : \check_templates\Type_Term::array_type($element); }
$terms=[term_source(1),term_source(1),term_source(2),term_parameter(7,0),term_parameter(7,1),term_parameter(8,0),term_constant('literal:1'),term_constant('literal:01'),term_constant('project_constant:1')];
foreach (array_slice($terms,0,6) as $term) { $terms[]=term_array($term); $terms[]=term_application(9,[$term,$terms[3]]); }
$terms[]=term_application(9,[$terms[3],$terms[0]]); $terms[]=term_application(10,[$terms[0],$terms[3]]);
$out=['dependent'=>array_map(fn($t)=>$t->dependent,$terms),'same'=>[]];
foreach ($terms as $a) { foreach ($terms as $b) { $out['same'][]=$retained ? \check_templates\Terms::same($a,$b) : \check_templates\Type_Term::same($a,$b); } }
echo json_encode($out),"\n";
