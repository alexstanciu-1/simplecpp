"""Exercise selected and ordinary users of the same application vector through real publication and Ninja."""
from source_suffix import source_suffix
import hashlib
import json
import re
import shutil
from pathlib import Path
import subprocess
import tempfile

from app_namespace_native import ROOT, artifacts, service
from extends_native import runtime, source_edit
from inline_adapters_native import incremental_native_build

SCANNER = ROOT / 'specs/planning/benchmarks/tokenizer'


def reduced_provider(work, app, baseline):
    """A fresh compiler publication whose application-vector recipe omits the capability."""
    root = work / 'reduced'
    tools = root / 'tools/api_generator'
    shutil.copytree(ROOT / 'tools/api_generator', tools, ignore=shutil.ignore_patterns('tests'))
    (root / 'runtime').symlink_to(ROOT / 'runtime', target_is_directory=True)
    shutil.copytree(ROOT / 'compiler', root / 'compiler', ignore=shutil.ignore_patterns('tests', 'generated'))
    recipe = tools / 'templates/collections/vector.concrete.json'
    data = json.loads(recipe.read_text())
    del data['destination_append']
    recipe.write_text(json.dumps(data))
    layout = ROOT / 'build/clang/native-layout'
    subprocess.run(['php', tools / 'generate.php', 'emit-compiler', ROOT / 'runtime/metadata/runtime.json',
                    ROOT / 'build/clang/runtime-publication.json', layout / 'facts.json', layout / 'context.json',
                    root / 'compiler/generated'], check=True, stdout=subprocess.DEVNULL)
    output = root / 'out'
    args = ['php', root / 'compiler/main.php', 'project', app, work / 'facts.json', output]
    subprocess.run(args, check=True, stdout=subprocess.DEVNULL)
    ordinary = artifacts(output)
    assert all(b'friend struct destination_' not in data for data in ordinary.values())
    assert set(ordinary) == set(baseline)
    subprocess.run([*args, '--optimize-function=scan'], check=True, stdout=subprocess.DEVNULL)
    assert artifacts(output) == ordinary


def prove(work, flavor, library, *, quick=False):
    app = work / ('app-' + flavor)
    app.mkdir()
    source_edit(app / 'module.json', json.dumps({'entry': 'main.' + source_suffix(flavor)}))
    fixture = (SCANNER / ('scanner.' + source_suffix(flavor))).read_text()
    split = fixture.index('Scan scan(' if flavor == 'cpp' else 'function scan(')
    records, body = fixture[:split], fixture[split:]
    layout = app / ('records.' + source_suffix(flavor))
    source_edit(layout, records)
    names = ['scan', 'again']
    for name in names:
        source_edit(app / (name + '.' + source_suffix(flavor)), body.replace('scan(', name + '('))
    # The entry consumes both results, one with growth and the other with spare capacity.
    main = {'cpp': 'auto a=scan("if ===",0); auto b=again("if ===",6); echo count(a->tokens),count(b->tokens);',
            'php': '$a=scan("if ===",0); $b=again("if ===",6); echo count($a->tokens),count($b->tokens);',
            'js': 'let a=scan("if ===",0); let b=again("if ===",6); echo count(a.tokens),count(b.tokens);'}
    # Check every field of the second token on both the growth and presized paths.
    for name in ('a', 'b'):
        var, access, decl = {'cpp': ('', '->', 'auto '), 'php': ('$', '->', ''), 'js': ('', '.', 'let ')}[flavor]
        local = var + 'token' + name
        main[flavor] += f'{decl}{local}={var}{name}{access}tokens[1];'
        main[flavor] += 'echo ' + ','.join(local + access + field for field in ('offset', 'length', 'kind')) + ';'
    source_edit(app / ('main.' + source_suffix(flavor)), main[flavor] + 'return 8;')
    output, fresh = work / ('out-' + flavor), work / ('fresh-' + flavor)
    resident = service(app, output)

    def run(selection, expected_users, changed=None, expected=b'2233113311'):
        resident.stdin.write(json.dumps({'optimize_functions': selection}) + '\n')
        resident.stdin.flush()
        report = json.loads(resident.stdout.readline())
        assert 'error' not in report, report
        current = artifacts(output)
        helpers = [p for p, text in current.items() if p.suffix == '.hpp' and b'static void append(' in text and b'auto* storage = target.storage_;' in text]
        assert len(helpers) == bool(expected_users), (flavor, selection, helpers, report)
        relative = lambda n: f'{app.name}/{n}.{source_suffix(flavor)}'
        unit = lambda n: 'units/' + hashlib.sha256(relative(n).encode()).hexdigest() + '.cpp'
        for name in names:
            assert (b'::append(' in current[Path(unit(name))] and b'destination_' in current[Path(unit(name))]) == (name in expected_users)
        rebuilt = incremental_native_build(output, work / ('native-' + flavor), 'clang++', library, expected, cxx_flags=('-stdlib=libc++',) if quick else ())
        if changed is not None:
            assert set(report['emitted_sources']) == {relative(n) for n in changed}, report
            assert rebuilt == {unit(n) for n in changed}, rebuilt
        args = ['php', ROOT / 'compiler/main.php', 'project', app, work / 'facts.json', fresh, '--force',
                *['--optimize-function=' + name for name in selection]]
        subprocess.run(args, check=True, stdout=subprocess.DEVNULL)
        assert current == artifacts(fresh), (flavor, selection)
        return current

    try:
        baseline = run([], [])
        selected = run(['scan'], ['scan'], ['scan'])
        assert all(selected[p] == text for p, text in baseline.items() if p.suffix == '.hpp')
        # Exercise allocation failure in the helper used by this production bundle.
        helper, = [p for p, data in selected.items() if b'auto* storage = target.storage_;' in data]
        spelling = re.search(rb'struct (destination_\w+) final', selected[helper])[1].decode()
        records_header, = [p for p, data in selected.items() if p.suffix == '.hpp' and b'struct Scan {' in data]
        adapter = work / 'adapter.hpp'
        adapter.write_text(f'#include "{output / records_header}"\n#include "{output / helper}"\n'
                           f'using Result=App::scpp_records::Scan;\n'
                           f'#define APPEND(items, ...) App::{spelling}::append(items, __VA_ARGS__)\n')
        binary = work / 'failure'
        subprocess.run(['clang++', *(['-stdlib=libc++'] if quick else []), '-std=c++23', '-I', ROOT / 'runtime/generated/public', '-I', work,
                        SCANNER / 'destination/failure.cpp', library, '-o', binary], check=True)
        subprocess.run([binary], check=True)
        run(['scan'], ['scan'], [])
        if quick:
            assert run([], [], ['scan']) == baseline
            return
        run(names, names, ['again'])
        run(['again'], ['again'], ['scan'])
        assert run([], [], ['again']) == baseline
        assert run(['scan'], ['scan'], ['scan']) == selected
        path = app / ('scan.' + source_suffix(flavor))
        spelling = {'cpp': 'item->offset=start;', 'js': 'item.offset=start;', 'php': '$item->offset=$start;'}[flavor]
        assert spelling in body
        source_edit(path, body.replace(spelling, ''))
        run(['scan'], [], ['scan'], expected=b'2203113311')
        source_edit(path, body)
        assert run(['scan'], ['scan'], ['scan']) == selected
        # Project cache keys include the selection even with all source stats unchanged.
        args = ['php', ROOT / 'compiler/main.php', 'project', app, work / 'facts.json', fresh]
        for selection, rebuilt, expected in [([], True, baseline), ([], False, baseline),
                                             (['--optimize-function=scan'], True, selected)]:
            assert json.loads(subprocess.check_output([*args, *selection]))['rebuilt'] == rebuilt
            assert artifacts(fresh) == expected
        if flavor == 'cpp':
            reduced_provider(work, app, baseline)
        extra = {'cpp': 'int spare;', 'php': 'public int $spare;', 'js': 'spare:int;'}[flavor]
        source_edit(layout, records.replace('struct Token {', 'struct Token {' + extra))
        run(['scan'], [])
        source_edit(layout, records)
        assert run(['scan'], ['scan']) == selected
    finally:
        resident.stdin.close()
        resident.wait()


if __name__ == '__main__':
    with tempfile.TemporaryDirectory() as directory:
        work = Path(directory)
        (work / 'facts.json').write_text('{"schema":1,"native_integers":{"int":{"signed":true,"digits":31}}}')
        import argparse
        parser = argparse.ArgumentParser(description=__doc__)
        parser.add_argument('--quick-runtime', type=Path)
        args = parser.parse_args()
        if args.quick_runtime:
            prove(work, 'php', args.quick_runtime, quick=True)
        else:
            library = runtime(work, 'clang++')
            for flavor in ('cpp', 'php', 'js'):
                prove(work, flavor, library)
