"""Named callable signatures, pointer lowering, incremental reuse and recovery."""
import argparse
import hashlib
import json
from pathlib import Path
import tempfile

from app_namespace_native import artifacts, invoke, service
from classes_native import fixture
from extends_native import source_edit
from incremental_executable_native import run, snapshots, write_build


def prove(work, flavor):
    app, output, native = (work / name for name in ('app', 'output', 'native'))
    app.mkdir(parents=True)
    native.mkdir()
    (app / 'module.json').write_text('{}')
    forms = fixture(flavor)
    fn, v = forms['function'], forms['v']

    def source(name):
        return app / f'{name}.{flavor}'

    signature = 'callback<int,int,int>'
    compute = fn('compute', 'int', [('a', 'int'), ('b', 'int')], f'return {v}a * 10 + {v}b;')
    static_method = {
        'cpp': 'class Arithmetic { public: static int twice(int a) { return a * 2; } };',
        'php': 'class Arithmetic { public static function twice(int $a): int { return $a * 2; } }',
        'js': 'class Arithmetic { public static twice(a: int): int { return a * 2; } }',
    }[flavor]
    target = 'Arithmetic.twice' if flavor == 'js' else 'Arithmetic::twice'
    arguments = [('op', signature)]
    source_edit(source('compute'), compute)
    source_edit(source('arithmetic'), static_method)
    source_edit(source('consumer'),
        fn('left', 'int', [], 'echo "L"; return 2;')
        + fn('right', 'int', [], 'echo "R"; return 3;')
        + fn('apply', 'int', arguments, f'return {v}op(left(), right());')
        + fn('relay', 'int', arguments, f'return apply({v}op);')
        + fn('unary', 'int', [('op', 'callback<int,int>')], f'return {v}op(4);')
        + fn('notify', 'void', [('op', 'callback<void>')], f'{v}op();'))
    box = forms['klass']('Box', forms['field']('rank', 'int', '7'))
    source_edit(source('box'), box)
    source_edit(source('other'), forms['klass']('Other', forms['field']('rank', 'int', '7')))
    compare = 'use Box as Payload;' + fn('compare', 'int', [('a', 'Payload'), ('b', 'Payload')],
                 f'return {v}a{forms["access"]}rank + {v}b{forms["access"]}rank;')
    source_edit(source('compare'), compare)
    source_edit(source('compare_use'),
        fn('compareBoxes', 'int', [('op', 'callback<int,Box,Box>')], f'return {v}op(new Box(), new Box());'))
    source_edit(source('quiet'), fn('quiet', 'void', [], 'return;'))
    main = fn('mark', 'void', [], 'echo "!";') + f'''
        echo relay(compute(...)), ":", unary({target}(...)), ":", compareBoxes(compare(...));
        notify(mark(...));
        return 8;
    '''
    source_edit(source('main'), main)
    resident = service(app, output)
    manifest_path = output / 'manifest.json'
    timings = []

    def step(expected, emitted=None, rebuilt=None):
        objects_before = snapshots(native, 'unit-*.o')
        executable = native / 'program'
        executable_before = executable.stat().st_mtime_ns if executable.exists() else None
        sources_before = artifacts(output)
        result = invoke(resident)
        assert 'error' not in result, (flavor, result)
        if emitted is not None:
            assert sorted(result['emitted_sources']) == sorted(f'app/{name}.{flavor}' for name in emitted), result
            assert result['preparation']['prepared'] == len(emitted), result
        manifest = json.loads(manifest_path.read_text())
        units = [output / name for name in manifest['translation_units']]
        command = write_build(native, units)
        command[2] = '4'
        graph = native / 'build.ninja'
        graph.write_text(graph.read_text().replace(' -MD ', ' -ftime-trace -MD '))
        built = run(command, native)
        assert built.returncode == 0, built.stdout + built.stderr
        changed = {name for name, stamp in snapshots(native, 'unit-*.o').items()
                   if objects_before.get(name) != stamp}
        if rebuilt is not None:
            assert len(changed) == rebuilt, (flavor, result, changed, built.stdout)
            sources_after = artifacts(output)
            edited = [path for path, data in sources_after.items() if path.suffix == '.cpp' and sources_before.get(path) != data]
            assert changed == {'unit-' + hashlib.sha256(str((output / path).resolve()).encode()).hexdigest() + '.o'
                               for path in edited}, (changed, edited)
            if rebuilt == 0:
                assert executable.stat().st_mtime_ns == executable_before, built.stdout
        executed = run([native / 'program'])
        assert (executed.returncode, executed.stdout, executed.stderr) == (8, expected, ''), executed
        frontend_ms = 0.0
        for obj in changed:
            trace = native / Path(obj).with_suffix('.json')
            frontend_ms += sum(event['dur'] for event in json.loads(trace.read_text())['traceEvents']
                               if event['name'] == 'Total Frontend') / 1000
        timings.append({'prepared': result['preparation']['prepared'], 'rebuilt': len(changed),
                        'php_compile_ms': round(result['php_ms'], 2), 'clang_frontend_ms': round(frontend_ms, 2)})

    def fresh(label):
        folder = work / label
        process = service(app, folder)
        try:
            return invoke(process), folder
        finally:
            process.stdin.close()
            process.wait()

    def rejection(message, label):
        before = artifacts(output), manifest_path.read_bytes()
        failed = invoke(resident)
        assert message in failed.get('error', '') and f'main.{flavor}:' in failed['error'], failed
        assert invoke(resident)['error'] == failed['error'], failed
        control, _ = fresh(label)
        assert control['error'] == failed['error'], (control, failed)
        assert before == (artifacts(output), manifest_path.read_bytes())

    try:
        step('LR23:8:14!')
        generated = b'\n'.join(artifacts(output).values())
        assert b'(*var_op_0)' in generated and b'&compute_' in generated and b'&method_' in generated
        assert b'std::function' not in generated and b'#include <functional>' not in generated
        step('LR23:8:14!', emitted=[], rebuilt=0)
        source_edit(source('compute'), compute.replace(' * 10', ' * 11'))
        step('LR25:8:14!', emitted=['compute'], rebuilt=1)
        source_edit(source('arithmetic'), static_method.replace(' * 2', ' * 3'))
        step('LR25:12:14!', emitted=['arithmetic'], rebuilt=1)
        source_edit(source('compare'), compare.replace('rank +', 'rank -'))
        step('LR25:12:0!', emitted=['compare'], rebuilt=1)
        source_edit(source('compare'), compare)
        step('LR25:12:14!', emitted=['compare'], rebuilt=1)
        source_edit(source('box'), box.replace('7', '8'))
        step('LR25:12:16!')
        assert timings[-1]['prepared'] > 1, timings[-1]
        clean, fresh_output = fresh('nominal-recovered')
        assert 'error' not in clean and artifacts(output) == artifacts(fresh_output), clean
        source_edit(source('box'), box)
        step('LR25:12:14!')
        source_edit(source('compare'), compare.replace('use Box', 'use Other'))
        rejection('Callback target signature mismatch', 'nominal-alias')
        source_edit(source('compare'), compare)
        step('LR25:12:14!')
        source_edit(source('compute'), fn('compute', 'string', [('a', 'int'), ('b', 'int')], 'return "wrong";'))
        rejection('Callback target signature mismatch', 'signature')
        source('compute').unlink()
        rejection('Callback requires a source free function or static method', 'missing')
        source_edit(source('compute'), compute)
        step('LR23:12:14!')
        clean, fresh_output = fresh('recovered')
        assert 'error' not in clean and artifacts(output) == artifacts(fresh_output), clean
        step('LR23:12:14!', emitted=[], rebuilt=0)

        source_edit(source('arithmetic'), static_method.replace('static ', ''))
        rejection('Callback requires a source free function or static method', 'instance-method')
        source_edit(source('arithmetic'), static_method.replace(' * 2', ' * 3'))
        source_edit(source('compute'), compute.replace(f'{v}b)', f'{v}b=3)') if flavor != 'js'
                    else compute.replace('b:int)', 'b:int=3)'))
        rejection('Callback target signature mismatch', 'defaulted-target')
        source_edit(source('compute'), compute)
        source_edit(source('main'), main.replace('notify(mark(...))', 'notify(compute(...))'))
        rejection('Callback target signature mismatch', 'wrong-arity')
        source_edit(source('main'), main.replace('relay(compute(...))', 'relay(2)'))
        rejection('Expected exact callback signature', 'numeric-argument')
        print(flavor, json.dumps(timings), flush=True)
    finally:
        resident.stdin.close()
        resident.wait()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-named-callbacks-') as temporary:
        work = (args.output or Path(temporary)).resolve()
        work.mkdir(parents=True, exist_ok=True)
        for flavor in ('php', 'cpp', 'js'):
            prove(work / flavor, flavor)


if __name__ == '__main__':
    main()
