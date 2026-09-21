#!/usr/bin/env python3
"""Compare uncached compilation, empty ccache, and exact replay on eleven objects."""
import argparse
import json
import os
import re
import subprocess
import tempfile
from pathlib import Path
from callable_surface import prepare
from partitioned_callables import prepare as prepare_parts
from layout_isolation import prepare as prepare_layout
from experiment import measure, require_scratch, write_changed
from run_corpus import regen, warm, verify
from signature_case import make_case

p = argparse.ArgumentParser()
p.add_argument('--app', type=Path, required=True)
p.add_argument('--depend', action='store_true', help='Use ccache depend mode to avoid a separate preprocessing pass')
args = p.parse_args()
app = args.app.resolve()
require_scratch(app)
repo = Path(__file__).resolve().parents[2]
build = app / '.prism/build'
out = app.parent / ('cache-depend-results' if args.depend else 'cache-results')
out.mkdir(exist_ok=True)
corpus = json.loads((app.parent / 'corpus.json').read_text())
selected = set(json.loads((app.parent / 'parallelism-results/verified-work-set.json').read_text())['rebuilt_outputs']) - {'main'}
originals, edited, counts = make_case(app)
(out / 'manifest.json').write_text(json.dumps({'cache': 'ccache', 'depend_mode': args.depend, 'jobs': 16, 'selected_objects': sorted(selected), 'source_call_sites': counts, 'cache_sloppiness': ['pch_defines', 'time_macros'], 'dependency_flags': '-MMD', 'pch_setup_in_timing': False, 'cache_directories': 'fresh private directory per round'}, indent=2))
def generate():
    prepare(app)
    prepare_parts(app)
    prepare_layout(app)

def graph(launcher):
    base = (build / 'latency-layout.ninja').read_text()
    # Only the eleven affected objects change commands; the remaining 522 reuse
    # their original objects. Separate timestamp-free PCHs are setup work.
    rules = '''rule cache_project_pch
  command = clang++ $cxxflags -Xclang -fno-pch-timestamp -x c++-header $in -o $out
rule cache_runtime_pch
  command = clang++ $cxxflags -Xclang -fno-pch-timestamp -x c++-header $in -o $out
rule cache_part
  command = LAUNCH clang++ $cxxflags -Xclang -fno-pch-timestamp -include-pch cache-project.pch -MMD -MF $out.d -MT $out -c $in -o $out
  depfile = $out.d
  deps = gcc
rule cache_unit
  command = LAUNCH clang++ $cxxflags -Xclang -fno-pch-timestamp -include-pch cache-runtime.pch $more_cxxflags -MMD -MF $out.d -MT $out -c $in -o $out
  depfile = $out.d
  deps = gcc
'''.replace('LAUNCH', launcher)
    lines = []
    for line in base.splitlines():
        match = re.match(r'build (\S+): (\S+) (.*)', line)
        if match and match[1] in selected:
            rule = 'cache_part' if match[2] == 'compile_callable_part' else 'cache_unit'
            rest = match[3].replace('layout-project.pch', 'cache-project.pch').replace('layout_runtime_pch.hpp.gch', 'cache-runtime.pch')
            line = f'build cache/{match[1]}: {rule} {rest}'
        elif line.startswith('build main: link '):
            line = ' '.join('cache/' + word if word in selected else word for word in line.split())
        lines.append(line)
    return rules + '\n'.join(lines) + '''
build cache-project.pch: cache_project_pch ../layout/generated/__project_units.hpp
build cache-runtime.pch: cache_runtime_pch layout_runtime_pch.hpp
'''

def force():
    for name in selected:
        (build / 'cache' / name).unlink(missing_ok=True)
    (build / 'main').unlink(missing_ok=True)

def check(label):
    verify(app, corpus, out, label)
    assert 'signature_probe=123456789' in (out / (label + '.run.log')).read_text().splitlines()
    row = json.loads((out / 'measurements.jsonl').read_text().splitlines()[-1])
    assert {s['output'] for s in row['native_steps']} == {'cache/' + name for name in selected} | {'main'}

try:
    warm(app, 'latency-layout.ninja')
    for key, text in edited.items():
        (app / key).write_text(text)
        regen(repo, app, key)
    generate()
    write_changed(build / 'latency-cache-direct.ninja', graph(''))
    write_changed(build / 'latency-cache.ninja', graph('ccache'))
    subprocess.run(['ninja', '-f', 'latency-cache-direct.ninja', 'cache-project.pch', 'cache-runtime.pch'], cwd=build, check=True)
    for trial in range(3):
        force()
        label = f'direct-r{trial}'
        measure(app, 'latency-cache-direct.ninja', label, out, [])
        check(label)
        # A fresh private cache for each cold trial; never clear shared caches.
        os.environ['CCACHE_DIR'] = tempfile.mkdtemp(prefix='ccache-', dir=app.parent)
        os.environ['CCACHE_CONFIGPATH'] = str(Path(os.environ['CCACHE_DIR']) / 'ccache.conf')
        Path(os.environ['CCACHE_CONFIGPATH']).write_text('sloppiness = pch_defines,time_macros\nmax_size = 1G\n' + ('depend_mode = true\n' if args.depend else ''))
        for kind in ['empty-cache', 'replay', 'replay-again']:
            force()
            label = f'{kind}-r{trial}'
            measure(app, 'latency-cache.ninja', label, out, [])
            check(label)
            stats = subprocess.run(['ccache', '--show-stats', '--verbose'], text=True, capture_output=True, check=True)
            (out / (label + '.stats.log')).write_text(stats.stdout)
finally:
    for key, text in originals.items():
        (app / key).write_text(text)
        regen(repo, app, key)
    generate()
    warm(app, 'latency-layout.ninja')
    verify(app, corpus, out, 'restored')
