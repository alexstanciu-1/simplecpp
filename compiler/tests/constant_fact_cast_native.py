"""Replay PHP-prepared bool casts through the translated evaluator and integer local settlement."""
import argparse
import json
import os
from pathlib import Path
import statistics
import subprocess
import tempfile

from app_namespace_native import ROOT, invoke, service

FIXTURE = Path(__file__).with_name('constant_fact')
SOURCES = ROOT / 'compiler/examples/constant_evaluation'
NAMES = ('literal', 'parameter')


def oracle(app, profile=False):
    flags = ['-d', 'zend_extension=xdebug'] if profile else []
    cmd = ['php', *flags, FIXTURE / 'cast_oracle.php', app]
    if profile:
        cmd.append('--calls')
    return json.loads(subprocess.check_output(cmd, env={**os.environ, 'XDEBUG_MODE': 'develop' if profile else 'off'}))


def translated_unit(work, reference):
    app = work / 'translated'
    app.mkdir()
    codes = f"const int CONVERSION_IDENTITY={reference['identity']};\nconst int CONVERSION_BOOLEAN_INTEGER={reference['boolean_integer']};\n"
    source = codes + '\n'.join((SOURCES / name).read_text() for name in ('constant_fact.phs', 'integer_conversion.phs'))
    (app / 'main.phs').write_text(source + '\nreturn 8;')
    (app / 'module.json').write_text('{"entry":"main.phs"}')
    out = work / 'out'
    with service(app, out) as proc:
        response = invoke(proc)
        proc.stdin.close()
        assert 'error' not in response, response
    unit, = [out / u for u in json.loads((out / 'manifest.json').read_text())['translation_units'] if u != 'entry.cpp']
    driver = work / 'probe.cpp'
    driver.write_text(f'#include "{unit}"\n#include "{FIXTURE / "cast_driver.cpp"}"\n')
    return driver


def measure(work, profile):
    app = work / 'inputs'
    app.mkdir()
    reference = oracle(app)
    phases = reference['phases']
    if profile:
        monitored = oracle(app, profile=True)
        assert monitored['phases'] == phases
        assert monitored['calls'] == {'fresh': 4, 'edit': 2, 'noop': 0}, monitored
        print('PHP evaluator calls', monitored['calls'])
    driver = translated_unit(work, reference)
    records = []
    for phase, count, dirty in [('fresh', 3, ['literal', 'parameter']), ('edit', 1, ['literal']), ('noop', 0, [])]:
        row = phases[phase]
        assert row['work'] == {'prepared': count, 'reused': 3-count} and row['dirty'] == dirty, row
        assert row['literal']['local'] == [True, int(phase != 'fresh')] and row['parameter']['local'] == [False, None], row
        records.append(str(len(dirty)))
        for name in dirty:
            site = row[name]
            known, value = site['input']
            records.append(' '.join(map(str, (NAMES.index(name), int(known), int(bool(value)), *site['ops']))))
    for counted in (True, False):
        binary = work / ('counted' if counted else 'timed')
        flags = ['-DCOUNT_EVALUATIONS', '-finstrument-functions'] if counted else []
        subprocess.run(['clang++', '-O2', '-std=c++23', '-stdlib=libc++', '-Wall', '-Wextra', '-Werror', *flags,
                        '-I', ROOT / 'runtime/generated/public', driver, ROOT / 'build/clang/libscpp_runtime.a',
                        '-o', binary], check=True)
        rows = subprocess.check_output([binary], input='\n'.join(records)+'\n', text=True).splitlines()
        for (phase, row), output in zip(phases.items(), rows, strict=True):
            metric, *facts = output.split()
            for i, name in enumerate(NAMES):
                for j, field in enumerate(('cast', 'local')):
                    known, value = map(int, facts[4*i+2*j:4*i+2*j+2])
                    assert [bool(known), value if known else None] == row[name][field], (phase, name, field, facts)
            if counted:
                assert int(metric) == 2 * len(row['dirty']), (phase, metric)
                print(phase, 'native evaluator calls', metric)
            elif row['dirty']:
                print(phase, 'translated cast/local settlement ns', metric)
    for name, samples in reference['samples_ns'].items():
        print(name, 'PHP preparation median ms', round(statistics.median(samples)/1e6, 4))


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--profile-php', action='store_true', help='count evaluator calls using the Xdebug extension in a separate run')
    with tempfile.TemporaryDirectory() as tmp:
        measure(Path(tmp), parser.parse_args().profile_php)
