<?php
namespace scpp\compiler;

/** Project-level backend requirements follow the selected dependency closure. */
final class CPP_Runtime_Build
{
	public static function requirements(runtime_catalog_input $input): runtime_cpp_build_spec
	{
		$result = new runtime_cpp_build_spec();
		$seen_sources /** hash<bool> */ = [];
		$definitions /** hash<string> */ = [];
		$modules /** vector<runtime_module_spec> */ = [];
		foreach ($input->modules as $module) {
			$modules[] = $module;
			$requirements /** runtime_cpp_build_spec */ = $module->cpp_build;
			if (q_count($requirements->providers) !== 0) { throw new \RuntimeException('Runtime provider requirements need host resolution before publication'); }
			foreach ($requirements->compile_options as $option) { $result->compile_options[] = $option; }
			foreach ($requirements->diagnostics as $diagnostic) { $result->diagnostics[] = $diagnostic; }
			foreach ($requirements->sources as $source) {
				if (!isset($seen_sources[$source])) { $result->sources[] = $source; $seen_sources[$source] = true; }
			}
			foreach ($requirements->definitions as $definition) {
				$name = Runtime_Build_Contract::definition_name($definition);
				if (isset($definitions[$name])) {
					if ($definitions[$name] !== $definition) { throw new \RuntimeException('Conflicting runtime build definition: ' . $name); }
					continue;
				}
				$definitions[$name] = $definition;
				$result->definitions[] = $definition;
			}
		}
		// Libraries follow consumers before dependencies for ordinary static-link ordering.
		$seen_libraries /** hash<bool> */ = [];
		for ($index = q_count($modules) - 1; $index >= 0; $index -= 1) {
			$consumer /** runtime_module_spec */ = $modules[$index];
			$consumer_requirements /** runtime_cpp_build_spec */ = $consumer->cpp_build;
			foreach ($consumer_requirements->link_options as $option) { $result->link_options[] = $option; }
			foreach ($consumer_requirements->libraries as $library) {
				if (!isset($seen_libraries[$library])) { $result->libraries[] = $library; $seen_libraries[$library] = true; }
			}
		}
		Runtime_Build_Contract::validate($result);
		return $result;
	}
}
