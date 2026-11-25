"""Typed empty-default execution and recovery in ordinary and prelowered output."""
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
a = p.parse_args()
out = a.results.resolve()
out.mkdir(parents=True, exist_ok=False)
source = """function grow(vector<int> $values = []): int { $values[] = 7; return count($values); }
function keys(hash<int> $values = []): int { $values["item"] = 9; return count($values); }
function nested(vector<vector<int>> $values = []): int { return count($values); }
class Reader {
 public function read(vector<int> $values = []): int { return grow($values); }
}
$reader = new Reader();
$values vector<int> = [1, 2];
echo grow(), ":", grow(), ":", grow($values), ":", count($values), ":";
echo keys(), ":", keys(), ":", nested(), ":", $reader->read();
"""
expected = '1:1:3:2:1:1:0:1'
# A distinct generic provider proves admission and factory invocation without type-name gates.
modules = out / 'modules'
shutil.copytree(ROOT / 'runtime/metadata/modules', modules)
header = out / 'empty_provider.hpp'
provider_header = (APP / 'tests/runtime_protocol_support.hpp').read_text()
provider_header = provider_header.replace('namespace protocol_proof {',
    'namespace protocol_proof {\ninline int factory_calls = 0;\ninline scpp::int_t<> calls() { return scpp::int_t<>{factory_calls}; }', 1)
provider_header = provider_header.replace('static tray<T> from_list(scpp::initializer_list<T> input) {',
    'static tray<T> from_list(scpp::initializer_list<T> input) { ++factory_calls;')
header.write_text(provider_header)
provider = json.loads((APP / 'tests/runtime_protocols.json').read_text())
for section in ['cpp_types', 'cpp_initialization', 'cpp']:
    for entry in provider[section]:
        entry['header'] = str(header)
provider['functions'].append({'id': 'proof.calls', 'name': 'factory_calls', 'parameters': [], 'returns': 'int'})
provider['cpp'].append({'id': 'proof.calls', 'symbol': 'protocol_proof::calls', 'header': str(header)})
(modules / 'protocol_proof.json').write_text(json.dumps(provider))
provider_source = """function grow(tray<int> $values = []): int {
 $values[] = 7;
 $sum = 0;
 foreach ($values as $value) { $sum += $value; }
 return $sum;
}
echo factory_calls(), ":", grow(), ":", factory_calls(), ":", grow(), ":", factory_calls(), ":";
$explicit tray<int> = [2];
echo grow($explicit), ":", factory_calls();
"""
with CommandRunner(out / 'logs', ROOT) as runner:
    def check(mode, provider_case=False):
        label = ('provider-' if provider_case else '') + mode
        folder = out / label
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(provider_source if provider_case else source)
        runner.run(label + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder, modules, 'protocol_proof' if provider_case else 'compiler', mode])
        manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
        assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
        build = json.loads(runner.run(label + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
        actual = runner.run(label + '-execute', [build['executable']]).decode()
        wanted = '0:7:1:7:2:9:3' if provider_case else expected
        assert actual == wanted, (actual, wanted)
    run_tasks([Task(('provider-' if provider_case else '') + mode,
                    lambda mode=mode, provider_case=provider_case: check(mode, provider_case))
               for provider_case in [False, True] for mode in ['ordinary', 'concrete']] +
              [Task('contracts', lambda: runner.run('contracts',
                  ['php8.5', APP / 'tests/empty_defaults.php', out / 'contracts']))])
(out / 'summary.json').write_text(json.dumps({'passed': True, 'expected': expected}, indent=2) + '\n')
