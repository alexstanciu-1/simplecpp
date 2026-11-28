"""Retained keyed source contracts and native object reuse versus fresh builds."""
from source_suffix import source_suffix
import argparse
import json
import tempfile
from pathlib import Path
from app_namespace_native import artifacts, service, invoke
from classes_native import fixture
from extends_native import runtime, source_edit, build
from inline_adapters_native import incremental_native_build
from populated_vectors_native import declaration

ROOT = Path(__file__).resolve().parents[2]


def prove(work, quick_runtime=None):
    work.mkdir(parents=True, exist_ok=True)
    libraries = {'clang++': quick_runtime} if quick_runtime else {cc: runtime(work, cc, [ROOT / 'runtime/generated/src/hash.cpp']) for cc in ('clang++', 'g++')}
    for flavor in (('php',) if quick_runtime else ('cpp', 'php', 'js')):
        s = fixture(flavor)
        function = lambda typ, name, body: s['function'](name, typ, [], body)
        api = function('int', 'key', 'return 1;') + function('int', 'value', 'return 7;')
        main = declaration(flavor, 'h', 'hash<int,int>', '[key() => value()]') + f'echo {s["v"]}h[key()];return 8;'
        changed_key = main.replace('key() =>', '2 =>').replace('h[key()]', 'h[2]')
        extra_entry = changed_key.replace('value()]', 'value(), 3 => 9]').replace('h[2]', 'h[3]')
        string_keys = declaration(flavor, 'h', 'hash<int,string>', '["key" => value()]') + f'echo {s["v"]}h["key"];return 8;'
        # Keep cross-unit declaration demand constant while changing only the keyed initializer.
        stable_calls = 'key(); value();'
        main, changed_key, extra_entry, string_keys = [
            stable_calls + body for body in (main, changed_key, extra_entry, string_keys)
        ]
        retired = stable_calls + 'return 8;'
        app, out = work/flavor, work/(flavor+'-out')
        app.mkdir()
        source_edit(app/'module.json', json.dumps({'entry':'main.'+source_suffix(flavor)}))
        for name, text in [('api',api), ('main',main), ('idle',function('int', 'idle', 'return 42;'))]:
            source_edit(app/(name+'.'+source_suffix(flavor)), text)
        edits = [(None,None,b'7'), (None,None,b'7'), ('api',api.replace('return 7;', 'return 8;'),b'8'),
                 ('api',function('string','key','return "bad";')+function('int','value','return 7;'),None),
                 ('api',None,None), (None,None,None), ('api',api,b'7'),
                 ('main',changed_key,b'7'), ('main',extra_entry,b'9'), ('main',string_keys,b'7'),
                 ('main',retired,b''), ('main',main,b'7'), (None,None,b'7')]
        if quick_runtime:
            edits = [*edits[:4], ('api', api, b'7'), ('main', retired, b''), ('main', main, b'7')]
        resident = service(app,out)
        rows = []
        try:
            for revision,(name,text,expected) in enumerate(edits):
                before = artifacts(out)
                if name:
                    path = app/(name+'.'+source_suffix(flavor))
                    if text is None: path.unlink()
                    else: source_edit(path,text)
                current = invoke(resident)
                fresh_out = work/f'{flavor}-fresh-{revision}'
                fresh = service(app,fresh_out)
                try: comparison = invoke(fresh)
                finally: fresh.stdin.close(); fresh.wait()
                if expected is None:
                    assert current.get('error') and current['error'] == comparison.get('error')
                    assert artifacts(out) == before
                    continue
                assert 'error' not in current and 'error' not in comparison, (current,comparison)
                assert artifacts(out) == artifacts(fresh_out)
                if text == retired: assert b'from_owned_entries' not in b''.join(artifacts(out).values())
                manifest = json.loads((out/'manifest.json').read_text())
                for cc,library in libraries.items():
                    changed = incremental_native_build(out,work/(flavor+'-'+cc),cc,library,expected, cxx_flags=('-stdlib=libc++',) if quick_runtime else ())
                    if name is None and revision > 0: assert not changed and current['preparation']['prepared'] == 0
                    if name == 'main':
                        assert changed == {manifest['sources'][manifest['entry']]}, (cc, revision, changed)
                    if revision == 2:
                        assert changed and all('api.' in source for source,unit in manifest['sources'].items() if unit in changed), changed
                        assert len(changed) == 1, changed
                    if not quick_runtime and (revision in (0,6) or name == 'main'): build(fresh_out,work/(flavor+'-fresh-'+cc),cc,library,expected)
                rows.append({'revision':revision, 'php_ms':current['php_ms'], 'prepared':current['preparation']['prepared']})
        finally: resident.stdin.close(); resident.wait()
        (work/(flavor+'-results.json')).write_text(json.dumps(rows,indent=2))
        print(flavor,'PASS source recovery, factory retirement and Clang/GCC native object reuse',flush=True)


if __name__ == '__main__':
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--out',type=Path,required=True)
    parser.add_argument('--quick-runtime', type=Path)
    args=parser.parse_args()
    args.out.mkdir(parents=True,exist_ok=True)
    with tempfile.TemporaryDirectory(prefix='proof-',dir=args.out.resolve()) as directory:
        prove(Path(directory), args.quick_runtime)
