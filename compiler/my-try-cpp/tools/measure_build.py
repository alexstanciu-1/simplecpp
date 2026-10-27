"""Measure compiler-only and compiler/support rebuilds with published dependencies fixed."""
import argparse
import hashlib
import json
from pathlib import Path
import statistics
import subprocess
import time

PROJECT = Path(__file__).resolve().parents[1]


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--build', type=Path, default=PROJECT / 'build/compiler')
    parser.add_argument('--output', type=Path, required=True)
    parser.add_argument('--jobs', type=int, default=8)
    parser.add_argument('--trials', type=int, default=3)
    args = parser.parse_args()
    if args.jobs < 1 or args.trials < 1:
        parser.error('jobs and trials must be positive')
    build = args.build.resolve()
    command = ['ninja', '-C', str(build), f'-j{args.jobs}', 'my-try-cpp']
    subprocess.run(command, check=True, capture_output=True)
    archives = list((PROJECT / 'dependencies').rglob('*.a'))
    def fingerprint():
        return {str(p): (p.stat().st_mtime_ns, hashlib.sha256(p.read_bytes()).hexdigest()) for p in archives}
    before = fingerprint()
    groups = {
        'src': list(build.glob('CMakeFiles/*.dir/src/**/*.o')),
        'support': list(build.glob('CMakeFiles/compiler_support.dir/support/**/*.o')),
    }
    assert len(groups['src']) == 10 and len(groups['support']) == 7, groups
    result = {'jobs': args.jobs, 'trials': args.trials, 'cases': {}}
    for label, objects in [('src', groups['src']), ('src_support', groups['src'] + groups['support'])]:
        runs = []
        for _ in range(args.trials):
            for obj in objects:
                obj.unlink()
            started = time.perf_counter()
            completed = subprocess.run(command, check=True, capture_output=True, text=True)
            runs.append(round(time.perf_counter() - started, 4))
            assert completed.stdout.count('Building CXX object') == len(objects), completed.stdout
        result['cases'][label] = {'translation_units': len(objects), 'runs_seconds': runs,
                                   'median_seconds': statistics.median(runs)}
    started = time.perf_counter()
    completed = subprocess.run(command, check=True, capture_output=True, text=True)
    result['no_op_seconds'] = round(time.perf_counter() - started, 4)
    assert 'no work to do' in completed.stdout
    assert before == fingerprint(), 'Published dependency archives changed'
    result['dependency_archives_unchanged'] = True
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps(result, indent=2) + '\n')
    print(json.dumps(result, indent=2))


if __name__ == '__main__':
    main()
