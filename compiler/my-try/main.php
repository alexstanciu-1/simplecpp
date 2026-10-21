<?php

/*
 * Role: browser/CLI host entry for C++ S2S output or the existing compiler debug report.
 * Call map: boot.php -> Compiler::init -> exec_cpp (S2S) or exec_llvm (debug report).
 * This host entry is not part of the convertible compiler core.
 */
namespace scpp\compiler;

\define('dbg', true);
require_once __DIR__ . '/boot.php';

if (PHP_SAPI !== 'cli') {
	header('Content-Type: text/plain; charset=UTF-8');
}

// CLI: --s2s SOURCE_DIRECTORY [--entry RELATIVE_PATH] [--output ABSOLUTE_DIRECTORY] [--optimization 0|1|2|3].
// HTTP also accepts entry=RELATIVE_PATH, but never selects an output directory.
// No parameter retains the existing sample/debug report.
$lowering = new cpp_lowering_options();
$s2s = false;
$source_directory = '';
$project_mode = false;
$entry_path = '';
$output_directory = '';
$optimization = 2;
$lowering_report = '';
$lowering_explain = '';
$usage_error = false;
if (PHP_SAPI === 'cli')
{
	if ($argc !== 1)
	{
		if (($argc < 3) || !in_array($argv[1], ['--s2s', '--project'], true)) {
			$usage_error = true;
		}
		else {
			$s2s = true;
			$project_mode = $argv[1] === '--project';
			$source_directory = $argv[2];
			$usage_error = $source_directory === '';
			for ($index = 3; $index < $argc; $index++) {
				if ($argv[$index] === '--prelower-templates') {
					$lowering->prelower_templates = true; continue;
				}
				if ($argv[$index] === '--no-prelower-templates') {
					$lowering->prelower_templates = false; continue;
				}
				if ($index + 1 >= $argc) {
					$usage_error = true; break;
				}
				if (($argv[$index] === '--entry') && ($entry_path === '')) { $entry_path = $argv[$index + 1]; }
				elseif (($argv[$index] === '--optimization') && preg_match('/^[0-3]$/D', $argv[$index + 1])) { $optimization = (int)$argv[$index + 1]; }
				elseif (($argv[$index] === '--lowering-report') && ($lowering_report === '')) { $lowering_report = $argv[$index + 1]; }
				elseif (($argv[$index] === '--lowering-explain') && ($lowering_explain === '')) { $lowering_explain = $argv[$index + 1]; }
				elseif (($argv[$index] === '--output') && ($output_directory === '')) { $output_directory = $argv[$index + 1]; }
				else { $usage_error = true; }
				if ($argv[$index + 1] === '') { $usage_error = true; }
				$index++;
			}
		}
	}
}
else
{
	$mode = $_GET['mode'] ?? 'report';
	$source_directory = $_GET['source'] ?? '';
	$entry_path = $_GET['entry'] ?? '';
	if ((!is_string($mode)) || (!is_string($source_directory)) || (!is_string($entry_path))) {
		$usage_error = true;
	}
	elseif ($mode === 's2s') {
		$s2s = true;
		$usage_error = $source_directory === '';
	}
	elseif ($mode !== 'report') {
		$usage_error = true;
	}
}
if (($lowering_report !== '') && ($lowering_report === $lowering_explain)) { $usage_error = true; }
if ($usage_error)
{
	if (PHP_SAPI === 'cli') {
		fwrite(STDERR, "Usage: php compiler/my-try/main.php [(--s2s SOURCE_DIRECTORY | --project PRISM_JSON_OR_DIRECTORY) [--entry RELATIVE_PATH] [--output ABSOLUTE_DIRECTORY] [--prelower-templates|--no-prelower-templates] [--optimization 0|1|2|3] [--lowering-report PATH] [--lowering-explain PATH]] (prelowering is the default)\n");
	}
	else {
		http_response_code(400);
		echo "Usage: main.php?mode=s2s&source=SOURCE_DIRECTORY&entry=RELATIVE_PATH (entry optional for one source; or mode=report)\n";
	}
	exit(1);
}

if ($s2s)
{
	$publication = null;
	$failed = false;
	try {
		if ($output_directory !== '') { $publication = new Output_Publication($output_directory); }
		$compiler = new Compiler($lowering);
		$project = null;
		if ($project_mode) {
			require_once __DIR__ . '/host/project.php';
			$project = new Project_Compilation($source_directory);
			$project->initialize($compiler, $entry_path);
		}
		else { $compiler->init([$source_directory]); }
		if (!$project_mode && $entry_path !== '') { $compiler->select_entry('main', $entry_path); }
		$policy = new cpp_lowering_policy($optimization);
		if ($project !== null) {
			$compiler->sync([]);
			$compiler->prepare(true);
			$project->validate();
			fwrite(STDERR, json_encode($project->report, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n");
			$compiler->cpp($publication, $policy);
		}
		else { $compiler->exec_cpp($publication, $policy); }
		foreach ([$lowering_report => false, $lowering_explain => true] as $report_path => $explain) {
			if ($report_path === '') { continue; }
			// Exclusive creation prevents a diagnostic path from overwriting source/build inputs.
			$handle = fopen($report_path, 'xb');
			if ($handle === false) { throw new \RuntimeException('Cannot create lowering report: ' . $report_path); }
			try {
				$report = CPP_Lowering_Report::encode(Model::$cpp_files, Model::$cpp_output_program, $policy, $explain);
				if (fwrite($handle, $report) !== strlen($report)) { throw new \RuntimeException('Incomplete lowering report write'); }
			}
			finally { fclose($handle); }
		}
		echo CPP_Output::encode(Model::$cpp_files);
	}
	catch (\Throwable $error)
	{
		$message = 'S2S generation failed: ' . $error->getMessage() . "\n";
		if (PHP_SAPI === 'cli') {
			fwrite(STDERR, $message);
		}
		else {
			http_response_code(500);
			echo $message;
		}
		$failed = true;
	}
	finally {
		if ($publication !== null) { $publication->close(); }
	}
	exit($failed ? 1 : 0);
}

// Imported stage reports escape their output for an HTML preformatted block.
// Buffer once so CLI/plain-text HTTP clients receive the original text safely.
ob_start();
try
{
	$paths /** vector<string> */ = [__DIR__ . '/tests/samples/01_base'];
	$compiler = new Compiler();
	$compiler->init($paths);
	$compiler->exec_llvm();

	echo "Simple C++ — PHP compiler\n\n";
	foreach (Model::$modules as $module)
	{
		if ($module->changes === change_state::deleted) {
			continue;
		}
		echo "Module: {$module->resolved_path}\n";
		foreach ($module->sources as $record) {
			$file = $record->file;
			echo "\nSource: {$file->path}\n";
			echo htmlspecialchars($file->content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), "\n";
		}
	}
	$host = new Host_Report();
	$host->show();
	$host->run_native();
	$report = ob_get_clean();
	echo html_entity_decode($report, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
catch (\Throwable $error)
{
	ob_end_clean();
	if (PHP_SAPI !== 'cli') {
		http_response_code(500);
	}
	echo "Compilation failed: ", $error->getMessage(), "\n";
	exit(1);
}
