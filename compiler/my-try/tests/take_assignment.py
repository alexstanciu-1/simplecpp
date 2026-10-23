"""Conditional take initializes required outputs only on guaranteed payload paths."""
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
out = p.parse_args().results.resolve()
out.mkdir(parents=True, exist_ok=False)
# Test provider explicitly opts its shared payload into the existing capability.
# This does not change the production json_pull_reader result contract.
catalog = out / 'metadata'
shutil.copytree(ROOT / 'runtime/metadata/modules', catalog)
module = json.loads((catalog / 'json.json').read_text())
for declaration in module['types']:
    if declaration['name'] == 'json_pull_reader':
        declaration['capabilities'].append('result_payload')
(catalog / 'json.json').write_text(json.dumps(module))
cases = {
    'paths': ('''function extract(result<json_pull_reader> $wrapped): json_pull_reader {
 $out json_pull_reader; $failure error;
 if (!take($out, $failure, $wrapped)) { throw Exception::make("missing"); }
 return $out;
}
function fallback(result_or_false<json_pull_reader> $wrapped): json_pull_reader {
 $out json_pull_reader;
 if (take($out, $wrapped)) { return $out; }
 throw Exception::make("missing");
}
function via_while(result<json_pull_reader> $wrapped): json_pull_reader {
 $out json_pull_reader; $failure error;
 while (take($out, $failure, $wrapped)) { return $out; }
 throw Exception::make("missing");
}
function via_for(result<json_pull_reader> $wrapped): json_pull_reader {
 $out json_pull_reader; $failure error;
 for (;take($out, $failure, $wrapped);) { return $out; }
 throw Exception::make("missing");
}
function via_do(result<json_pull_reader> $wrapped): json_pull_reader {
 $out json_pull_reader; $failure error;
 do { continue; } while (!take($out, $failure, $wrapped));
 return $out;
}
$reader = json_pull_reader_create();
$wrapped result<json_pull_reader> = $reader;
$falseable result_or_false<json_pull_reader> = $reader;
echo extract($wrapped) === $reader, fallback($falseable) === $reader,
 via_while($wrapped) === $reader, via_for($wrapped) === $reader, via_do($wrapped) === $reader;
$a json_pull_reader; $error error;
if (take($a, $error, $wrapped) && ($a === $reader)) { echo "A"; }
$b json_pull_reader;
if (!take($b, $error, $wrapped) || ($b !== $reader)) { echo "bad"; } else { echo "B"; }
$c json_pull_reader;
$pick = take($c, $error, $wrapped) ? $c : $reader;
echo $pick === $reader;
$d json_pull_reader;
if (!!take($d, $error, $wrapped)) { echo $d === $reader; }
$failed result<json_pull_reader> = error::make("failure");
try { $unused = extract($failed); } catch (Exception $caught) { echo "F"; }
$old json_pull_reader = $reader;
$sentinel result_or_bool<json_pull_reader> = true_sentinel;
$flag bool = false;
echo take($old, $flag, $sentinel), $old === $reader;
''', b'11111AB11F11'),
}
negative = {
 'plain': ('function ignore(bool $x): bool { return $x; } function bad(result<json_pull_reader> $wrapped, json_pull_reader $reader): json_pull_reader {$out json_pull_reader; $failure error; take($out, $failure, $wrapped); return $out;}', 'Required local may be read before assignment'),
 'saved': ('function ignore(bool $x): bool { return $x; } function bad(result<json_pull_reader> $wrapped, json_pull_reader $reader): json_pull_reader {$out json_pull_reader; $failure error; $ok = take($out, $failure, $wrapped); if ($ok) { return $out; } return $reader;}', 'Required local may be read before assignment'),
 'false-arm': ('function ignore(bool $x): bool { return $x; } function bad(result<json_pull_reader> $wrapped, json_pull_reader $reader): json_pull_reader {$out json_pull_reader; $failure error; if (!take($out, $failure, $wrapped)) { return $out; } return $reader;}', 'Required local may be read before assignment'),
 'or-shortcut': ('function ignore(bool $x): bool { return $x; } function bad(result<json_pull_reader> $wrapped, json_pull_reader $reader): json_pull_reader {$out json_pull_reader; $failure error; if (true || take($out, $failure, $wrapped)) { return $out; } return $reader;}', 'Required local may be read before assignment'),
 'and-after': ('function ignore(bool $x): bool { return $x; } function bad(result<json_pull_reader> $wrapped, json_pull_reader $reader): json_pull_reader {$out json_pull_reader; $failure error; $ok = take($out, $failure, $wrapped) && ($out === $reader); return $out;}', 'Required local may be read before assignment'),
 'argument-leak': ('function ignore(bool $x): bool { return $x; } function bad(result<json_pull_reader> $wrapped, json_pull_reader $reader): json_pull_reader {$out json_pull_reader; $failure error; $unused = ignore(take($out, $failure, $wrapped)); if ($unused) { return $out; } return $reader;}', 'Required local may be read before assignment'),
 'comparison-leak': ('function ignore(bool $x): bool { return $x; } function bad(result<json_pull_reader> $wrapped, json_pull_reader $reader): json_pull_reader {$out json_pull_reader; $failure error; if (take($out, $failure, $wrapped) === true) { return $out; } return $reader;}', 'Required local may be read before assignment'),
 'while-zero': ('function ignore(bool $x): bool { return $x; } function bad(result<json_pull_reader> $wrapped, json_pull_reader $reader): json_pull_reader {$out json_pull_reader; $failure error; while (take($out, $failure, $wrapped)) { break; } return $out;}', 'Required local may be read before assignment'),
 'do-break': ('function ignore(bool $x): bool { return $x; } function bad(result<json_pull_reader> $wrapped, json_pull_reader $reader): json_pull_reader {$out json_pull_reader; $failure error; do { break; } while (!take($out, $failure, $wrapped)); return $out;}', 'Required local may be read before assignment'),
 'sentinel': ('function ignore(bool $x): bool { return $x; } function bad(result<json_pull_reader> $wrapped, json_pull_reader $reader): json_pull_reader {$out json_pull_reader; $flag bool; $sentinel result_or_bool<json_pull_reader> = true_sentinel; if (take($out, $flag, $sentinel)) { return $out; } return $reader;}', 'Required local may be read before assignment'),
}
with CommandRunner(out / 'logs', ROOT) as runner:
    def check(name, mode, data):
        source, expected = data
        label = name + '-' + mode
        folder = out / label
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(source)
        runner.run(label + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                        catalog, 'json', mode])
        build = json.loads(runner.run(label + '-build', ['php8.5', APP / 'tools/build_cpp.php',
                                                        folder / 'published', folder / 'cache'], timeout=300))
        actual = runner.run(label + '-execute', [build['executable']])
        assert actual == expected, (actual, expected)
    tasks = [Task(name + '-' + mode, lambda name=name, mode=mode, data=data: check(name, mode, data))
             for name, data in cases.items() for mode in ['ordinary', 'concrete']]
    def reject(name, program, diagnostic, mode):
        label = name + '-' + mode
        folder = out / label
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(program)
        runner.run(label, ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                          catalog, 'json', mode], expected=255)
        error = (out / 'logs' / (label + '.stderr')).read_text()
        assert diagnostic.lower() in error.lower(), error
    tasks += [Task(name + '-' + mode, lambda name=name, pair=pair, mode=mode: reject(name, *pair, mode))
              for name, pair in negative.items() for mode in ['ordinary', 'concrete']]
    tasks.append(Task('retained', lambda: runner.run('retained', ['php8.5', APP / 'tests/take_assignment.php', out / 'retained'])))
    results = run_tasks(tasks)
(out / 'summary.json').write_text(json.dumps([{'name': r.name, 'passed': r.error is None, 'seconds': r.seconds}
                                             for r in results], indent=2) + '\n')
