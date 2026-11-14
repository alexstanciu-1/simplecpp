<?php

declare(strict_types=1);
require_once $argv[1];
use scpp\compiler\{Incremental_Project, entry_target, type_registry, diagnostic, diagnostic_group, Type_Resolution, Source_Frontend, source_file, flavor, Program_Preparation, type_availability};

function verify(bool $condition, string $message): void
{
	if (!$condition) throw new RuntimeException($message);
}

/** @return string[] */
function artifacts(string $directory): array /** array<string,string> */
{
	$files = [];
	foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
		if (in_array($file->getExtension(), ['cpp', 'hpp'], true)) {
			$files[substr($file->getPathname(), strlen($directory))] = file_get_contents($file->getPathname());
		}
	}
	ksort($files);
	return $files;
}

verify(type_registry::required_module('byte') === 'test-numeric', 'Missing generated alias membership');
verify(type_registry::required_module('uint8') === 'test-numeric', 'Missing canonical membership');
verify(type_registry::resolve('uint32') === null, 'Optional widths activated');
$runtime = $argv[2] . '/runtime';
mkdir($runtime);
file_put_contents($runtime . '/module.json', json_encode(['name' => 'runtime', 'type' => 'c++', 'modules' => ['numeric' => 'numeric.json']]));
file_put_contents($runtime . '/numeric.json', json_encode(['name' => 'test-numeric', 'type' => 'c++']));
foreach (['php', 'cpp', 'js'] as $flavor) {
	$app = $argv[2] . '/' . $flavor;
	mkdir($app);
	$entry = 'main.' . $flavor;
	$write = static function (bool $enabled) use ($app, $entry): void {
		file_put_contents($app . '/module.json', json_encode(['name' => 'app', 'entry' => $entry, 'sources' => [$entry],
			'dependencies' => ['runtime' => ['modules' => $enabled ? ['numeric'] : []]]]));
	};
	$source = match ($flavor) {
		'php' => '$x byte = 8; echo (int)$x; return 8;',
		'cpp' => 'byte x = 8; echo (int)x; return 8;',
		'js' => 'let x:byte = 8; echo (int)x; return 8;',
	};
	file_put_contents($app . '/' . $entry, $source);
	$project = new Incremental_Project($app, $app . '/out', new entry_target(31), $runtime);
	$previous = null;
	foreach ([true, false, true, false, true] as $enabled) {
		$write($enabled);
		$fresh = new Incremental_Project($app, $app . '/fresh', new entry_target(31), $runtime);
		foreach ([$project, $fresh] as $instance) {
			try {
				$instance->run();
				verify($enabled, 'Unavailable type admitted');
			}
			catch (diagnostic|diagnostic_group $error) {
				verify(!$enabled && str_contains($error->getMessage(), 'requires module test-numeric'), $error->getMessage());
			}
		}
		$session = $project->session();
		verify($session->type_availability->allows('test-numeric') === $enabled, 'Wrong session selection');
		verify($session->files()[$session->entry_key()]->type_availability === $session->type_availability, 'Root lost context');
		if ($previous !== null) {
			verify($previous->type_availability->allows('test-numeric') !== $enabled, 'Old immutable context changed');
			verify(!$previous->is_current, 'Old session still current');
			if (!$enabled) {
				$written = $previous->files()[$previous->entry_key()]->main()->body()->statements[0]->type_reference;
				verify(Type_Resolution::resolve($written) === type_registry::resolve('byte'), 'Independent context leaked');
			}
		}
		$previous = $session;
		if ($enabled) {
			verify(artifacts($app . '/out') === artifacts($app . '/fresh'), 'Recovery differs from fresh');
			verify($project->run()['preparation']['prepared'] === 0, 'Unchanged selection lost reuse');
		} else {
			try {
				$project->run();
				throw new RuntimeException('Retry without edits admitted unavailable type');
			}
			catch (diagnostic|diagnostic_group $error) {
				verify(str_contains($error->getMessage(), 'requires module test-numeric'), $error->getMessage());
			}
		}
	}
	$forms = match ($flavor) {
		'php' => ['function f(byte $x): byte { return $x; } return 8;', 'struct S { public byte $x; } return 8;', '$x=(byte)8; return 8;'],
		'cpp' => ['byte f(byte x) { return x; } return 8;', 'struct S { byte x; } return 8;', 'auto x=(byte)8; return 8;'],
		'js' => ['function f(x:byte): byte { return x; } return 8;', 'struct S { x:byte; } return 8;', 'let x=(byte)8; return 8;'],
	};
	foreach ($forms as $form) {
		foreach ([new type_availability(), $session->type_availability] as $context) {
			$file = (new Source_Frontend())->collect(new source_file('gate', $form, flavor::from($flavor)), availability: $context);
			try {
				Program_Preparation::prepare('entry', ['entry' => $file], new entry_target(31));
				verify($context->allows('test-numeric'), 'Boundary bypassed availability');
			}
			catch (diagnostic|diagnostic_group $error) {
				verify(!$context->allows('test-numeric') && str_contains($error->getMessage(), 'requires module test-numeric'), $error->getMessage());
			}
		}
	}
	echo "$flavor module selection, isolation and recovery passed\n";
}
