"""Explicit empty-only factory execution, admission and recovery in both C++ modes."""
import argparse
import copy
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
modules = out / 'modules'
shutil.copytree(ROOT / 'runtime/metadata/modules', modules)
header = out / 'empty_provider.hpp'
header.write_text('''#pragma once
#include "scpp/index_write.hpp"
#include "scpp/value_iteration.hpp"
namespace empty_proof {
inline int calls = 0;
template <typename T> struct vessel {
 scpp::vector_t<T> entries;
 static vessel<T> make(scpp::int_t<> capacity) { ++calls; return {}; }
};
inline scpp::int_t<> factory_calls() { return scpp::int_t<>{calls}; }
}
namespace scpp {
template <typename T> void index_append(empty_proof::vessel<T>& v, const T& x) { v.entries.append(x); }
template <typename T> T checked_index_read(const empty_proof::vessel<T>& v, const int_t<>& i) { return v.entries.at(i); }
template <typename T> void index_store(empty_proof::vessel<T>& v, const int_t<>& i, const T& x) { v.entries.at(i) = x; }
template <typename T> auto value_iteration(const empty_proof::vessel<T>& v) { return foreach_range(v.entries); }
}
''')
provider = json.loads((APP / 'tests/runtime_protocols.json').read_text())
provider['module'] = 'empty_proof'
recipe = provider['types'][0]
recipe.update(id='empty.vessel', name='vessel', construction='make', default_constructible=True,
              empty_initialization='default')
recipe.pop('initialization')
provider['cpp_types'] = [{'id': 'empty.vessel', 'symbol': 'empty_proof::vessel', 'header': str(header)}]
provider.pop('cpp_initialization')
provider['functions'] = [
    {'id': 'empty.make', 'name': 'make', 'member': {'owner': 'vessel', 'dispatch': 'static'},
     'parameters': [{'name': 'capacity', 'type': 'int', 'passing': 'value', 'default': {'int': 0}}],
     'returns': {'name': 'vessel', 'arguments': [{'parameter': 'Element'}]}},
    {'id': 'empty.calls', 'name': 'factory_calls', 'parameters': [], 'returns': 'int'}]
provider['cpp'] = [{'id': 'empty.make', 'symbol': 'make', 'header': str(header)},
                   {'id': 'empty.calls', 'symbol': 'empty_proof::factory_calls', 'header': str(header)}]
(modules / 'empty_proof.json').write_text(json.dumps(provider))
source = '''class VesselHolder { public $values vessel<int> = []; }
class ImplicitDefaults { public $values vessel<int>; public $items vector<int>; public $lookup hash<int>; }
function grow(vessel<int> $values = []): int {
 $values[] = 7;
 $sum = 0;
 foreach ($values as $value) { $sum += $value; }
 return $sum;
}
echo factory_calls(), ":", grow(), ":", factory_calls(), ":", grow(), ":", factory_calls(), ":";
$explicit vessel<int> = vessel<int>::[];
$explicit[] = 2;
echo grow($explicit), ":", factory_calls();
$first = new VesselHolder(); $second = new VesselHolder();
$first->values[] = 11;
$first_sum = 0; foreach ($first->values as $value) { $first_sum += $value; }
$second_sum = 0; foreach ($second->values as $value) { $second_sum += $value; }
echo ":", $first_sum, ":", $second_sum, ":", factory_calls();
$implicit = new ImplicitDefaults();
$implicit_sum = 0; foreach ($implicit->values as $value) { $implicit_sum += $value; }
echo ":", $implicit_sum, ":", count($implicit->items), ":", count($implicit->lookup), ":", factory_calls();
'''
storage_source = '''class Item { public int $value = 1; }
function fresh(Storage<Item> $items = []): int {
 $items->append(new Item()); return $items->count();
}
$a Storage<Item> = Storage<Item>::[];
$b Keyed_Storage<Item> = [];
$c Key_Storage_List<Item> = [];
echo fresh(), ":", fresh(), ":", $a->count(), ":", $b->count(), ":", count($c->items());
'''
with CommandRunner(out / 'logs', ROOT) as runner:
    publisher = out / 'publish.php'
    publisher.write_text((APP / 'tests/async_tasks_publish.php').read_text()
                         .replace("dirname(__DIR__) . '/boot.php'", repr(str(APP / 'boot.php')))
                         .replace('$compiler->exec_cpp($publication);',
                                  '$compiler->exec_cpp($publication, new cpp_lowering_policy((int)$argv[5]));'))
    def check(mode, storage=False, level=2):
        label = ('storage-' if storage else 'provider-') + mode + '-O' + str(level)
        folder = out / label
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(storage_source if storage else source)
        runner.run(label + '-publish', ['php8.5', publisher, folder,
                                       modules, 'compiler' if storage else 'empty_proof', mode, str(level)])
        if not storage:
            emitted = '\n'.join(p.read_text() for p in (folder / 'published').rglob('*.cpp'))
            assert ('record_VesselHolder::record_VesselHolder()' in emitted) == (level < 2)
        build = json.loads(runner.run(label + '-build', ['php8.5', APP / 'tools/build_cpp.php',
                                                       folder / 'published', folder / 'cache'], timeout=300))
        actual = runner.run(label + '-execute', [build['executable']]).decode()
        expected = '1:1:0:0:0' if storage else '0:7:1:7:2:9:3:11:0:5:0:0:0:5'
        assert actual == expected, (actual, expected)

    def reject_registration(case):
        folder = out / case
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text('$value vessel<int> = [];')
        catalog = folder / 'modules'
        shutil.copytree(modules, catalog)
        changed = copy.deepcopy(provider)
        definition = changed['types'][0]
        if case == 'no-opt-in':
            definition.pop('empty_initialization')
            diagnostic = 'target does not support initialization shape'
        elif case == 'no-default-opt-in':
            definition.pop('default_constructible')
            (folder / 'source/main.phs').write_text(
                'class Holder { public $value vessel<int>; public function __construct() {} } new Holder();')
            diagnostic = 'Property requires set: initialization before the constructor body'
        elif case == 'no-factory':
            definition.pop('construction')
            diagnostic = 'Empty initialization requires a registered construction factory'
        elif case == 'conflicting-recipes':
            definition['initialization'] = [{'value': {'parameter': 'Element'}, 'nested': {'parameter': 'Element'}}]
            diagnostic = 'Empty-only and sequence initialization registrations conflict'
        else:
            changed['functions'][0]['parameters'][0].pop('default')
            diagnostic = 'Empty-only initialization requires a usable zero-argument construction'
        (catalog / 'empty_proof.json').write_text(json.dumps(changed))
        runner.run(case, ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                         catalog, 'empty_proof', 'concrete'], expected=255)
        error = (out / 'logs' / (case + '.stderr')).read_text()
        assert diagnostic in error, error

    run_tasks([Task(('storage-' if storage else 'provider-') + mode + '-O' + str(level),
                    lambda mode=mode, storage=storage, level=level: check(mode, storage, level))
               for storage in [False, True] for mode in ['ordinary', 'concrete'] for level in [0, 2]] +
              [Task('contracts', lambda: runner.run('contracts',
                  ['php8.5', APP / 'tests/empty_provider.php', out / 'contracts', modules]))] +
              [Task(case, lambda case=case: reject_registration(case))
               for case in ['no-opt-in', 'no-default-opt-in', 'no-factory', 'conflicting-recipes', 'required-argument']])
(out / 'summary.json').write_text(json.dumps({'passed': True}) + '\n')
