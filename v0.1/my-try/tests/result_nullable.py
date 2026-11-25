"""Nested Result nullable payload states and recursive prelowered transport."""
import argparse
import json
import shutil
import sys
from pathlib import Path
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
p.add_argument('--case', action='append', default=[])
args = p.parse_args()
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=False)
modules = out / 'modules'
shutil.copytree(ROOT / 'runtime/metadata/modules', modules)
provider = json.loads((APP / 'tests/runtime_protocols.json').read_text())
for section in ['cpp_types', 'cpp_initialization', 'cpp']:
    for entry in provider[section]:
        entry['header'] = str(APP / 'tests/runtime_protocol_support.hpp')
provider['dependencies'].append('filesystem')
(modules / 'protocol_proof.json').write_text(json.dumps(provider))
cases = {
 'read-binding': ('''$out ?string = "old"; $problem error;
if (!take($out, $problem, scpp_output_read("__DATA__", "absent"))) { return 1; }
if (isset($out)) { return 2; }
if (!take($out, $problem, scpp_output_read("__DATA__", "empty"))) { return 3; }
$text string = $out; if ($text !== "") { return 4; }
if (!take($out, $problem, scpp_output_read("__DATA__", "present"))) { return 5; }
$text = $out; echo $text;
if (take($out, $problem, scpp_output_read("__DATA__", "../escape"))) { return 6; }
$text = $out; echo ":", $text;
''', b'contents:contents'),
 'sentinels': ('''$empty ?int = null; $out ?int = 9; $flag = false;
$yes result_or_false<?int> = $empty;
if (!isset($yes)) { return 1; }
if (!take($out, $yes)) { return 2; }
if (isset($out)) { return 3; }
$no result_or_false<?int> = false_sentinel;
$out = 9; if (take($out, $no)) { return 4; }
$n int = $out; echo $n;
$sentinel result_or_bool<?int> = true_sentinel;
if (!isset($sentinel)) { return 5; }
if (!take($out, $flag, $sentinel)) { return 6; }
if (!$flag) { return 7; }
$n = $out; echo ":", $n;
$value result_or_bool<?int> = $empty;
if (!take($out, $flag, $value)) { return 8; }
if (isset($out)) { return 9; }
''', b'9:9'),
 'states': ('''function wrap(?string $v): result<?string> { return $v; }
function unwrap(result<?string> $v): ?string { return $v; }
$empty ?string = null; $present ?string = "hello";
$success = wrap($empty); $copy = $success; $error error;
$out ?string = $present;
if (!isset($copy)) { return 1; }
if (!take($out, $error, $copy)) { return 2; }
if (isset($out)) { return 3; }
if ($error) { return 4; }
$filled = wrap($present); $again = unwrap($filled); $text string = $again;
echo $text;
$failure result<?string> = error::make("failed");
$out = $present;
if (take($out, $error, $failure)) { return 5; }
if (!$error) { return 6; }
$old string = $out; echo ":", $old;
''', b'hello:hello'),
 'provider': ('''$value tray<int> = [11]; $optional ?tray<int> = $value;
$wrapped result<?tray<int>> = $optional;
$out ?tray<int> = null; $error error;
if (!take($out, $error, $wrapped)) { return 1; }
$copy tray<int> = $out; $copy[0] = 19;
$again ?tray<int> = $wrapped; $saved tray<int> = $again;
echo $saved[0], ":", $copy[0];
''', b'11:19'),
 'handles': ('''class Node { public int $n = 7; }
$node = new Node(); $optional ?Node = $node;
$wrapped result<?Node> = $optional; $out ?Node = null; $error error;
$extracted ?Node = $wrapped; if ($extracted !== $node) { return 8; }
if (!take($out, $error, $wrapped)) { return 1; }
echo $out === $node;
$empty ?Node = null; $success result<?Node> = $empty;
if (!take($out, $error, $success)) { return 2; }
if (isset($out)) { return 3; }
echo ":empty";
''', b'1:empty'),
}
negative = {'noncopy': ('struct Owned { public unique<int> $value; } $source ?Owned; $result result<?Owned> = $source;', 'copyable_value')}
with CommandRunner(out / 'logs', ROOT) as runner:
    def check(name, mode, data):
        source, expected = data
        if mode == 'legacy':
            source = 'function exercise(): int {\n' + source + '\nreturn 0; } echo exercise();'
            expected += b'0'
        label = name + '-' + mode
        folder = out / label
        (folder / 'source').mkdir(parents=True)
        data_dir = folder / 'data'
        data_dir.mkdir()
        (data_dir / 'empty').write_text('')
        (data_dir / 'present').write_text('contents')
        if name == 'read-binding' and mode == 'ordinary':
            script = folder / 'read.php'
            script.write_text('''<?php
require $argv[1];
$root = $argv[2];
$values = [fs_output_read($root, 'absent'), fs_output_read($root, 'empty'), fs_output_read($root, 'present')];
try { fs_output_read($root, '../escape'); $values[] = false; }
catch (Throwable $e) { $values[] = true; }
echo json_encode($values);
''')
            actual = runner.run(label + '-php', ['php8.5', script, ROOT / 'tools/php_portability/runtime/bootstrap.php', data_dir])
            assert json.loads(actual) == [None, '', 'contents', True], actual
        (folder / 'source/main.phs').write_text(source.replace('__DATA__', str(data_dir)))
        if mode == 'legacy':
            project = folder / 'source'
            runner.run(label + '-init', ['php8.5', ROOT / 'bin/scpp.php', 'init', '--php-profile=strict'], project, cli_reason='CLI fixture setup')
            config = json.loads((project / 'prism.json').read_text())
            config['build']['cxx'] = 'clang++-18'
            config['runtime']['modules'] = ['filesystem']
            (project / 'prism.json').write_text(json.dumps(config))
            runner.run(label + '-build', ['php8.5', ROOT / 'bin/scpp.php', 'build', '--build-runtime', '--no-stan'], project, timeout=300, cli_reason='CLI fixture build')
            executable = project / '.prism/build/main'
        else:
            runner.run(label + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                            modules, 'protocol_proof', mode])
            build = json.loads(runner.run(label + '-build', ['php8.5', APP / 'tools/build_cpp.php',
                                                            folder / 'published', folder / 'cache'], timeout=300))
            executable = build['executable']
        actual = runner.run(label + '-execute', [executable])
        assert actual == expected, (actual, expected)
    tasks = [Task(name + '-' + mode, lambda name=name, mode=mode, data=data: check(name, mode, data))
             for name, data in cases.items() if not args.case or name in args.case for mode in ['ordinary', 'concrete']]
    def reject(name, program, diagnostic, mode):
        if mode == 'legacy':
            source = 'function exercise(): int {\n' + source + '\nreturn 0; } echo exercise();'
            expected += b'0'
        label = name + '-' + mode
        folder = out / label
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(program)
        runner.run(label, ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                          modules, 'protocol_proof', mode], expected=255)
        error = (out / 'logs' / (label + '.stderr')).read_text()
        assert diagnostic.lower() in error.lower(), error
    tasks += [Task(name + '-' + mode, lambda name=name, pair=pair, mode=mode: reject(name, *pair, mode))
              for name, pair in negative.items() if not args.case or name in args.case for mode in ['ordinary', 'concrete']]
    if not args.case or 'read-binding' in args.case:
        tasks.append(Task('read-binding-legacy', lambda: check('read-binding', 'legacy', cases['read-binding'])))
    if not args.case or 'retained' in args.case:
        tasks.append(Task('retained', lambda: runner.run('retained', ['php8.5', APP / 'tests/result_nullable.php', out / 'retained'])))
    if not args.case:
        tasks.append(Task('registry', lambda: runner.run('registry', ['php8.5', APP / 'tests/type_capabilities.php'])))
    results = run_tasks(tasks)
(out / 'summary.json').write_text(json.dumps([{'name': r.name, 'passed': r.error is None, 'seconds': r.seconds}
                                             for r in results], indent=2) + '\n')
