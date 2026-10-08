<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function trait_model_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}
function trait_model_owner(string $name): collected_member_owner
{
	foreach (Model::collected_files() as $file) {
		foreach ($file->defined_elements as $index) {
			$entry = $file->entries[$index];
			if (($entry instanceof collected_member_owner) && ($entry->change_status !== change_state::deleted)) {
				if ($entry->qualified_name() === $name) { return $entry; }
			}
		}
	}
	throw new \LogicException('Missing member owner ' . $name);
}
$directory = sys_get_temp_dir() . '/trait-model-' . bin2hex(random_bytes(6));
mkdir($directory);
$trait_path = $directory . '/traits.phs';
$consumer_path = $directory . '/consumers.phs';
$traits = 'namespace features; trait Counter { private int $count = 1; public function next(): int { $this->count += 1; return $this->count; } } trait Label {}';
$consumers = 'namespace app; use features\\Counter as Counted; class A { use Counted, \\features\\Label; int $own; } struct B { use Counted; }';
try {
	file_put_contents($trait_path, $traits); file_put_contents($consumer_path, $consumers);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->sync([]);
	$trait = trait_model_owner('features\\Counter');
	$a = trait_model_owner('app\\A'); $b = trait_model_owner('app\\B');
	trait_model_check($trait instanceof collected_trait && !($trait instanceof collected_record), 'Trait acquired record identity');
	$method = $trait->syntax()->methods[0];
	trait_model_check($method->occurrence()->declaring_type() === $trait, 'Authored trait method lost origin');
	trait_model_check($method->preparation() === null && $trait->syntax()->preparation() === null, 'Trait source was prepared for a consumer');
	trait_model_check($a->syntax()->methods->is_empty() && $b->syntax()->properties->is_empty(), 'Use flattened members into authored inventories');
	trait_model_check($a->syntax()->use_traits->count() === 1 && $a->syntax()->use_traits[0]->traits->count() === 2, 'Simple use list lost grouping');
	trait_model_check($a->syntax()->use_traits[0]->traits[0]->name === 'Counted', 'Use lost lexical spelling');
	trait_model_check($a->syntax()->use_traits[0]->traits[1]->name === '\\features\\Label', 'Qualified use lost spelling');
	$registry = Model::$type_catalog->registry();
	$definition = Source_Types::definition($trait);
	$type = $registry->use($registry->canonical($definition)->type_id());
	trait_model_check($definition->nominal_kind() === nominal_type_kind::trait_type && $definition->capabilities() === [], 'Trait registration granted value capabilities');
	trait_model_check(!Type_Queries::is_nominal_handle($type) && Type_Queries::source_record($type) === null, 'Trait became a runtime handle/layout');
	$compiler->sync([$trait_path, $consumer_path]);
	trait_model_check(trait_model_owner('features\\Counter') === $trait && trait_model_owner('app\\A') === $a, 'No-op replaced declaration identity');
	trait_model_check($trait->syntax()->methods[0] === $method, 'No-op replaced method origin');
	file_put_contents($consumer_path, str_replace('Counted, \\features\\Label', '\\features\\Label, Counted', $consumers));
	$compiler->sync([$consumer_path]);
	trait_model_check(trait_model_owner('app\\A') === $a && $a->change_status !== change_state::unchanged, 'Use change lost identity/change handoff');
	trait_model_check($a->syntax()->use_traits[0]->traits[0]->name === '\\features\\Label', 'Use order not updated');
	file_put_contents($trait_path, str_replace('+= 1', '+= 2', $traits)); $compiler->sync([$trait_path]);
	trait_model_check(trait_model_owner('features\\Counter') === $trait && $trait->syntax()->methods[0] === $method, 'Body edit lost trait/method identity');
	try { $compiler->prepare(); throw new \LogicException('Trait execution was enabled before instantiation'); }
	catch (\RuntimeException $error) { trait_model_check(str_contains($error->getMessage(), 'consumer-owned member instantiation'), 'Wrong trait gate'); }

	foreach (['class A { use Counted { next as other; } }', 'class A { use Counted,; }'] as $invalid) {
		file_put_contents($consumer_path, 'namespace app; ' . $invalid);
		try { $compiler->sync([$consumer_path]); throw new \LogicException('Malformed/adapted use was accepted'); }
		catch (\RuntimeException $expected) {}
		file_put_contents($consumer_path, $consumers); $compiler->sync([$consumer_path]);
		trait_model_check(trait_model_owner('app\\A')->syntax()->use_traits[0]->traits->count() === 2, 'Parse failure did not recover');
	}
	file_put_contents($consumer_path, 'namespace app; class A { int $own; } struct B {}'); $compiler->sync([$consumer_path]);
	trait_model_check(trait_model_owner('app\\A')->syntax()->use_traits->is_empty(), 'Removed use retained syntax');
	file_put_contents($trait_path, ''); $compiler->sync([$trait_path]);
	$compiler->prepare();
	Compiler_Lifecycle::reset(); $compiler->init([$directory]); $compiler->sync([]); $compiler->prepare();
	trait_model_check(trait_model_owner('app\\A')->syntax()->use_traits->is_empty(), 'Fresh parse differs after removal');
}
finally {
	Compiler_Lifecycle::reset();
	foreach ([$trait_path, $consumer_path] as $path) { if (is_file($path)) { unlink($path); } }
	rmdir($directory);
}
echo "Trait origins, simple use syntax, identity/recovery and execution gate passed\n";
