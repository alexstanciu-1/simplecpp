"""Opaque shared provider receivers: checks, retention and selected arguments."""
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
modules = out / 'modules'
shutil.copytree(ROOT / 'runtime/metadata/modules', modules)
metadata = json.loads((APP / 'tests/runtime_handles.json').read_text())
for section in ['cpp', 'cpp_types', 'cpp_properties']:
    for item in metadata[section]:
        item['header'] = str(APP / 'tests/runtime_handles.hpp')
(modules / 'handles.json').write_text(json.dumps(metadata))
cases = {
    'retained': ('''function replace(ticket &$receiver): int {
    ticket_clear($receiver); echo "argument:"; return 9;
}
$reader ticket = open_ticket(1);
$alias ticket = $reader;
$reader->change(replace($reader));
echo $alias->read(), ":", $alias->echo_value(17);
''', b'argument:9:17', 0),
    'null': ('''function argument(): int { echo "unexpected"; return 1; }
$reader ticket = null;
echo "before:";
$reader->change(argument());
echo "after";
''', b'before:', 1),
    'observed': ('''$reader ticket = open_ticket(23);
$other ticket = open_ticket(31);
echo $reader->read_other($other);
''', b'31', 0),
}
with CommandRunner(out / 'logs', ROOT) as runner:
    def check(case, mode):
        label = case + '-' + mode
        folder = out / label
        (folder / 'source').mkdir(parents=True)
        source, expected, status = cases[case]
        (folder / 'source/main.phs').write_text(source)
        runner.run(label + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                       modules, 'handles', mode])
        manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
        assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
        build = json.loads(runner.run(label + '-build', ['php8.5', APP / 'tools/build_cpp.php',
                                                       folder / 'published', folder / 'cache'], timeout=300))
        actual = runner.run(label + '-run', [build['executable']], expected=status)
        assert actual == expected, (actual, expected)
        if status:
            assert 'operator->() requires a present shared pointer value' in (out / 'logs' / (label + '-run.stderr')).read_text()
    results = run_tasks([Task(case + '-' + mode, lambda case=case, mode=mode: check(case, mode))
                        for case in cases for mode in ['ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps([{'name': r.name, 'seconds': r.seconds, 'passed': r.error is None}
                                             for r in results], indent=2) + '\n')
