"""Compare optimization levels and linkers on the unedited units retained by bench.py."""
import argparse
import hashlib
import json
from pathlib import Path
import random
import shlex
import shutil
import subprocess
import tempfile
import time

from bench import HERE, verify


def elapsed(command):
    start = time.perf_counter_ns()
    subprocess.run(command, check=True, capture_output=True)
    return (time.perf_counter_ns() - start) / 1e6


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('build', type=Path, help='finished bench.py output directory')
    parser.add_argument('v02', type=Path)
    parser.add_argument('output', type=Path)
    args = parser.parse_args()
    rng = random.Random(43)
    common = ['clang++', '-std=c++23', '-stdlib=libc++', '-g0',
              '-I' + str(args.v02.resolve() / 'runtime/generated/public')]
    profiles = ['-O0', '-O1', '-O2']
    modes = {'lld': ['-fuse-ld=lld'], 'lld-1': ['-fuse-ld=lld', '-Wl,--threads=1'],
             'lld-4': ['-fuse-ld=lld', '-Wl,--threads=4']}
    mold = shutil.which('ld.mold')
    if mold:
        modes['mold-4'] = ['--ld-path=' + mold, '-Wl,--threads=4']
    report = {'rounds': 5, 'sizes': {}, 'compile': [], 'link': []}
    with tempfile.TemporaryDirectory() as temp:
        work = Path(temp)
        programs = {}
        for name in ['access-log', 'stock-ledger', 'route-table']:
            case = args.build.resolve() / '0' / name
            source = case / 'app/main.phs'
            if source.read_bytes() != (HERE / name / 'main.phs').read_bytes():
                raise ValueError('Not the baseline source: ' + name)
            manifest = json.loads((case / 'out/manifest.json').read_text())
            units = [case / 'out' / f for f in manifest['translation_units']]
            app = case / 'out' / manifest['sources']['app/main.phs']
            report['sizes'][name] = dict(source=source.stat().st_size, app_cpp=app.stat().st_size,
                                         app_lines=len(app.read_bytes().splitlines()),
                                         entry_cpp=(case / 'out/entry.cpp').stat().st_size,
                                         app_sha256=hashlib.sha256(app.read_bytes()).hexdigest())
            programs[name] = (units, manifest['link_inputs'])
        for i in range(-1, 5):
            jobs = [(name, level) for name in programs for level in profiles]
            rng.shuffle(jobs)
            for name, level in jobs:
                units, archives = programs[name]
                objects = []
                wall = 0
                phases = {'Frontend': 0, 'Backend': 0}
                for n, unit in enumerate(units):
                    obj = work / f'{name}-{level}-{n}.o'
                    cmd = [*common, level, '-c', unit, '-o', obj]
                    wall += elapsed(cmd)
                    trace = work / 'trace.json'
                    elapsed([*cmd, '-ftime-trace=' + str(trace), '-o', work / 'traced.o'])
                    totals = {e['name']: e['dur'] for e in json.loads(trace.read_text())['traceEvents']
                              if 'dur' in e}
                    for k in phases:
                        phases[k] += totals['Total ' + k] / 1000
                    objects.append(obj)
                if i >= 0:
                    report['compile'].append(dict(name=name, level=level, round=i, ms=wall, **phases))
                binary = work / name
                if i == -1:
                    elapsed([*common, '-fuse-ld=lld', *objects, *archives, '-o', binary])
                    verify(binary, HERE / name)
                    verify(binary, args.build.resolve() / '0' / name / 'large')
                if level != '-O2':
                    continue
                choices = list(modes)
                rng.shuffle(choices)
                for mode in choices:
                    command = [*common, *modes[mode], *objects, *archives, '-o', binary]
                    plan = subprocess.run([*command, '-###'], capture_output=True, text=True, check=True)
                    direct = shlex.split(plan.stderr.strip().splitlines()[-1])
                    pair = [('driver', command), ('direct', direct)]
                    rng.shuffle(pair)
                    times = {k: elapsed(cmd) for k, cmd in pair}
                    if i == -1:
                        verify(binary, HERE / name)
                    if i >= 0:
                        report['link'].append(dict(name=name, mode=mode, round=i, **times))
        sections = subprocess.check_output(['readelf', '-SW', binary], text=True)
        report['last_executable_debug_sections'] = [s.strip() for s in sections.splitlines() if '.debug_' in s]
    args.output.write_text(json.dumps(report, indent=2) + '\n')


if __name__ == '__main__':
    main()
