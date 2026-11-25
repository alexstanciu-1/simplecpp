<?php
namespace scpp\compiler;

// Host adapter only: project JSON/policies stay with the existing project service owner.
require_once dirname(__DIR__, 3) . '/bin/project_services.php';
require_once __DIR__ . '/runtime_build.php';

final class Project_Compilation
{
	private string $root;
	private array $contexts = [];
	private array $module_roots = [];
	private array $module_rows = [];
	private array $config;
	public array $report = [];

	public function __construct(string $path)
	{
		$config_path = realpath(is_dir($path) ? $path . '/prism.json' : $path);
		if ($config_path === false || !is_file($config_path)) { throw new \RuntimeException('Project config not found: ' . $path); }
		$this->root = dirname($config_path);
		$this->config = \load_project_config($config_path);
		foreach (\resolve_project_dependency_graph($this->root, $config_path, $this->config) as $context) {
			$this->contexts[$context['project_root']] = $context;
		}
	}

	/** The project inventory selects membership; the compiler owns parsing and all name resolution. */
	public function initialize(Compiler $compiler, string $entry_override = ''): void
	{
		if (\resolve_php_runtime_profile($this->config['runtime']) !== 'strict') {
			throw new \RuntimeException('The v0.2 project compiler requires runtime.languages.php.profile=strict');
		}
		$modules = array_values(array_unique(array_merge(['core', 'strings'], $this->config['runtime']['modules'])));
		$metadata = Runtime_Catalog_Loader::load(dirname(__DIR__, 3) . '/runtime/metadata/modules', $modules);
		Host_Runtime_Build::resolve($metadata);
		Compiler_Lifecycle::configure_runtime($metadata);
		$inputs = new Storage();
		foreach ($this->contexts as $root => $context) {
			if ($context['config']['libraries'] !== []) { throw new \RuntimeException('Native library project integration remains outside the v0.2 project slice'); }
			$name = $root === $this->root ? 'main' : 'dependency_' . substr(hash('sha256', \relative_path($this->root, $root)), 0, 16);
			$this->module_roots[$name] = $root;
			$input = new module_input($root, $name);
			$input->selected_sources = true;
			foreach (\collect_project_php_files($root) as $path) {
				// A nested dependency owns its subtree, even when its parent inventories it too.
				$nested_owner = false;
				foreach ($this->contexts as $other => $_context) {
					if ($other !== $root && str_starts_with($other, $root . '/') && str_starts_with($path, $other . '/')) { $nested_owner = true; break; }
				}
				if ($nested_owner) { continue; }
				if (pathinfo($path, PATHINFO_EXTENSION) === 'jss') { throw new \RuntimeException('JSS project sources require their separate frontend'); }
				$input->source_paths[] = \relative_path($root, $path);
			}
			$inputs->append($input);
			$label = \project_context_report_label($this->root, $root);
			foreach (\normalize_project_module_config_rows($context['config'], $label) as $module) {
				$source_rows = [];
				foreach ($input->source_paths as $source) {
					if (\project_module_matches_source($module, $source)) { $source_rows[] = ['source' => $source]; }
				}
				$this->module_rows[] = array_merge($module, ['project_root' => $label, 'sources' => $source_rows]);
			}
		}
		$entry = \resolve_build_entrypoint($this->root, $this->config, $entry_override === '' ? null : $entry_override);
		$compiler->init_modules($inputs);
		$compiler->select_entry('main', \relative_path($this->root, $entry));
	}

	/** Feed existing policies with completed v0.2 identities, never v0.1 STAN or a second resolver. */
	public function validate(): void
	{
		$sources = new Storage();
		$locations = new \SplObjectStorage();
		foreach (Model::sources() as $record) {
			if ($record->changes === change_state::deleted || $record->parsed === null) { continue; }
			$root = $this->module_roots[Source_Registry::logical_module($record->owning_module())];
			$label = \project_context_report_label($this->root, $root);
			$locations[$record->file] = ['project_root' => $label, 'source' => $record->path];
			$sources->append($record->parsed->collection);
		}
		$files = [];
		$dependencies = [];
		foreach (Project_Evidence::collect($sources) as $evidence) {
			$location = $locations[$evidence->source->source_file()];
			$key = \project_module_source_key($location['project_root'], $location['source']);
			$files[$key] ??= ['root_functions' => [], 'root_classes' => [], 'root_constants' => []];
			$dependencies[$key] ??= $location + ['direct_source_dependencies' => [], 'dependency_categories' => []];
			foreach ($evidence->declarations as $declaration) {
				$bucket = $declaration instanceof collected_function ? 'root_functions' : ($declaration instanceof collected_constant ? 'root_constants' : 'root_classes');
				$files[$key][$bucket][] = ['name' => $declaration->qualified_name()];
			}
			foreach ($evidence->references as $target) {
				$target_location = $locations[$target->source_file()];
				$target_path = $target_location['project_root'] === '.' ? $target_location['source'] : $target_location['project_root'] . '/' . $target_location['source'];
				$dependencies[$key]['direct_source_dependencies'][] = $target_path;
				$dependencies[$key]['dependency_categories'][] = ['target' => $target->qualified_name(), 'kind' => $target instanceof collected_function ? 'function' : ($target instanceof collected_constant ? 'constant' : 'class'), 'category' => 'direct', 'source_dependencies' => [$target_path]];
			}
		}
		$input = ['file_summaries' => $files, 'dependency_summaries' => array_values($dependencies)];
		$dependency = \collect_project_module_dependency_validation($this->root, $this->module_rows, $input, \normalize_project_module_dependency_policy($this->config, '.'));
		$public = \collect_project_module_public_api_validation($this->root, $this->contexts, $this->module_rows, $input, \normalize_project_module_public_policy($this->config, '.'));
		$this->report = ['format' => 'scpp-project-v1', 'evidence_owner' => 'v0.2-prepared-declarations', 'projects' => array_keys($this->contexts), 'modules' => $this->module_rows, 'dependency_validation' => $dependency, 'public_api_validation' => $public];
		\enforce_project_module_validation($dependency, $public);
	}
}
