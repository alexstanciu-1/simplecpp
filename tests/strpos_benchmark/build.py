"""Build all six actual emitted callers and keep the pre-link and post-ThinLTO LLVM IR."""
import argparse
import json
import os
import re
from pathlib import Path
import shutil
import subprocess

HERE = Path(__file__).resolve().parent
ROOT = HERE.parents[1]


def run(args, **kwargs):
    return subprocess.run(args, check=True, **kwargs)


def build(work, runtime, emitted):
    work.mkdir(parents=True, exist_ok=True)
    if emitted is None:
        emitted = work / 'emitted'
        app = work / 'app'
        shutil.copytree(HERE / 'app', app, dirs_exist_ok=True, ignore=shutil.ignore_patterns('module.sources.json'))
        response = run(['php', ROOT / 'compiler/tests/incremental_executable.php', app, emitted],
                       input='"run"\n', capture_output=True, text=True, env={**os.environ, 'XDEBUG_MODE': 'off'})
        result = json.loads(response.stdout)
        if 'error' in result:
            raise RuntimeError(result['error'])
    manifest = json.loads((emitted / 'manifest.json').read_text())
    units = {Path(key).suffix[1:]: emitted / file for key, file in manifest['sources'].items()
             if Path(key).stem == 'search'}
    assert set(units) == {'php', 'js', 'cpp'}
    compiler = 'clang++'
    flags = ['-std=c++23', '-stdlib=libc++', '-O2', '-g0', '-ffunction-sections', '-fdata-sections',
             '-I' + str(runtime / 'public')]
    driver = work / 'driver.o'
    run([compiler, *flags, '-c', HERE / 'driver.cpp', '-o', driver])
    for mode in ['ordinary', 'thin']:
        out = work / mode
        out.mkdir(exist_ok=True)
        options = ['-flto=thin'] if mode == 'thin' else []
        link = ['-fuse-ld=lld', '-static-libstdc++', '-Wl,--gc-sections']
        if mode == 'thin':
            link += ['-Wl,--save-temps', '-Wl,--thinlto-cache-dir=' + str(out / 'cache')]
        run(['python3', '-B', ROOT / 'tools/layout_probe/qualification.py', '--cxx', compiler,
             '--cache', work / 'qualification', *['--flag=' + arg for arg in flags + options],
             '--link-flag=-fuse-ld=lld', '--link-flag=-static-libstdc++'])
        for flavor, unit in units.items():
            run([compiler, *flags, *options, '-c', unit, '-o', out / (flavor + '.o')])
            if mode == 'ordinary':
                run([compiler, *flags, '-S', '-emit-llvm', unit, '-o', out / (flavor + '.ll')])
        run([compiler, *flags, *options, '-I' + str(ROOT / 'runtime/src/sequence'),
             '-I' + str(ROOT / 'runtime/src/numeric'), '-c', runtime / 'src/runtime.cpp', '-o', out / 'runtime.o'])
        run([compiler, '-stdlib=libc++', '-O2', *options, *link, driver, 'php.o', 'js.o', 'cpp.o',
             'runtime.o', '-o', 'bench'], cwd=out)
        if mode == 'thin':
            version = run([compiler, '-dumpversion'], capture_output=True, text=True).stdout.split('.')[0]
            dis = shutil.which('llvm-dis-' + version) or 'llvm-dis'
            for flavor in units:
                run([dis, out / (flavor + '.o.4.opt.bc'), '-o', out / (flavor + '.ll')])
        calls = {}
        for flavor in units:
            ir = (out / (flavor + '.ll')).read_text()
            for fn, body in re.findall(r'^define [^\n]*@(_ZN3App\w+)\([^\n]*\{\n(.*?)^}', ir, re.M | re.S):
                names = re.findall(r'\b(?:call|invoke)\b[^\n]*?@([^ (]+)\(', body)
                callees = run(['c++filt', *names], capture_output=True, text=True).stdout.splitlines() if names else []
                if any(name.startswith(('scpp::php::', 'scpp::js::', 'scpp::cpp::')) for name in callees):
                    raise RuntimeError(f'Unexpected flavor wrapper call in {fn}: {callees}')
                calls[fn] = callees
        if len(calls) != 6:
            raise RuntimeError(f'Expected six application functions, found {list(calls)}')
        (out / 'calls.json').write_text(json.dumps(calls, indent=2) + '\n')
        run([out / 'bench'])


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('work', type=Path)
    parser.add_argument('--runtime', type=Path, default=ROOT / 'runtime/generated')
    parser.add_argument('--emitted', type=Path)
    args = parser.parse_args()
    build(args.work.resolve(), args.runtime.resolve(), args.emitted and args.emitted.resolve())
