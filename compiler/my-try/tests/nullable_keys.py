"""Optional keys use checked argument conversion before indexed operations."""
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
cases = {
    'values': ('''class Key {} class Child extends Key {}
function key(Child $value, int &$calls): ?Child { $calls++; return $value; }
$original = new Child();
$input ?Child = $original;
$map hash<int, Key> = [];
$calls = 0;
$map[key($original, $calls)] = 7;
echo $map[$input], ":", $calls, ":", isset($map[$input]) ? 1 : 0, ":";
$nested hash<hash<int>, Key> = [];
$nested[$input] = hash<int>::[];
$name ?string = "part";
$nested[$input][$name] = 9;
echo $nested[$input][$name], ":", isset($nested[$input][$name]) ? 1 : 0, ":";
unset($nested[$input][$name]);
echo isset($nested[$input][$name]) ? 1 : 0, ":";
$index ?int = 0;
$vector vector<int> = [11];
echo $vector[$index], ":";
unset($map[$input]);
echo isset($map[$input]) ? 1 : 0;
''', b'7:1:1:9:1:0:11:0', False),
    'missing': ('''class Key {}
function value(): int { echo "unexpected"; return 1; }
$map hash<int, Key> = [];
$key ?Key = null;
echo "before:";
$map[$key] = value();
echo "after";
''', b'before:', True),
}
with CommandRunner(out / 'logs', ROOT) as runner:
    def check(name, mode, data):
        source, expected, failure = data
        label = name + '-' + mode
        folder = out / label
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(source)
        runner.run(label + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                        ROOT / 'runtime/metadata/modules', 'core', mode])
        build = json.loads(runner.run(label + '-build', ['php8.5', APP / 'tools/build_cpp.php',
                                                        folder / 'published', folder / 'cache'], timeout=300))
        actual = runner.run(label + '-execute', [build['executable']], expected=1 if failure else 0)
        assert actual == expected, (actual, expected)
        if failure:
            assert 'nullable required-value extraction' in (out / 'logs' / (label + '-execute.stderr')).read_text()
    tasks = [Task(name + '-' + mode, lambda name=name, mode=mode, data=data: check(name, mode, data))
             for name, data in cases.items() for mode in ['ordinary', 'concrete']]
    tasks += [Task('indexing', lambda: runner.run('indexing', ['php8.5', APP / 'tests/indexing.php'])),
              Task('contracts', lambda: runner.run('contracts', ['php8.5', APP / 'tests/object_key_contracts.php', out / 'contracts']))]
    results = run_tasks(tasks)
(out / 'summary.json').write_text(json.dumps([{'name': r.name, 'passed': r.error is None, 'seconds': r.seconds}
                                             for r in results], indent=2) + '\n')
