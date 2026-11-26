"""Portable native proof for flat lists; clean runtime objects, both compiler families."""
import argparse
import json
import os
from pathlib import Path
import subprocess
import time
from entry_probe import raw_entry

ROOT = Path(__file__).resolve().parents[2]


def run(command, **kwargs):
    result = subprocess.run(list(map(str, command)), capture_output=True, text=True,
                            env={**os.environ, 'XDEBUG_MODE': 'off'}, **kwargs)
    assert result.returncode == 0, (command, result.stdout, result.stderr)
    return result.stdout


def includes(runtime):
    return [arg for path in [runtime / 'generated/public', runtime / 'public', runtime / 'generated/private',
                            runtime / 'src/numeric', runtime / 'src/sequence', runtime / 'src/hash', runtime / 'src/ownership']
            for arg in ('-I', str(path))]


def prove(out, cxx, runtime, fixtures, extra_sources=()):
    out.mkdir(parents=True, exist_ok=True)
    flags = ['-std=c++23', '-O2', '-g0', '-Wall', '-Wextra', '-Werror', *includes(runtime)]
    objects = []
    for name, source in [('runtime', runtime / 'generated/src/runtime.cpp'),
                         ('output', runtime / 'src/output/output.cpp'),
                         ('arithmetic', runtime / 'src/numeric/arithmetic.cpp'), *extra_sources]:
        obj = out / (name + '.o')
        run([cxx, *flags, '-c', source, '-o', obj])
        objects.append(obj)
    observations = {}
    for name, fixture in fixtures.items():
        work = out / name
        work.mkdir(exist_ok=True)
        files = fixture.get('files', {'main.cpp': fixture.get('cpp', '')})
        units = []
        for path, code in files.items():
            if fixture.get('catch_out_of_range'):
                code = '#include <stdexcept>\n' + raw_entry(code)
                code += '\nint main() { try { tested_entry(); } catch (const std::out_of_range&) { return 8; } return 91; }\n'
            file = work / path
            file.parent.mkdir(parents=True, exist_ok=True)
            file.write_text(code)
            if file.suffix == '.cpp':
                units.append(file)
        start = time.perf_counter()
        run([cxx, *flags, '-I', work, *units, *objects, '-o', work / 'test'])
        result = subprocess.run([work / 'test'], capture_output=True)
        assert result.returncode == 8 and result.stdout.hex() == fixture['stdout_hex'], (name, result.returncode, result.stdout, result.stderr)
        observations[name] = {'native_wall_ms': (time.perf_counter() - start) * 1000}
    (out / 'results.json').write_text(json.dumps(observations, indent=2))
    print(cxx, 'PASS', len(observations), 'initialization native fixtures')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--out', type=Path, required=True)
    parser.add_argument('--cxx', default='clang++')
    parser.add_argument('--runtime', type=Path, default=ROOT / 'runtime')
    parser.add_argument('--fixtures', type=Path)
    args = parser.parse_args()
    args.out.mkdir(parents=True, exist_ok=True)
    fixtures = args.fixtures or args.out / 'fixtures.json'
    if args.fixtures is None:
        run(['php', ROOT / 'compiler/tests/list_initialization.php', fixtures])
    prove(args.out, args.cxx, args.runtime, json.loads(fixtures.read_text()))
