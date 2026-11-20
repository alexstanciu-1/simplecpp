"""Run the scanner lifetime fixture against default and renamed string read providers."""
import json
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile

from classes_native import fixture

PROJECT = Path(__file__).resolve().parents[2]
SCANNER = PROJECT / 'specs/planning/benchmarks/tokenizer'


def emit_scanner(work, flavor, method, driver):
    app = work / flavor
    app.mkdir()
    (app / 'module.json').write_text(json.dumps({'entry': 'main.' + flavor}))
    source = (SCANNER / ('scanner.' + flavor)).read_text()
    # Read the first byte of each token through the ordinary string binding.
    # Identifier continuation and keyword checks still read from the scoped view.
    field = {'php': '$out->source[$i]', 'cpp': 'out->source[i]', 'js': 'out.source[i]'}[flavor]
    parameter = '$text[$i]' if flavor == 'php' else 'text[i]'
    assert field in source
    (app / ('scanner.' + flavor)).write_text(source.replace(field, parameter, 1))
    (app / ('main.' + flavor)).write_text(fixture(flavor)['infer']('r', 'scan("if",0)') + 'return 8;')
    out = work / (flavor + '-out')
    run = subprocess.run(['php', driver, app, out], input='"run"\n', capture_output=True, text=True, check=True)
    assert 'error' not in json.loads(run.stdout), run.stdout
    unit = out / json.loads((out / 'manifest.json').read_text())['sources'][f'{flavor}/scanner.{flavor}']
    code = unit.read_text()
    assert f').{method}(' in code and 'scpp_byte_view.read(' in code
    assert code.count('.checked_bytes()') == 1
    header = next(p for p in unit.parent.glob('*.hpp') if 'Scan scan_(' in p.read_text())
    return unit, header


def prove(root, facts, context):
    compiler = root / 'compiler'
    shutil.copytree(PROJECT / 'compiler', compiler, ignore=shutil.ignore_patterns('tests', 'generated', '__pycache__'))
    driver = compiler / 'tests/incremental_executable.php'
    driver.parent.mkdir()
    shutil.copyfile(PROJECT / 'compiler/tests' / driver.name, driver)
    runtime = root / 'runtime'
    shutil.copytree(PROJECT / 'runtime', runtime, ignore=shutil.ignore_patterns('generated'))
    (root / 'tools').symlink_to(PROJECT / 'tools', target_is_directory=True)
    config = runtime / 'metadata/runtime.json'
    metadata = json.loads(config.read_text())
    index = metadata['string']['index_read']
    for method in (index['method'], 'checked_byte_view'):
        work = root / method
        work.mkdir()
        index['method'] = method
        config.write_text(json.dumps(metadata))
        for family in ('runtime', 'compiler'):
            subprocess.run(['php', PROJECT / 'tools/api_generator/generate.php', 'emit-' + family,
                            config, runtime / 'metadata/publication.json', facts, context, root / family / 'generated'],
                           check=True)
        scanners = [emit_scanner(work, flavor, method, driver) for flavor in ('cpp', 'php', 'js')]
        cc = 'clang++'
        flags = [cc, '-std=c++23', '-O1', '-UNDEBUG', *['-I' + str(runtime / path) for path in
                 ('generated/public', 'generated/private', 'src/numeric', 'src/sequence', 'src/hash')]]
        obj = work / (cc + '.o')
        subprocess.run([*flags, '-c', runtime / 'generated/src/runtime.cpp', '-o', obj], check=True)
        for unit, header in scanners:
            exe = work / 'lifetime'
            subprocess.run([*flags, '-I', header.parent, f'-DSCPP_SCANNER_HEADER="{header.name}"',
                            PROJECT / 'compiler/tests/scoped_byte_view.cpp', unit, obj, '-o', exe], check=True)
            subprocess.run([exe], check=True)


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-byte-view-') as temp:
        prove(Path(temp), *map(Path, sys.argv[1:]))
