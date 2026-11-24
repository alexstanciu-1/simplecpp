"""One boxed descriptor implements read, write and existing-key mutation capabilities."""
import json
from pathlib import Path
import subprocess
import sys
import tempfile

from interfaces_native import forms
from parent_methods_native import write_app


def fixture(flavor, family):
    s, _, _, method, _, implementation = forms(flavor)
    v, access = s['v'], s['access']
    key = {'php': 'int $key', 'cpp': 'int key', 'js': 'key:int'}[flavor]
    items = f'{v}this{access}items'
    slot = f'{items}[{v}key]'
    operations = [
        ('IndexedRead', 'readAt', family, f'return {slot};'),
        ('IndexWrite', 'storeAt', 'void', f'{slot}=7;'),
        ('IndexedMutation', 'incrementAt', 'void', f'{slot}+=2;'),
    ]
    parents = ' : public IndexedRead, public IndexWrite' if flavor == 'cpp' else ' extends IndexedRead, IndexWrite'
    contracts, methods, callers = [], [], []
    for interface, name, result, body in operations:
        signature = {'cpp': f'{result} {name}({key});', 'php': f'public function {name}({key}):{result};',
                     'js': f'{name}({key}):{result};'}[flavor]
        bases = parents if interface == 'IndexedMutation' else ''
        contracts.append(f'interface {interface}{bases} {{ {signature} }}')
        methods.append(method(name, body, result=result, params=key, intent='override'))
        call = f'{v}view{access}{name}({v}key);'
        callers.append(s['function']('via'+interface, result, [('view', interface), ('key', 'int')],
                                     call if result == 'void' else 'return '+call))

    descriptor = implementation('Descriptor', s['field']('items', family) + ''.join(methods),
                                interfaces=('IndexedMutation',))
    # Convert the concrete object to the composite view, then to each parent at the call boundary.
    exercise = s['function']('exercise', family, [('d', 'IndexedMutation')],
                             f'viaIndexWrite({v}d,0); viaIndexedMutation({v}d,0); return viaIndexedRead({v}d,0);')
    missing = s['function']('missing', family, [('d', 'IndexedMutation')], f'return viaIndexedRead({v}d,1);')
    factory = s['function']('makeDescriptor', 'Descriptor', [], 'return new Descriptor();')
    view = s['function']('asMutation', 'IndexedMutation', [('d', 'Descriptor')], f'return {v}d;')
    return ''.join(contracts) + descriptor + ''.join(callers) + exercise + missing + factory + view + 'return 8;'


def prove(work, flavor, family, php, cxx, linker, archive):
    app = work / (flavor + '-' + family)
    output = work / (app.name + '-out')
    write_app(app, flavor, {'main': fixture(flavor, family)})
    root = Path(__file__).resolve().parents[2]
    subprocess.run([php, root/'compiler/main.php', 'project', app, archive.parent/'native-layout/facts.json', output],
                   check=True, stdout=subprocess.DEVNULL)
    units = json.loads((output/'manifest.json').read_text())['translation_units']
    unit, = [output/name for name in units if name != 'entry.cpp']
    emitted = unit.read_text()
    for binding in ('.source_read(', '.source_require(', '.source_set('):
        assert binding in emitted, (flavor, family, binding)

    probe = app/'probe.cpp'
    probe.write_text(f'#include "{unit}"\n' + '''
#include <cassert>
#include <stdexcept>
#include <string_view>
int main() {
    using namespace App;
    auto descriptor = makeDescriptor_();
    auto view = asMutation_(descriptor);
    descriptor->items = decltype(descriptor->items)::container();
    assert(exercise_(view).get_integer() == 9);
    assert(missing_(view).kind() == scpp::boxed_kind::null);
    try {
        viaIndexedMutation_(view, 1);
        return 1;
    } catch (const std::out_of_range& error) {
        assert(std::string_view(error.what()) == "boxed_missing_key");
    }
    assert(!descriptor->items.has(scpp::int64{1}));
    assert(descriptor->items.container_size() == 1);
    assert(descriptor->items.read(scpp::int64{0}).get_integer() == 9);
}
''')
    binary = app/'proof'
    subprocess.run([cxx, '-std=c++23', '-stdlib=libc++', '-O1', '-Wall', '-Wextra', '-Werror',
                    f'--ld-path={linker}', '-I', root/'runtime/generated/public', probe, archive, '-o', binary], check=True)
    subprocess.run([binary], check=True)


if __name__ == '__main__':
    php, cxx, linker, archive = sys.argv[1:]
    with tempfile.TemporaryDirectory(prefix='scpp-indexed-descriptor-') as tmp:
        for flavor in ('cpp', 'php', 'js'):
            for family in ('mixed', 'dynamic'):
                prove(Path(tmp), flavor, family, php, cxx, linker, Path(archive).resolve())
