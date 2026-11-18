"""Sanitize emitted producer storage/cleanup against a separately instrumented runtime archive."""
import argparse
import json
from pathlib import Path
import subprocess

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('fixtures', type=Path)
parser.add_argument('--runtime', type=Path, required=True)
parser.add_argument('--output', type=Path, required=True)
parser.add_argument('--cases', nargs='+', default=('string', 'capture', 'checked_cleanup', 'abandon_started', 'abandon_unstarted', 'local_string', 'local_owner', 'local_failure', 'branch_string', 'branch_owner', 'branch_failure', 'loop_for', 'loop_owner', 'loop_failure', 'loop_header_owner'))
args = parser.parse_args()
root = Path(__file__).resolve().parents[2]
args.output.mkdir(parents=True, exist_ok=True)
fixtures = json.loads(args.fixtures.read_text())
results = {}
for flavor in ('cpp', 'php', 'js'):
    for case in args.cases:
        name = flavor + '_' + case
        fixture = fixtures[name]
        source = args.output / (name + '.cpp')
        source.write_text(fixture['cpp'])
        binary = args.output / name
        subprocess.run(['clang++', '-std=c++23', '-O1', '-g0', '-fsanitize=address,undefined',
                        '-fno-omit-frame-pointer', '-I', str(root / 'runtime/generated/public'),
                        str(source), str(args.runtime.resolve()), '-o', str(binary)], check=True)
        r = subprocess.run([str(binary.resolve())], capture_output=True)
        assert r.returncode == 8 and r.stdout.hex() == fixture['stdout_hex'] and not r.stderr, (name, r)
        results[name] = {'exit': r.returncode, 'stdout_hex': r.stdout.hex()}
(args.output / 'results.json').write_text(json.dumps(results, indent=2))
print(f'{len(results)} yield lifetime/safety cases passed')
