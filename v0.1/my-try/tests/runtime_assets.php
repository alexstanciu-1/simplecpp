<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

$metadata = Runtime_Catalog_Loader::load(dirname(__DIR__, 3) . '/runtime/metadata/modules', ['filesystem', 'json', 'process']);
Compiler_Lifecycle::configure_runtime($metadata);
foreach ([
	'$value = new process_handle();',
	'$value = new json_document();',
	'$value = new file_lock_handle();',
	'$handle process_handle; $wrong int = process_poll($handle);',
	'$diagnostic ?json_parse_error = null; $value = json_document_parse("null", $diagnostic, "wrong");',
	'$handle file_lock_handle; fs_lock_try($handle, 17);',
	'$output process_output; $output->exit_code = "wrong";',
	'$node json_node; $node->unknown = 1;',
] as $invalid) {
	Compiler_Lifecycle::reset();
	$module = new module('/assets', '/assets', '/assets');
	$file = new file(); $file->path = 'main.phs'; $file->content = $invalid;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	$failed = false;
	try { (new Compiler(new cpp_lowering_options(false)))->exec_cpp(); } catch (\RuntimeException $error) { $failed = true; }
	if (!$failed || !Model::$cpp_files->is_empty()) { throw new \LogicException('Invalid asset call admitted: ' . $invalid); }
}
echo "Runtime asset rejection checks passed\n";
