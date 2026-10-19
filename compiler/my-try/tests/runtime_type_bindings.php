<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function binding_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

// Host-only fixture mutation; production reads directly into typed metadata records.
$core_text = file_get_contents(dirname(__DIR__, 3) . '/runtime/metadata/modules/core.json');
$core = json_decode($core_text, true, 512, JSON_THROW_ON_ERROR);
$input = Model::$runtime_metadata;
$integer_row = array_search('core.representation.int', array_column($core['cpp_types'], 'id'), true);
$shared_row = array_search('core.representation.shared', array_column($core['cpp_types'], 'id'), true);
$value_row = array_search('core.representation.value', array_column($core['cpp_types'], 'id'), true);
$invalids = [];
$bad=$core; $bad['cpp_types'][$integer_row]['type']='absent'; $invalids[]=[$bad,'Unknown source type'];
$bad=$core; $bad['cpp_types'][$integer_row]['type']='vector'; $invalids[]=[$bad,'must use its declaration id'];
$bad=$core; $bad['cpp_types'][$integer_row]['modifier']='value'; $invalids[]=[$bad,'both a type and modifier'];
$bad=$core; $bad['cpp_types'][$integer_row]['arguments']=[['native'=>'int();']]; $invalids[]=[$bad,'Invalid runtime C++ binding'];
$bad=$core; $bad['cpp_types'][$integer_row]['arguments']=[['native'=>'std::int64_t','parameter'=>'Value']]; $invalids[]=[$bad,'exactly one target'];
$bad=$core; $bad['cpp_types'][$integer_row]['arguments']=[['parameter'=>'Value','representation'=>'stored']]; $invalids[]=[$bad,'Unknown C++ type parameter'];
$bad=$core; $bad['cpp_types'][$integer_row]['arguments']=[['modified_type'=>true,'representation'=>'payload']]; $invalids[]=[$bad,'requires a modifier binding'];
$bad=$core; $bad['cpp_types'][$shared_row]['arguments'][0]['parameter']='Missing'; $invalids[]=[$bad,'Unknown C++ type parameter'];
$bad=$core; unset($bad['cpp_types'][$shared_row]['arguments'][0]['representation']); $invalids[]=[$bad,'requires representation'];
$bad=$core; $bad['cpp_types'][$value_row]['arguments'][0]['representation']='stored'; $invalids[]=[$bad,'requires payload representation'];
$bad=$core; unset($bad['cpp_types'][$value_row]['arguments']); $invalids[]=[$bad,'requires explicit arguments'];
$bad=$core; $duplicate=$bad['cpp_types'][$integer_row]; $duplicate['id']='duplicate'; $bad['cpp_types'][]=$duplicate; $invalids[]=[$bad,'Duplicate C++ type definition binding'];
$bad=$core; $bad['cpp_types'][0]['type']='int'; $invalids[]=[$bad,'type binding count mismatch'];
foreach ($invalids as [$bad,$message]) {
	$failed=false;
	try {
		$candidate = new runtime_catalog_input();
		$candidate->modules->add('core', Runtime_Catalog_Reader::decode(json_encode($bad), 'bad.json'));
		Compiler_Lifecycle::configure_runtime($candidate);
	} catch (\Throwable $error) { $failed=str_contains($error->getMessage(), $message); }
	binding_check($failed && Model::$runtime_metadata === $input, 'Invalid binding accepted or wrong diagnostic: '.$message);
}

$custom = '{"version":1,"module":"binding_proof","dependencies":["core"],"functions":[],"cpp":[],
"types":[
 {"id":"proof.duo","name":"duo","kind":"struct","capabilities":["value_storable","copyable_value"],
  "parameters":[{"name":"First","requires":["value_storable"]},{"name":"Second","requires":["value_storable"]}]},
 {"id":"proof.closed","name":"closed","kind":"struct","capabilities":["value_storable","copyable_value"]}],
"cpp_types":[
 {"id":"proof.duo","symbol":"std::pair","header":"utility","arguments":[
  {"parameter":"Second","representation":"stored"},{"parameter":"First","representation":"stored"}]},
 {"id":"proof.closed","symbol":"scpp::int_t","header":"scpp/int_t.hpp","arguments":[{"native":"std::int32_t","header":"cstdint"}]}]}';
$candidate = new runtime_catalog_input();
$candidate->modules->add('core', Runtime_Catalog_Reader::decode($core_text, 'core.json'));
$candidate->modules->add('binding_proof', Runtime_Catalog_Reader::decode($custom, 'binding_proof.json'));
Compiler_Lifecycle::configure_runtime($candidate);
Compiler_Lifecycle::reset();
$registry=Model::$type_catalog->registry();
$integer=$registry->use(Model::$type_catalog->canonical('int')->type_id());
$string=$registry->use(Model::$type_catalog->canonical('string')->type_id());
$duo_definition=object_cast(Model::$type_catalog->definition('duo'), abstract_template_type_definition::class);
$duo=$registry->use($registry->intern_application($duo_definition,[$integer,$string])->type_id());
$closed=$registry->use(Model::$type_catalog->canonical('closed')->type_id());
$mapping=CPP_Types::representation($duo);
binding_check($mapping->spelling==='std::pair<scpp::string_t, scpp::int_t<>>','Metadata argument order was ignored');
$closed_mapping=CPP_Types::representation($closed);
binding_check($closed_mapping->spelling==='scpp::int_t<std::int32_t>' && isset($closed_mapping->headers['cstdint']), 'Closed native template or argument header lost');
$engine=new CPP_Specializations(true,true);
$closed_entry=$engine->entry($engine->request_composed($closed));
$duo_entry=$engine->entry($engine->request_composed($duo));
$artifacts=new Storage /** Storage<cpp_artifact> */();
$engine->append_artifacts($artifacts);
$artifact_snapshot=[]; foreach ($artifacts as $artifact) { $artifact_snapshot[$artifact->file_name]=$artifact->text; }
// The installed recipes belong to the new registry; immutable input survives its reset.
$definition_before=Model::$type_catalog->definition('closed');
Compiler_Lifecycle::reset();
binding_check(Model::$runtime_metadata===$candidate && Model::$type_catalog->definition('closed')!==$definition_before, 'Recipe configuration did not survive identity reset');
echo 'Runtime type bindings: '.count($invalids)." invalid schemas rejected; reordered and closed native templates, demand and reset passed\n";
if (isset($argv[1])) {
	$folder=$argv[1];
	foreach ($artifact_snapshot as $file=>$text) { $path=$folder.'/'.$file; if(!is_dir(dirname($path))) { mkdir(dirname($path),0777,true); } file_put_contents($path,$text); }
	file_put_contents($folder.'/proof.cpp', '#include "'.$duo_entry->header.'"' . "\n" . '#include "'.$closed_entry->header.'"' . "\n"
		.'#include <utility>' . "\nint main(){ scpp::internal::".$duo_entry->name.' pair; auto copy=pair; pair=std::move(copy); scpp::internal::'.$closed_entry->name.' number; auto other=number; number=std::move(other); }' . "\n");
}
