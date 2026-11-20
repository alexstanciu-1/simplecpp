"""Checked staged writes and weak symbol entries using the existing source types."""
import argparse
from pathlib import Path
import shutil
import tempfile

from app_namespace_native import artifacts, invoke, service
from application_vectors_native import measure
from classes_native import fixture
from exceptions_native import fresh_build
from extends_native import ROOT, source_edit


EXAMPLE = ROOT / 'compiler/examples/staged_ownership'


def prove(work, flavor):
    app, output = work / 'app', work / 'output'
    app.mkdir(parents=True)
    (app / 'module.json').write_text('{}')
    for name in ('declarations', 'proof'):
        shutil.copyfile(EXAMPLE / f'{name}.php.scpp', app / f'{name}.php')
    main = app / f'main.{flavor}'
    entry = 'prove_staged_ownership(); return 8;'
    source_edit(main, entry)
    resident = service(app, output)
    try:
        initial = invoke(resident)
        assert 'error' not in initial, initial
        measure(output, work / 'native', b'staged ownership')
        baseline = artifacts(output), (output / 'manifest.json').read_bytes()
        noop = invoke(resident)
        assert noop['preparation']['prepared'] == 0 and not noop['emitted_sources'], noop

        forms = fixture(flavor)
        v, access, infer = forms['v'], forms['access'], forms['infer']
        storage = {
            'parent': ('Syntax_Node', '', 'weak(new Syntax_Node())'),
            'body': ('Function_Declaration', '4, false', 'new Function_Body(4, 8)'),
            'end': ('Function_Declaration', '4, false', '8'),
            'head': ('Symbol_Entry', '', 'weak(new Function_Declaration(4, false))'),
        }
        for member, (type_name, args, value) in storage.items():
            source_edit(main, infer('target', f'new {type_name}({args})')
                        + f'{v}target{access}{member} = {value}; return 8;')
            failure = invoke(resident)
            assert f'Cannot access private field {type_name}::{member}' in failure.get('error', ''), failure
            assert failure['error'] == fresh_build(app, work / f'fresh-{member}')['error']
            assert baseline == (artifacts(output), (output / 'manifest.json').read_bytes())

        source_edit(main, entry)
        recovered = invoke(resident)
        assert 'error' not in recovered, recovered
        assert recovered['preparation']['prepared'] == 1, recovered
        fresh = work / 'fresh'
        assert 'error' not in fresh_build(app, fresh)
        assert artifacts(output) == artifacts(fresh) == baseline[0]
        measure(output, work / 'native-recovered', b'staged ownership')
        print(f'{flavor}: staged writes, weak index lifetime, and incremental recovery passed', flush=True)
    finally:
        resident.stdin.close()
        assert resident.wait() == 0


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-staged-ownership-') as temporary:
        work = (args.output or Path(temporary)).resolve()
        work.mkdir(exist_ok=True)
        for flavor in ('php', 'cpp', 'js'):
            prove(work / flavor, flavor)


if __name__ == '__main__':
    main()
