"""Build and compare native prototypes over Simple C++-generated weak owners."""
import json
import os
from pathlib import Path
import subprocess
import sys
from tempfile import TemporaryDirectory

HERE = Path(__file__).resolve().parent
ROOT = HERE.parents[3]
sys.path.insert(0, str(ROOT / 'compiler/tests'))
from app_namespace_native import invoke, service

TYPES = ('SymbolEntry', 'RuntimeBinding', 'MethodInstance', 'LocalBinding')


def build(work, interface):
    mode = 'interface' if interface else 'tagged'
    app = work / mode
    app.mkdir()
    model = (HERE / 'model.phs').read_text()
    model = model.replace('INTERFACE_DECL', 'interface Target {public function resolve():Selection;}' if interface else '')
    model = model.replace('IMPLEMENTS', 'implements Target' if interface else '').replace('OVERRIDE', 'override' if interface else '')
    for t in TYPES:
        observed = 'Target' if interface else t
        model += f'\nfunction bind{t}({t} $owner):weak<{observed}> {{'
        if interface:
            model += ' $view Target = $owner; return weak($view); }'
        else:
            model += ' return weak($owner); }'
    for t in (('Target',) if interface else TYPES):
        model += f'\nfunction read{t}(weak<{t}> $ref):Selection {{ $p = lock($ref);'
        model += ' if ($p === null) { return selection(0,0,0); } return $p->resolve(); }'
    (app / 'main.phs').write_text(model + '\nreturn 8;')
    (app / 'module.json').write_text('{"entry":"main.phs"}')
    out = work / (mode+'-out')
    with service(app, out) as proc:
        result = invoke(proc)
        proc.stdin.close()
        assert 'error' not in result, result
    unit, = [out/u for u in json.loads((out/'manifest.json').read_text())['translation_units'] if u != 'entry.cpp']
    exe = app / 'measure'
    subprocess.run(['clang++', '-std=c++23', '-stdlib=libc++', '-O2', '-g0', '-Wall', '-Wextra', '-Werror',
                    '-I', ROOT/'runtime/generated/public', '-include', unit, f'-DINTERFACE={int(interface)}',
                    HERE/'measure.cpp', ROOT/'build/clang/libscpp_runtime.a', '-o', exe], check=True)
    return exe


if __name__ == '__main__':
    with TemporaryDirectory() as directory:
        work = Path(directory)
        checksums = []
        for mode in (False, True):
            result = subprocess.check_output([build(work, mode)], text=True)
            print(result, end='', flush=True)
            checksums.append(result.split('checksum=')[-1].strip())
        assert checksums[0] == checksums[1], checksums
        reference = work / 'reference'
        reference.mkdir()
        subprocess.run(['php', HERE/'reference.php', ROOT/'compiler/boot.php', reference],
                       env={**os.environ, 'XDEBUG_MODE': 'off'}, check=True)
