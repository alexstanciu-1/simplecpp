#!/usr/bin/env python3
"""Regenerate real source body edits; compare native build layouts and check execution."""
import argparse
import hashlib
import json
import re
import subprocess
import time
from pathlib import Path
from experiment import prepare, measure

HERE = Path(__file__).resolve().parent


def regen(repo, app, source):
    subprocess.run(['php', str(HERE / 'regenerate.php'), str(repo), str(app), source], check=True,
                   stdout=subprocess.DEVNULL)


def verify(app, corpus, out, label):
    result = subprocess.run([str(app / '.prism/build/main')], cwd=app, text=True,
                            stdout=subprocess.PIPE, stderr=subprocess.STDOUT, timeout=30)
    (out / (label + '.run.log')).write_text(result.stdout)
    expected = ['structure_smoke=ok'] + [f'latency_{row["id"]}={row["expected"]}' for row in corpus]
    if result.returncode or any(s not in result.stdout.splitlines() for s in expected):
        raise RuntimeError('Correctness failure: ' + label + '\n' + result.stdout)


def warm(app, config):
    result = subprocess.run(['ninja', '-f', config, '-j12'], cwd=app / '.prism/build',
                            stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True)
    if result.returncode:
        raise RuntimeError(result.stdout)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--app', type=Path, required=True)
    parser.add_argument('--repo', type=Path, required=True)
    parser.add_argument('--rounds', type=int, default=5)
    parser.add_argument('--cases', type=int, default=20)
    parser.add_argument('--out', type=Path, required=True)
    args = parser.parse_args()
    app, repo, out = args.app.resolve(), args.repo.resolve(), args.out.resolve()
    out.mkdir(parents=True, exist_ok=True)
    corpus = json.loads((app.parent / 'corpus.json').read_text())
    sources = [r['source'] for r in corpus[:12]]
    originals = {}
    for row in corpus:
        path = app / row['source']
        text = path.read_text()
        if hashlib.sha256(text.encode()).hexdigest() != row['source_sha256'] or text.count(row['original']) != 1:
            raise RuntimeError('Stale metadata for ' + row['source'])
        originals[row['source']] = text
    for round_id in range(args.rounds):
        for row in corpus[:args.cases]:
            source = row['source']
            path = app / source
            original = originals[source]
            literal = row['expression']
            # A local computation and branch replace an existing return. Same value,
            # actual changed body, fresh spelling per trial, no header changes.
            # Keep line count initially fixed; insertion/fan-out is measured separately.
            value = re.search(r'\d+(?=\)?$)', literal)
            if value is None or row['type'] not in ['int','uint16']:
                raise RuntimeError('This frozen corpus expects integer getters')
            delta = round_id + 7
            rewritten = f'($latency_operand + {delta}) - {delta}'
            expr = literal[:value.start()] + rewritten + literal[value.end():]
            needle = f'return {literal};'
            patch = f'$latency_operand int = {value[0]}; if ($latency_operand < 0) {{ return {literal}; }} return {expr};'
            changed_method = row['original'].replace(needle, patch)
            changed = original.replace(row['original'], changed_method)
            # Establish both original layouts before each independent edit trial.
            path.write_text(original)
            regen(repo, app, source)
            prepare(app, sources, 180)
            warm(app, 'latency-baseline.ninja')
            warm(app, 'latency-experiment.ninja')
            configs = ['latency-baseline.ninja', 'latency-experiment.ninja']
            if round_id % 2:
                configs.reverse()
            try:
                path.write_text(changed)
                regen(repo, app, source)
                prepare(app, sources, 180)
                for config in configs:
                    label = f'{row["id"]}-r{round_id}-' + ('baseline' if 'baseline' in config else 'experiment')
                    # Shared unsplit objects must miss in BOTH layouts. Split outputs
                    # retain their own paths and were regenerated independently.
                    (app / '.prism/generated' / Path(source).with_suffix('.cpp')).touch()
                    measure(app, config, label, out, [])
                    verify(app, corpus, out, label)
            finally:
                path.write_text(original)
                regen(repo, app, source)
                prepare(app, sources, 180)
    warm(app, 'latency-baseline.ninja')
    warm(app, 'latency-experiment.ninja')
    verify(app, corpus, out, 'final-restored')


if __name__ == '__main__':
    main()
