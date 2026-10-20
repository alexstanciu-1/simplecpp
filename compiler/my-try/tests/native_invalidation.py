#!/usr/bin/env python3
"""Exact native rebuild sets and phase timings; no cache optimization policy changes."""
import argparse
import collections
import json
from pathlib import Path
import shutil
import sys
import time

APP = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    output = parser.parse_args().results.resolve()
    output.mkdir(parents=True, exist_ok=False)
    source = output / 'source'
    source.mkdir()
    published = output / 'published'
    cache = output / 'cache'
    compiler = output / 'timed_clang.py'
    shutil.copyfile(APP / 'tests/fixtures/timed_clang.py', compiler)
    compiler.chmod(0o700)
    log = compiler.with_suffix('.jsonl')
    sources = {
        'main.phs': '$box Box; $box->value = 7; $alias Box =& forward($box); return read($alias);',
        'type.phs': 'struct Box { int $value; }',
        'read.phs': 'function read(const Box &$box, int $offset = 0): int { $value int = $box->value; $items vector<int> = [0]; foreach ($items as $offset => $item) {} return $value + $offset; }',
        'forward.phs': 'function &forward(Box &$box): Box { return identity($box); }',
        'identity.phs': 'function &identity(Box &$box): Box { return $box; }',
        'unrelated.phs': 'function unrelated(): int { return 21; }',
    }
    for name, text in sources.items():
        (source / name).write_text(text)
    observations = []
    with CommandRunner(output / 'logs', APP.parents[1], jobs=12) as runner:
        def proof():
            def unit(name):
                return 'n' + name.encode().hex() + '.cpp'

            def build(name, expected_units, code, *, pch=0, linked=1, source_root=source):
                start = time.monotonic()
                frontend = json.loads(runner.run(name + '-publish', ['php', APP / 'tests/fixtures/timed_publication.php',
                    source_root, published], cli_reason='Host-only phase timing at coordinator boundaries'))
                publication_seconds = time.monotonic() - start
                previous_lines = len(log.read_text().splitlines()) if log.exists() else 0
                start = time.monotonic()
                result = json.loads(runner.run(name + '-build', ['php', APP / 'tests/fixtures/native_cache_driver.php',
                    published, cache, APP.parents[1] / 'runtime/include', compiler],
                    timeout=300, cli_reason='Native process timing and cache acceptance'))
                native_seconds = time.monotonic() - start
                actions = [json.loads(line) for line in log.read_text().splitlines()[previous_lines:]]
                for row in actions:
                    if row['phase'] in ('dependency_scan', 'pch_validation', 'pch_creation', 'object_compile'):
                        if '-Wshadow' not in row['arguments']:
                            raise RuntimeError(f'{name}: native compile path omitted -Wshadow')
                compiled = [next(arg for arg in row['arguments'] if arg.endswith('.cpp'))
                            for row in actions if row['phase'] == 'object_compile']
                actual = {Path(path).name for path in compiled}
                if actual != set(expected_units) or len(compiled) != len(expected_units):
                    raise RuntimeError(f'{name}: rebuilt {actual}, expected {set(expected_units)}')
                if (result['compiled'], result['pch_built'], result['linked']) != (len(expected_units), pch, linked):
                    raise RuntimeError(f'{name}: wrong cache action counts {result}')
                if any(row['exit_code'] != 0 for row in actions):
                    raise RuntimeError(f'{name}: failed native action')
                runner.run(name + '-execute', [result['executable']], expected=code)
                phases = collections.defaultdict(float)
                for row in actions:
                    phases[row['phase']] += row['seconds']
                observations.append(dict(name=name, frontend=frontend, publication_wall=publication_seconds,
                    native_wall=native_seconds, native_phases=dict(phases),
                    native_other=native_seconds-sum(phases.values()), compiled=compiled, result=result))
                (output / 'observations.json').write_text(json.dumps(observations, indent=2) + '\n')

            all_units = ['runtime.cpp', 'document.cpp', 'datetime.cpp', 'entry.cpp', *[unit(n) for n in ['read.phs', 'forward.phs', 'identity.phs', 'unrelated.phs']]]
            build('cold', all_units, 7, pch=1)
            if '-Wshadow' not in (output / 'logs/cold-build.stderr').read_text():
                raise RuntimeError('Successful native build lost legal-shadowing diagnostics')
            before = {p: (p.read_bytes(), p.stat().st_ino, p.stat().st_mtime_ns)
                      for p in published.rglob('*') if p.is_file() and not p.name.startswith('.scpp-')}
            build('warm', [], 7, linked=0)
            if any((p.read_bytes(), p.stat().st_ino, p.stat().st_mtime_ns) != state for p, state in before.items()):
                raise RuntimeError('Warm publication rewrote generated artifacts')
            (source / 'read.phs').write_text(sources['read.phs'].replace('$value + $offset', '$value + $offset + 1'))
            build('body', [unit('read.phs')], 8)
            (source / 'read.phs').write_text(sources['read.phs'].replace('$offset = 0', '$offset = 2'))
            build('default', [unit('read.phs'), 'entry.cpp'], 9)
            (source / 'read.phs').write_text(sources['read.phs'].replace('$offset = 0', '$offset = 2, int $extra = 0'))
            build('signature', [unit('read.phs'), 'entry.cpp'], 9)
            (source / 'type.phs').write_text('struct Box { int $value; int $extra; }')
            build('layout', [unit('read.phs'), 'entry.cpp'], 9)
            (source / 'added.phs').write_text('function added(): int { return 33; }')
            build('insert', [unit('added.phs')], 9)
            (source / 'added.phs').unlink()
            # Exact old membership may reuse its prior executable, not necessarily relink.
            build('delete', [], 9, linked=0)
            relocated = output / 'relocated'
            shutil.copytree(source, relocated)
            build('relocation', [], 9, linked=0, source_root=relocated)
            compiler.write_text(compiler.read_text() + '\n# changed compiler identity for invalidation proof\n')
            build('compiler-identity', all_units, 9, pch=1)
        run_tasks([Task('native-invalidation', proof)], jobs=12)
    (output / 'summary.json').write_text(json.dumps(dict(passed=True, cases=len(observations),
        native_compiler_rebuilt=False, phase_proxy=True), indent=2) + '\n')


if __name__ == '__main__':
    main()
