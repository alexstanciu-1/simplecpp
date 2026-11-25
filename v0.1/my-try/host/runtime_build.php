<?php
namespace scpp\compiler;

// Platform discovery stays with the existing host project service, outside the
// portable compiler. Resolve before retaining startup metadata or publishing it.
require_once dirname(__DIR__, 3) . '/bin/project_services.php';

final class Host_Runtime_Build
{
	public static function resolve(runtime_catalog_input $input): void
	{
		foreach ($input->modules as $module) {
			$build = $module->cpp_build;
			foreach ($build->providers as $provider) {
				// The current native consumer is explicitly Linux/Clang. Do not
				// silently publish macOS framework or Windows loader requirements.
				if (PHP_OS_FAMILY !== 'Linux') {
					throw new \RuntimeException('UI/WebView native build integration currently requires Linux');
				}
				$resolved = match ($provider) {
					'ui' => \resolve_runtime_ui_build_spec(),
					'webview' => \resolve_runtime_webview_build_spec(),
					default => throw new \RuntimeException('Unknown runtime SDK provider: ' . $provider),
				};
				foreach ($resolved['compile_defines'] as $definition) {
					if (!str_starts_with($definition, '-D')) { throw new \RuntimeException('Invalid provider definition'); }
					$build->definitions[] = substr($definition, 2);
				}
				foreach ($resolved['cflags'] as $option) { $build->compile_options[] = $option; }
				foreach ($resolved['ldflags'] as $option) { $build->link_options[] = $option; }
				foreach ($resolved['diagnostics'] ?? [] as $diagnostic) { $build->diagnostics[] = $diagnostic; }
				if (!$resolved['enabled'] && ($provider === 'ui')) {
					$build->diagnostics[] = 'UI disabled: GTK 3 development files were not found by the existing provider resolver.';
				}
			}
			$build->providers = [];
			Runtime_Build_Contract::validate($build);
		}
	}
}
