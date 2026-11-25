<?php
namespace scpp\compiler;
// Host-only measurement fixture: call existing coordinator phases with retained state.
require_once '/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/boot.php';
$folder = $argv[1];
$prepared = $argv[2] === 'prepared';
$source = $folder . '/source';
$published = $folder . '/published';
$compiler = new Compiler();
$compiler->cpp_lowering->prelower_templates = $prepared;
$compiler->cpp_lowering->concrete_type_substitution = $prepared;
$compiler->init([$source]);
$compiler->select_entry('main', 'main.phs');
$config = new native_cpp_config($folder . '/clang.py', '/home/alexv/__AI/simple_cpp/simple_cpp_01/runtime/include', $folder . '/cache');
$config->pch = !$prepared;
$config->optimization = 0;
$changed = $source . '/src/closing_checks.phs';
$original = file_get_contents($changed);
$rows = [];
$previous = [];
for ($revision = 0; $revision < 5; $revision++) {
    // Revisions 1..3 change one function body; revision 4 is a no-change invocation.
    $editing = ($revision > 0) && ($revision < 4);
    $started = hrtime(true);
    if ($editing) {
        $replacement = 'Owner transfer revision ' . $revision;
        $text = str_replace('Owner transfer', $replacement, $original, $matches);
        if ($matches !== 1) { throw new \LogicException('Expected exactly one literal edit'); }
        file_put_contents($changed, $text);
    }
    $written = hrtime(true);
    $compiler->sync($editing ? [$changed] : []);
    $synced = hrtime(true);
    $compiler->prepare(true);
    $analyzed = hrtime(true);
    $publication = new Output_Publication($published);
    try { $compiler->cpp($publication); } finally { $publication->close(); }
    $emitted = hrtime(true);
    $snapshot = [];
    $changed_artifacts = [];
    foreach (Model::$cpp_files as $artifact) {
        $snapshot[$artifact->file_name] = hash('sha256', $artifact->text);
        if (($previous[$artifact->file_name] ?? '') !== $snapshot[$artifact->file_name]) { $changed_artifacts[] = $artifact->file_name; }
    }
    $previous = $snapshot;
    $log = $folder . '/clang.jsonl';
    clearstatcache(true, $log);
    $offset = is_file($log) ? filesize($log) : 0;
    $build_start = hrtime(true);
    $result = (new Native_CPP_Build($config))->build($published);
    $ready = hrtime(true);
    if ($editing && ($result->compiled !== 1 || $result->linked !== 1 || $result->layouts_built !== 0 || $result->pch_built !== 0)) {
        throw new \LogicException('Body edit rebuilt unexpected native artifacts: ' . json_encode($result));
    }
    if ($revision === 4 && ($result->compiled !== 0 || $result->linked !== 0)) { throw new \LogicException('No-op native work'); }
    $phase_seconds = []; $phase_counts = [];
    foreach (explode("\n", trim(file_get_contents($log, false, null, $offset))) as $line) {
        if ($line === '') { continue; }
        $command = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        $phase = $command['phase'];
        $phase_seconds[$phase] = ($phase_seconds[$phase] ?? 0) + $command['seconds'];
        $phase_counts[$phase] = ($phase_counts[$phase] ?? 0) + 1;
    }
    $build_seconds = ($ready - $build_start) / 1e9;
    $row = ['revision' => $revision, 'edit_write' => ($written - $started) / 1e9,
        'frontend_sync' => ($synced - $written) / 1e9, 'preparation' => ($analyzed - $synced) / 1e9,
        'emission_publication' => ($emitted - $analyzed) / 1e9, 'build_wall' => $build_seconds,
        'build_other' => $build_seconds - array_sum($phase_seconds), 'native_phases' => $phase_seconds,
        'native_calls' => $phase_counts, 'measured_total' => ($ready - $started) / 1e9,
        'observer_gap' => ($build_start - $emitted) / 1e9, 'changed_artifacts' => $changed_artifacts,
        'compiled' => $result->compiled, 'reused' => $result->reused, 'linked' => $result->linked,
        'layouts_built' => $result->layouts_built, 'layouts_reused' => $result->layouts_reused,
        'pch_built' => $result->pch_built, 'executable' => $result->executable];
    $rows[] = $row;
    file_put_contents($folder . '/results.json', json_encode($rows, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
}
echo json_encode($rows, JSON_THROW_ON_ERROR), "\n";
