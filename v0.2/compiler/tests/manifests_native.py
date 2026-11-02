"""Manifest discovery -> mixed-flavor frontend -> exact native inputs -> executable."""
import json
import os
from pathlib import Path
import subprocess
import tempfile

root = Path(__file__).resolve().parents[2]
php = os.environ.get('PHP_EXECUTABLE', 'php')


def run(*args):
    return subprocess.run(list(map(str, args)), capture_output=True, text=True,
                          env={**os.environ, 'XDEBUG_MODE': 'off'})


def success(result):
    assert result.returncode == 0, result.stdout + result.stderr
    return result


with tempfile.TemporaryDirectory(prefix='scpp-manifest-native-') as temporary:
    work = Path(temporary)
    app, lib, native = [work / name for name in ('app', 'lib', 'native')]
    for directory in (app, lib, native):
        directory.mkdir()
    (app/'module.json').write_text(json.dumps({'dependencies': {
        'lib': {'path': '../lib'}, 'native': {'path': '../native'}}}))
    (lib/'module.json').write_text('{}')
    (native/'module.json').write_text('{"type":"c++","artifacts":["helper.o"]}')
    # Native syntax must never enter the Simple C++ tokenizer/parser.
    (native/'helper.cpp').write_text('#include <cstdint>\nstatic_assert(sizeof(std::int64_t) == 8);\n')
    (app/'main.cpp').write_text('helper(); return 8;')
    (app/'helper.php').write_text('function helper(): void { echo "module"; }')
    (lib/'helper.php').write_text('function helper(): void { echo "module"; }')
    success(run('clang++', '-std=c++23', '-stdlib=libc++', '-c', native/'helper.cpp', '-o', native/'helper.o'))
    output = work/'bundle'
    command = [php, root/'compiler/main.php', 'project', app,
               root/'build/clang/native-layout/facts.json', output]
    success(run(*command))
    bundle = json.loads((output/'manifest.json').read_text())
    assert len(bundle['sources']) == 2
    assert 'native_sources' not in bundle
    assert len(bundle['link_inputs']) == 2  # Manually built object plus runtime archive.
    binary = work/'program'
    includes = [root/'runtime/generated/public', root/'runtime/src/numeric', root/'runtime/src/sequence']
    success(run('clang++', '-std=c++23', '-stdlib=libc++', '-O1', '-g0', '-fuse-ld=mold',
                *[arg for path in includes for arg in ('-I', path)],
                *[output/path for path in bundle['translation_units']], *bundle['link_inputs'], '-o', binary))
    result = run(binary)
    assert result.returncode == 8 and result.stdout == 'module', result
    before = {p: p.read_bytes() for p in output.rglob('*') if p.is_file()}
    (lib/'module.json').write_text('{"dependencies":{"app":{"path":"../app"}}}')
    assert run(*command).returncode != 0
    assert all(p.read_bytes() == data for p, data in before.items())
print('Manifest CLI, implicit runtime, native inputs, output/exit and failure preservation passed')
