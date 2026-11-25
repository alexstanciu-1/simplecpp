"""Compile emitted collection calls against canonical-only bindings on two LP64 targets."""
import json
from pathlib import Path
import subprocess
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[3]
METADATA = ROOT / 'runtime/metadata/runtime.json'
GENERATOR = ROOT / 'tools/api_generator/generate.php'

EMIT = r'''
use scpp\compiler\{Cpp_Emitter, Source_Frontend, entry_target, flavor, source_file};
require $argv[1];
$file = new source_file('collections', $argv[3], flavor::from($argv[2]));
echo (new Cpp_Emitter())->emit((new Source_Frontend())->prepare($file, new entry_target(31)));
'''


def program(flavor):
    declarations = {
        'cpp': 'vector<int> v = []; hash<int,int> h = []; int i = 0; int j = 1; mixed m = []; dynamic d = [];',
        'php': '$v vector<int> = []; $h hash<int,int> = []; $i int = 0; $j int = 1; $m mixed = []; $d dynamic = [];',
        'js': 'let v: vector<int> = []; let h: hash<int,int> = []; let i: int = 0; let j: int = 1; let m: mixed = []; let d: dynamic = [];',
    }
    prefix = '$' if flavor == 'php' else ''
    v, h, i, j = (prefix + name for name in ('v', 'h', 'i', 'j'))
    boxed = ''.join(f'{prefix}{box}[{i}] = 1; {prefix}{box}[{j}] = {i} + 1;' for box in ('m', 'd'))
    return (declarations[flavor] + boxed + f'{v}[] = 1; {v}[] = {j} <=> {i};'
            f'{v}[] = (int)4; {v}[] = (int)true; {h}[{i} + 1] = {v}[{j}]; {h}[0] = {v}[{i}] + 1; return {h}[0];')


def proof(work, native_facts, context):
    publication = json.loads((ROOT / 'runtime/metadata/publication.json').read_text())
    for vector in publication['vectors']['specializations']:
        vector.update(native_index=False, native_value='none')
    selection = work / 'publication.json'
    selection.write_text(json.dumps(publication))

    boot = work / 'boot.php'
    bootstrap = (ROOT / 'compiler/boot.php').read_text()
    bootstrap = bootstrap.replace("__DIR__ . '/generated/", repr(str(work / 'compiler') + '/') + " . '")
    boot.write_text(bootstrap.replace('__DIR__', repr(str(ROOT / 'compiler'))))

    for native, target, suffix in [('long', 'x86_64-unknown-linux-gnu', 'L'),
                                   ('long long', 'x86_64-apple-darwin', 'LL')]:
        facts = json.loads(native_facts.read_text())
        for kind, name in [('int64', native), ('uint64', 'unsigned ' + native)]:
            facts['fixed_integers'][kind]['native_name'] = name
        layout = work / 'facts.json'
        layout.write_text(json.dumps(facts))
        for product in ('runtime', 'compiler'):
            subprocess.run(['php', GENERATOR, 'emit-' + product, METADATA, selection, layout,
                            context, work / product], check=True)
        for flavor in ('cpp', 'php', 'js'):
            cpp = subprocess.check_output(['php', '-r', EMIT, boot, flavor, program(flavor)], text=True)
            for call in (f'.append(1{suffix})', f'.at(0{suffix})', '.at(var_j_3)',
                         '.append((static_cast<scpp::int64>(true)))'):
                assert call in cpp, (target, flavor, call, cpp)
            arithmetic = '(var_i_2 + 1LL)'
            if native == 'long':
                arithmetic = f'static_cast<scpp::int64>({arithmetic})'
            assert f'.set({arithmetic}, ' in cpp, (target, flavor, cpp)
            for family in ('mixed', 'dynamic'):
                for argument in ('1' + suffix, arithmetic):
                    assert f'scpp::{family}::from_integer({argument})' in cpp, (target, flavor, cpp)
            # Also check the modeled native identity against Clang's actual target type.
            unit = cpp + f'\nstatic_assert(__is_same(scpp::int64, {native}));\n'
            subprocess.run(['clang++', '-target', target, '-ffreestanding', '-std=c++23',
                            '-Wall', '-Wextra', '-Werror', '-I', work / 'runtime/public',
                            '-x', 'c++', '-fsyntax-only', '-'], input=unit, text=True, check=True)


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-collection-targets-') as directory:
        proof(Path(directory), Path(sys.argv[1]), Path(sys.argv[2]))
