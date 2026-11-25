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
// Keep a valid custom provider installed while candidates fail, including failures
// after core bindings have already been privately resolved.
$custom = file_get_contents(__DIR__ . '/runtime_type_bindings.json');
$custom_schema = json_decode($custom, true, 512, JSON_THROW_ON_ERROR);
$input = new runtime_catalog_input();
$input->modules->add('core', Runtime_Catalog_Reader::decode($core_text, 'core.json'));
$input->modules->add('binding_proof', Runtime_Catalog_Reader::decode($custom, 'binding_proof.json'));
Compiler_Lifecycle::configure_runtime($input);
$integer_row = array_search('core.representation.int', array_column($core['cpp_types'], 'id'), true);
$shared_row = array_search('core.representation.shared', array_column($core['cpp_types'], 'id'), true);
$value_row = array_search('core.representation.value', array_column($core['cpp_types'], 'id'), true);
$string_row = array_search('core.representation.string', array_column($core['cpp_types'], 'id'), true);
$invalids = [];
$bad=$core; $bad['cpp_types'][$shared_row]['pointee_modified_type']=true; $invalids[]=[$bad,'Modified pointee requires only a modifier'];
$bad=$core; $bad['cpp_types'][$value_row]['pointee']='Target'; $invalids[]=[$bad,'Modified pointee requires only a modifier'];
$bad=$core; $bad['cpp_types'][$value_row]['arguments']=[]; $invalids[]=[$bad,'Modified pointee requires payload representation'];
$bad=$core; $bad['cpp_types'][$integer_row]['literal_interface']='unknown'; $invalids[]=[$bad,'Unknown C++ literal interface'];
$bad=$core; unset($bad['cpp_types'][$string_row]['byte_interface']); $invalids[]=[$bad,'Byte literal interface'];
$bad=$core; $bad['cpp_types'][$integer_row]['byte_interface']='fixture::bytes'; $invalids[]=[$bad,'Byte literal interface'];
$bad=$core; $bad['cpp_types'][$string_row]['byte_interface']='invalid();'; $invalids[]=[$bad,'Invalid runtime C++ binding'];
$bad=$core; $bad['cpp_types'][$shared_row]['pointee']='Absent'; $invalids[]=[$bad,'pointee must name a declared'];
$bad=$core; $bad['cpp_types'][$shared_row]['arguments'][0]['representation']='stored'; $invalids[]=[$bad,'pointee requires payload'];
$bad=$core; unset($bad['cpp_types'][$shared_row]['arguments']); $invalids[]=[$bad,'pointee requires explicit'];
$bad=$core; $bad['cpp_types'][$shared_row]['arguments']=[]; $invalids[]=[$bad,'pointee must occur'];
$bad=$core; $bad['cpp_types'][$integer_row]['type']='absent'; $invalids[]=[$bad,'Unknown source type'];
$bad=$core; $bad['cpp_types'][$integer_row]['type']='vector'; $invalids[]=[$bad,'must use its declaration id'];
$bad=$core; $bad['cpp_types'][$integer_row]['modifier']='value'; $invalids[]=[$bad,'both a type and modifier'];
$bad=$core; $bad['cpp_types'][$integer_row]['arguments']=[['native'=>'int();']]; $invalids[]=[$bad,'Invalid runtime C++ binding'];
$bad=$core; $bad['cpp_types'][$integer_row]['arguments']=[['native'=>'std::int64_t','parameter'=>'Value']]; $invalids[]=[$bad,'exactly one target'];
$bad=$core; $bad['cpp_types'][$integer_row]['arguments']=[['parameter'=>'Value','representation'=>'stored']]; $invalids[]=[$bad,'Unknown C++ type parameter'];
$bad=$core; $bad['cpp_types'][$integer_row]['arguments']=[['modified_type'=>true,'representation'=>'payload']]; $invalids[]=[$bad,'requires a modifier binding'];
$bad=$core; $bad['cpp_types'][$shared_row]['arguments'][0]['parameter']='Missing'; $invalids[]=[$bad,'pointee must occur'];
$bad=$core; unset($bad['cpp_types'][$shared_row]['arguments'][0]['representation']); $invalids[]=[$bad,'requires representation'];
$bad=$core; $bad['cpp_types'][$value_row]['arguments'][0]['representation']='stored'; $invalids[]=[$bad,'requires payload representation'];
$bad=$core; unset($bad['cpp_types'][$value_row]['arguments']); $invalids[]=[$bad,'requires explicit arguments'];
$bad=$core; $duplicate=$bad['cpp_types'][$integer_row]; $duplicate['id']='duplicate'; $bad['cpp_types'][]=$duplicate; $invalids[]=[$bad,'Duplicate C++ type definition binding'];
$bad=$core; $bad['cpp_types'][0]['type']='int'; $invalids[]=[$bad,'type binding count mismatch'];
$bad=$core; unset($bad['cpp_types'][$integer_row]['header']); $invalids[]=[$bad,'Missing C++ binding field'];
$bad=$core; $bad['cpp_types'][$integer_row]['unknown_contract']=true; $invalids[]=[$bad,'Unknown C++ type binding field'];
$bad=$core; $bad['cpp_types'][$integer_row]['header']='bad\"header'; $invalids[]=[$bad,'Invalid runtime C++ binding'];
$bad=$core; $bad['cpp_types'][$integer_row]['arguments']=[['native'=>'int','representation'=>'stored']]; $invalids[]=[$bad,'Native C++ argument cannot select semantic representation'];
$bad=$core; $bad['cpp_types'][$shared_row]['arguments'][0]['header']='provider.hpp'; $invalids[]=[$bad,'Semantic C++ argument requires representation'];
$bad=$core; $bad['cpp_types'][$shared_row]['arguments'][0]['representation']='unknown'; $invalids[]=[$bad,'Unknown C++ argument representation'];
$bad=$core; $bad['cpp_types'][$value_row]['arguments'][0]['modified_type']=false; $invalids[]=[$bad,'modified_type argument must be true'];
$bad=$core; $bad['cpp_types'][$integer_row]['arguments']=[['native'=>'int','header'=>'bad\"header']]; $invalids[]=[$bad,'Invalid runtime C++ binding'];
$bad=$core; $duplicate=$bad['cpp_types'][$value_row]; $duplicate['id']='duplicate.modifier'; $bad['cpp_types'][]=$duplicate; $invalids[]=[$bad,'Duplicate C++ type modifier binding'];

$bad=$core; $bad['cpp_types'][$integer_row]['concrete_boundary']='true'; $invalids[]=[$bad,'expected boolean'];
$bad=$core; $bad['cpp_types'][$value_row]['pointee_modified_type']='true'; $invalids[]=[$bad,'expected boolean'];

function binding_rejection(array $core, array $provider, string $message, runtime_catalog_input $installed): void
{
	$diagnostic = '';
	try {
		$candidate = new runtime_catalog_input();
		$candidate->modules->add('core', Runtime_Catalog_Reader::decode(json_encode($core), 'candidate-core.json'));
		$candidate->modules->add('binding_proof', Runtime_Catalog_Reader::decode(json_encode($provider), 'candidate-provider.json'));
		Compiler_Lifecycle::configure_runtime($candidate);
	} catch (\Throwable $error) { $diagnostic = $error->getMessage(); }
	binding_check(str_contains($diagnostic, $message), 'Expected rejection: '.$message.'; received: '.$diagnostic);
	binding_check(Model::require_runtime_metadata() === $installed, 'Rejected candidate replaced installed metadata');
	// A failed candidate must not poison subsequent validation of the valid input.
	Compiler_Lifecycle::configure_runtime($installed);
	binding_check(Model::require_runtime_metadata() === $installed, 'Valid configuration recovery lost installed input');
}
foreach ($invalids as [$bad,$message]) { binding_rejection($bad, $custom_schema, $message, $input); }
$bad=$core; $bad['cpp_types'][$shared_row]['arguments'][0]['layout']='unknown'; binding_rejection($bad, $custom_schema, 'Unknown C++ argument layout requirement', $input);
$bad=$core; $bad['cpp_types'][$integer_row]['arguments']=[['native'=>'int','layout'=>'declaration']]; binding_rejection($bad, $custom_schema, 'layout requires a formal parameter', $input);
$bad=$core; $bad['cpp_types'][$value_row]['arguments'][0]['layout']='declaration'; binding_rejection($bad, $custom_schema, 'layout requires a formal parameter', $input);
$provider_invalids = [];
$bad=$custom_schema; array_pop($bad['cpp_types']); $provider_invalids[]=[$bad,'type binding count mismatch'];
$bad=$custom_schema; $bad['cpp_types'][0]['id']='proof.absent'; $provider_invalids[]=[$bad,'Missing runtime type binding'];
$bad=$custom_schema; $bad['cpp_types'][0]['arguments'][0]['parameter']='Absent'; $provider_invalids[]=[$bad,'Unknown C++ type parameter'];
$bad=$custom_schema; $bad['cpp_types'][1]['arguments'][0]['native']='invalid()'; $provider_invalids[]=[$bad,'Invalid runtime C++ binding'];
foreach ($provider_invalids as [$bad,$message]) { binding_rejection($core, $bad, $message, $input); }
$candidate = $input;
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
$engine=new CPP_Specializations();
$closed_entry=$engine->entry($engine->request_composed($closed));
$duo_entry=$engine->entry($engine->request_composed($duo));
$artifacts=new Storage /** Storage<cpp_artifact> */();
$engine->append_artifacts($artifacts);
$artifact_snapshot=[]; foreach ($artifacts as $artifact) { $artifact_snapshot[$artifact->file_name]=$artifact->text; }
// The installed recipes belong to the new registry; immutable input survives its reset.
$definition_before=Model::$type_catalog->definition('closed');
Compiler_Lifecycle::reset();
binding_check(Model::require_runtime_metadata()===$candidate && Model::$type_catalog->definition('closed')!==$definition_before, 'Recipe configuration did not survive identity reset');
// Recovery also preserves demanded output across fresh registry identities.
$replayed_registry = Model::$type_catalog->registry();
$replayed_integer = $replayed_registry->use(Model::$type_catalog->canonical('int')->type_id());
$replayed_string = $replayed_registry->use(Model::$type_catalog->canonical('string')->type_id());
$replayed_definition = object_cast(Model::$type_catalog->definition('duo'), abstract_template_type_definition::class);
$replayed_duo = $replayed_registry->use($replayed_registry->intern_application($replayed_definition, [$replayed_integer, $replayed_string])->type_id());
$replayed_closed = $replayed_registry->use(Model::$type_catalog->canonical('closed')->type_id());
$replayed_engine = new CPP_Specializations();
$replayed_engine->request_composed($replayed_closed);
$replayed_engine->request_composed($replayed_duo);
$replayed_artifacts = new Storage /** Storage<cpp_artifact> */();
$replayed_engine->append_artifacts($replayed_artifacts);
$replayed_snapshot = [];
foreach ($replayed_artifacts as $artifact) { $replayed_snapshot[$artifact->file_name] = $artifact->text; }
binding_check($replayed_snapshot === $artifact_snapshot, 'Reset/recovery changed demanded concrete artifacts');
echo 'Runtime type bindings: '.(count($invalids) + count($provider_invalids))." invalid schemas rejected with recovery; reordered and closed native templates, demand and deterministic reset passed\n";
if (isset($argv[1])) {
	$folder=$argv[1];
	foreach ($artifact_snapshot as $file=>$text) { $path=$folder.'/'.$file; if(!is_dir(dirname($path))) { mkdir(dirname($path),0777,true); } file_put_contents($path,$text); }
	file_put_contents($folder.'/proof.cpp', '#include "'.$duo_entry->header.'"' . "\n" . '#include "'.$closed_entry->header.'"' . "\n"
		.'#include <utility>' . "\nint main(){ scpp::internal::".$duo_entry->name.' pair; auto copy=pair; pair=std::move(copy); scpp::internal::'.$closed_entry->name.' number; auto other=number; number=std::move(other); }' . "\n");
}
