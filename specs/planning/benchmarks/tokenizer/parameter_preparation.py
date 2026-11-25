"""Measure string parameter transfer separately from vector publication on prepared scanner bodies."""
import argparse
import json
import os
from pathlib import Path
import platform
import subprocess
import tempfile

HERE = Path(__file__).resolve().parent


def sample(worker, kind, iterations):
    worker.stdin.write(f'{kind} {iterations}\n')
    worker.stdin.flush()
    return float(worker.stdout.readline())


def run(compiler, php, rounds):
    original = compiler / '04_analyze/prepare/transfer.php'
    text = original.read_text()
    start = text.index('\t\tforeach ($body->parent->parameters as $parameter)')
    end = text.index('\t\t$count = count($body->statements);', start)
    assert text[start:end].count('self::parameter_assignments($body);') == 1
    results = {'host': platform.node(), 'cpu_affinity': sorted(os.sched_getaffinity(0)), 'cases': {}}
    with tempfile.TemporaryDirectory() as scratch:
        tmp = Path(scratch)
        (tmp / 'transfer.php').write_text(text[:start] + text[end:])
        # Load the same compiler and generated bindings, replacing just the parameter phase in the control.
        boot = (compiler / 'boot.php').read_text()
        target = "__DIR__ . '/04_analyze/prepare/transfer.php'"
        assert boot.count(target) == 1
        (tmp / 'boot.php').write_text(boot.replace(target, repr(str(tmp / 'transfer.php')))
                                     .replace('__DIR__', repr(str(compiler))))
        for flavor in ('cpp', 'php', 'js'):
            for case in ('move', 'miss', 'live'):
                workers = {}
                rows = []
                try:
                    hashes = []
                    for mode, boot in [('control', tmp / 'boot.php'), ('enabled', compiler / 'boot.php')]:
                        p = subprocess.Popen([php, '-d', 'opcache.enable_cli=0', HERE / 'parameter_preparation.php',
                            boot, HERE, flavor, case, mode], stdin=subprocess.PIPE, stdout=subprocess.PIPE,
                            text=True, env={**os.environ, 'XDEBUG_MODE': 'off'})
                        workers[mode] = p
                        info = json.loads(p.stdout.readline())
                        results['php'] = info['php']
                        hashes.append(info['control_sha256'])
                        sample(p, 'body', 50)
                    assert hashes[0] == hashes[1], (flavor, case, hashes)
                    sample(workers['enabled'], 'proof', 200)
                    for batch in range(rounds):
                        row = {}
                        for mode in (('control', 'enabled') if batch % 2 == 0 else ('enabled', 'control')):
                            row[mode] = sample(workers[mode], 'body', 200)
                        row['proof'] = sample(workers['enabled'], 'proof', 2000)
                        rows.append(row)
                finally:
                    for worker in workers.values():
                        worker.stdin.close()
                        worker.wait()
                results['cases'][f'{flavor}/{case}'] = rows
    return results


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('compiler', type=Path)
    parser.add_argument('--php', default='php8.5')
    parser.add_argument('--rounds', type=int, default=12)
    args = parser.parse_args()
    print(json.dumps(run(args.compiler.resolve(), args.php, args.rounds), indent=2))
