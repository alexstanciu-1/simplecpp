"""Shared hash lookup and guarded previous-generation reuse on Clang."""
import argparse
import json
import re
import subprocess
import tempfile
from pathlib import Path

from app_namespace_native import artifacts, invoke, service
from classes_native import fixture
from extends_native import runtime, source_edit
from inline_adapters_native import incremental_native_build
from interfaces_native import fresh
from parent_methods_native import write_app

ROOT = Path(__file__).resolve().parents[2]


def prove(work, flavor, library, cxx_flags=()):
    s = fixture(flavor)
    v, a, fn = s['v'], s['access'], s['function']
    previous = s['klass']('Generation', s['field']('scope', '?Node') + s['field']('sources', 'vector<Node>'))
    factory = fn('newScope', 'Node', [], 'echo "F";return new Node();')
    key = fn('path', 'string', [], 'echo "K";return "unit";')
    argument = fn('argument', 'int', [], 'echo "A";return 6;')
    choose = fn('scopeFor', 'Node', [('previous', '?Generation')],
                f'if ({v}previous !== null) {{' + s['infer']('scope', f'{v}previous{a}scope')
                + f'if ({v}scope !== null) {{return {v}scope;}}}}return newScope();')
    visit = fn('visit', 'void', [('previous', '?Generation')],
               f'if ({v}previous !== null) {{foreach ({v}previous{a}sources as {v}unit) {{echo {v}unit{a}x;}}}}')
    # A method argument with observable effects lives inside the same guard as the receiver.
    method = {'cpp': 'public: void attach(int x){this->x=x;}',
              'php': 'public function attach(int $x):void{$this->x=$x;}',
              'js': 'attach(x:int):void{this.x=x;}'}[flavor]
    node = s['klass']('Node', s['field']('x', 'int', '1') + method)
    attach = fn('attachIfPresent', 'void', [('unit', '?Node')],
                f'if ({v}unit !== null) {{{v}unit{a}attach(argument());}}')
    def empty_hash(name, value_type, key_type):
        typ = f'hash<{value_type},{key_type}>'
        return {'cpp': f'{typ} {name} = [];', 'php': f'${name} {typ} = [];',
                'js': f'let {name}:{typ} = [];'}[flavor]

    decl = empty_hash('old', 'Node', 'string')
    main = decl + s['infer']('miss', f'hash_get_or_null({v}old,path())')
    main += f'if ({v}miss !== null || count({v}old) !== 0) {{return 1;}}attachIfPresent({v}miss);visit(null);'
    main += s['infer']('gen', 'new Generation()') + s['infer']('first', 'scopeFor(null)')
    main += s['infer']('second', f'scopeFor({v}gen)') + f'{v}gen{a}scope={v}first;'
    main += s['infer']('reused', f'scopeFor({v}gen)') + f'if ({v}reused !== {v}first || {v}second === {v}first) {{return 2;}}'
    main += f'{v}old["unit"]=new Node();' + s['infer']('held', f'hash_get_or_null({v}old,path())')
    main += s['infer']('watch', f'weak({v}held)') + f'{v}old{a}remove("unit");attachIfPresent({v}held);'
    main += f'{v}gen{a}sources[]={v}held;{v}gen{a}sources[]={v}first;visit({v}gen);{v}gen{a}sources{a}clear();'
    main += f'{v}held=null;if (lock({v}watch) !== null) {{return 3;}}return 8;'

    indexed = empty_hash('indexed', 'Node', 'int')
    main = indexed + f'{v}indexed[42]=new Node();if (hash_get_or_null({v}indexed,42) !== {v}indexed[42]) {{return 4;}}' + main
    # Native insertion can create a present-empty slot even though source insertion checks required owners.
    bag = s['klass']('Bag', s['field']('items', 'hash<Node,string>'))
    query = fn('lookup', '?Node', [('bag', 'Bag'), ('key', 'string')],
               f'return hash_get_or_null({v}bag{a}items,{v}key);')
    app, out = work / flavor, work / (flavor + '-out')
    write_app(app, flavor, dict(node=node, generation=previous, factory=factory, choose=choose, bag=bag, query=query,
                                actions=key + argument + visit + attach, main=main))
    resident = service(app, out)
    path = app / ('choose.' + flavor)
    edited = choose.replace('return newScope();', 'echo "E";return newScope();')
    try:
        for revision, replacement in enumerate([choose, edited, None, choose, choose]):
            before = artifacts(out)
            if replacement is None:
                path.unlink()
            else:
                source_edit(path, replacement)
            got = invoke(resident)
            clean_out = work / f'{flavor}-fresh-{revision}'
            clean = fresh(app, clean_out)
            if replacement is None:
                assert 'Unknown' in got.get('error', '') and got['error'] == clean.get('error'), got
                assert artifacts(out) == before
                continue
            assert 'error' not in got and artifacts(out) == artifacts(clean_out), got
            units = {Path(p).stem: u for p, u in json.loads((out/'manifest.json').read_text())['sources'].items()}
            if revision == 0:
                headers = '\n'.join(p.read_text() for p in out.rglob('*.hpp'))
                body = re.search(r'::get_or_null\([^{}]+\) const \{(.*?)\n\}', headers, re.S)
                assert body and body[1].count('storage_->find(key)') == 1 and '.has(' not in body[1]
                assert (out/units['main']).read_text().count('.get_or_null(') == 3
                probe = work / (flavor + '-empty.cpp')
                probe.write_text('#include "' + str(out/units['query']) + '"\n'
                                 'int main(){auto bag=App::owner_Bag::make();scpp::string key{"x",1};'
                                 'bag->items.set(key,App::owner_Node{});'
                                 'return !bag->items.has(key) || bool(App::lookup_(bag,key));}\n')
                executable = work / (flavor + '-empty')
                subprocess.run(['clang++', '-std=c++23', *cxx_flags, '-I', ROOT/'runtime/generated/public',
                                probe, library, '-o', executable], check=True)
                subprocess.run([executable], check=True)
            expected = b'KEFEFKA61' if revision == 1 else b'KFFKA61'
            changed = incremental_native_build(out, work/(flavor+'-native'), 'clang++', library, expected, cxx_flags=cxx_flags)
            if revision == 1:
                assert changed == {units['choose']} and got['preparation']['prepared'] == 1 and len(got['emitted_sources']) == 1, (changed, got)
            if revision == 4:
                assert not changed and got['preparation']['prepared'] == 0 and not got['emitted_sources'], got
    finally:
        resident.stdin.close()
        resident.wait()

    for label, source, diagnostic in [
        ('strict-key', decl + f'hash_get_or_null({v}old,42);', 'requires string'),
        ('scalar', empty_hash('old', 'int', 'string') + f'hash_get_or_null({v}old,"x");', 'shared class values'),
        ('struct', empty_hash('old', 'Point', 'string') + f'hash_get_or_null({v}old,"x");', 'shared class values'),
    ]:
        case = work / (flavor + '-' + label)
        write_app(case, flavor, dict(main=source+'return 8;', node=node, point=s['record']('Point', '')))
        result = fresh(case, work/(flavor+'-'+label+'-out'))
        assert diagnostic in result.get('error', ''), result


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--quick", action="store_true")
    quick = parser.parse_args().quick
    with tempfile.TemporaryDirectory(prefix='scpp-shared-lookup-') as directory:
        work = Path(directory)
        library = ROOT/'build/clang/libscpp_runtime.a' if quick else runtime(work, 'clang++', [ROOT/'runtime/generated/src/hash.cpp'])
        for flavor in (('php',) if quick else ('cpp', 'php', 'js')):
            prove(work, flavor, library, ("-stdlib=libc++",) if quick else ())
            print(flavor, 'shared lookup, guarded fallback and recovery passed', flush=True)
