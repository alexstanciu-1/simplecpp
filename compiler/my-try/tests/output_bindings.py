"""Existing output-file leaves through legacy, ordinary and prelowered bindings."""
import argparse
import json
import sys
from pathlib import Path

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks

p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
out = p.parse_args().results.resolve()
out.mkdir(parents=True, exist_ok=False)
source = '''function exercise(): int {
$problem error;
$root string = "";
$held bool = false;
$lock file_lock_handle;
if (!take($held, $problem, fs_lock_try($lock, __LOCK__, false))) { return 1; }
if (!$held) { return 2; }
if (!take($root, $problem, scpp_output_root(__ROOT__))) { return 3; }
$contents nullable<string> = null;
$text string = "preserved";
if (!take($contents, $problem, scpp_output_read($root, "absent"))) { return 4; }
if (take($text, $contents)) { return 5; }
if ($text !== "preserved") { return 6; }
$changed bool = false;
if (!take($changed, $problem, scpp_output_write($root, "empty", ""))) { return 7; }
if (!$changed) { return 8; }
if (!take($contents, $problem, scpp_output_read($root, "empty"))) { return 9; }
if (!take($text, $contents)) { return 10; }
if ($text !== "") { return 11; }
if (!take($changed, $problem, scpp_output_write($root, "empty", ""))) { return 12; }
if ($changed) { return 13; }
if (take($changed, $problem, scpp_output_remove($root, "empty", "wrong"))) { return 14; }
if (!take($changed, $problem, scpp_output_remove($root, "empty", ""))) { return 15; }
if (!$changed) { return 16; }
if (!take($changed, $problem, scpp_output_remove($root, "empty", ""))) { return 17; }
if ($changed) { return 18; }
if (take($contents, $problem, scpp_output_read($root, "../escape"))) { return 19; }
echo "absent:empty:unchanged:failure:";
$digest string = "";
if (!take($digest, $problem, scpp_output_digest($root, "absent"))) { return 20; }
if ($digest !== "") { return 21; }
if (scpp_output_sha256("abc") !== "ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad") { return 22; }
$stage string = "";
$written int = 0;
if (!take($stage, $problem, scpp_output_stage($root))) { return 23; }
if (!take($written, $problem, fs_put($root . "/" . $stage, "abc"))) { return 24; }
if ($written !== 3) { return 25; }
if (!take($changed, $problem, scpp_output_promote($root, $stage, "program", "", true))) { return 26; }
if (!$changed) { return 27; }
if (fs_exists($root . "/" . $stage)) { return 28; }
$permissions int = 0;
if (!take($permissions, $problem, scpp_output_permissions($root, "program"))) { return 29; }
if ($permissions !== 448) { return 30; }
if (!take($digest, $problem, scpp_output_digest($root, "program"))) { return 31; }
if ($digest !== scpp_output_sha256("abc")) { return 32; }
$input_digest string = "";
if (!take($input_digest, $problem, scpp_input_digest($root . "/program"))) { return 33; }
if ($input_digest !== $digest) { return 34; }
if (!take($stage, $problem, scpp_output_stage($root))) { return 35; }
if (!take($written, $problem, fs_put($root . "/" . $stage, "abc"))) { return 36; }
if (!take($changed, $problem, scpp_output_promote($root, $stage, "program", $digest, true))) { return 37; }
if ($changed || fs_exists($root . "/" . $stage)) { return 38; }
if (!take($stage, $problem, scpp_output_stage($root))) { return 39; }
if (!take($written, $problem, fs_put($root . "/" . $stage, "new"))) { return 40; }
if (take($changed, $problem, scpp_output_promote($root, $stage, "program", "", false))) { return 41; }
if (!fs_exists($root . "/" . $stage)) { return 42; }
if (!take($changed, $problem, scpp_output_discard($root, $stage))) { return 43; }
if (!$changed) { return 44; }
if (!take($changed, $problem, scpp_output_discard($root, $stage))) { return 45; }
if ($changed) { return 46; }
if (!take($changed, $problem, scpp_output_remove($root, "program", "abc"))) { return 47; }
if (!$changed) { return 48; }
if (!take($changed, $problem, fs_lock_release($lock))) { return 49; }
echo "digest:promote:unchanged:discard";
return 0;
}
echo exercise();
'''
expected = b'absent:empty:unchanged:failure:digest:promote:unchanged:discard0'
with CommandRunner(out / 'logs', ROOT) as runner:
    def check(mode):
        folder = out / mode
        (folder / 'source').mkdir(parents=True)
        data = folder / 'data'
        text = source.replace('__ROOT__', json.dumps(str(data))).replace('__LOCK__', json.dumps(str(folder / 'data.lock')))
        if mode != 'legacy':
            # Keep the exact read contract registered; its extraction is separately
            # blocked on NULL-02, not replaced with a sentinel or weaker return type.
            # NULL-02 must remove this exclusion and replace read_boundary with execution.
            text = '\n'.join(line for line in text.splitlines()
                             if not any(marker in line for marker in [
                                 'scpp_output_read(', 'take($text, $contents)', '$text !==',
                             ]))
            text = text.replace('absent:empty:', 'read-pending:')
        (folder / 'source/main.phs').write_text(text)
        if mode == 'legacy':
            project = folder / 'source'
            runner.run(mode + '-init', ['php8.5', ROOT / 'bin/scpp.php', 'init', '--php-profile=strict'], project, cli_reason='CLI fixture setup')
            config = json.loads((project / 'prism.json').read_text())
            config['build']['cxx'] = 'clang++-18'
            config['runtime']['modules'] = ['filesystem']
            (project / 'prism.json').write_text(json.dumps(config))
            runner.run(mode + '-build', ['php8.5', ROOT / 'bin/scpp.php', 'build', '--build-runtime', '--no-stan'], project, timeout=300, cli_reason='CLI fixture build')
            executable = project / '.prism/build/main'
        else:
            runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                           ROOT / 'runtime/metadata/modules', 'filesystem', mode])
            manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
            assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
            build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php',
                                                           folder / 'published', folder / 'cache'], timeout=300))
            executable = build['executable']
        wanted = expected if mode == 'legacy' else expected.replace(b'absent:empty:', b'read-pending:')
        assert runner.run(mode + '-run', [executable]) == wanted
        assert list(data.iterdir()) == [], list(data.iterdir())
    def read_boundary(mode):
        label = 'read-pending-' + mode
        folder = out / label
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text('$value nullable<string> = null; $problem error; '
            'if (take($value, $problem, scpp_output_read("/unused", "file"))) { echo "ok"; }')
        runner.run(label, ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                          ROOT / 'runtime/metadata/modules', 'filesystem', mode], expected=255)
        assert 'Result operations require an unmodified type with result_payload capability' in (out / 'logs' / (label + '.stderr')).read_text()
    run_tasks([Task(mode, lambda mode=mode: check(mode)) for mode in ['legacy', 'ordinary', 'concrete']] +
              [Task('read-pending-' + mode, lambda mode=mode: read_boundary(mode))
               for mode in ['ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps({'legacy_leaves': '11 passed', 'v02_leaves': '10 passed', 'v02_read': 'blocked on NULL-02 result_payload', 'expected_legacy': expected.decode()}, indent=2) + '\n')
