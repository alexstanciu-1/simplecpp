#!/usr/bin/env python3
"""Candidate-only native proof. Does not change the verified portability target."""
import argparse
import hashlib
import json
from pathlib import Path
import subprocess
import time

ROOT = Path(__file__).resolve().parents[2]
EXPECTED = {
    'storage_bindings': '9:1:hole\n01:9\n1:9\nalias:9\n',
    'object_hashes': '7:9\n7:11:removed\n',
    'object_casts': 'same:9:row\nabsent:other\n',
}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--target-checkout', type=Path, required=True)
    parser.add_argument('--candidate-revision', required=True,
                        help='Exact upstream revision; local overlays are fingerprinted separately.')
    parser.add_argument('--results', type=Path, required=True)
    args = parser.parse_args()
    target = args.target_checkout.resolve()
    results = args.results.resolve()
    results.mkdir(parents=True, exist_ok=False)
    source = results / 'source'
    source.mkdir()
    project = results / 'phpp'
    records = []

    def run(name, command, cwd=ROOT):
        start = time.monotonic()
        process = subprocess.run([str(x) for x in command], cwd=cwd,
                                 stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True)
        (results / (name + '.log')).write_text(process.stdout)
        records.append(dict(name=name, command=[str(x) for x in command],
                            seconds=round(time.monotonic() - start, 3), code=process.returncode))
        (results / 'commands.json').write_text(json.dumps(records, indent=2) + '\n')
        if process.returncode:
            raise RuntimeError(f'{name} failed: {results / (name + ".log")}')
        return process.stdout

    # Fingerprint implementation inputs, including uncommitted candidate overlays.
    hashes = {}
    for directory in ['generators/php', 'runtime', 'bin']:
        for path in sorted((target / directory).rglob('*')):
            if path.is_file() and path.suffix in {'.php', '.hpp', '.cpp', '.json'}:
                hashes[str(path.relative_to(target))] = hashlib.sha256(path.read_bytes()).hexdigest()
    (results / 'candidate.json').write_text(json.dumps(dict(
        revision=args.candidate_revision, checkout=str(target), files=hashes), indent=2) + '\n')

    for index, (name, expected) in enumerate(EXPECTED.items()):
        fixture = (ROOT / 'tests/portability' / (name + '.php')).read_text()
        php = fixture.split("<<<'SOURCE'\n", 1)[1].split('\nSOURCE;', 1)[0] + '\n'
        (source / 'main.php').write_text(php)
        (results / (name + '.php')).write_text(php)
        run(name + '-php', ['php', ROOT / 'tests/portability' / (name + '.php')])
        run(name + '-convert', ['php', ROOT / 'tools/php_portability/convert.php', source, project])
        if index == 0:
            run('init', ['php', target / 'bin/scpp.php', 'init', '--php-profile=strict'], project)
            config_path = project / 'prism.json'
            config = json.loads(config_path.read_text())
            config['build']['cxx'] = 'clang++-18'
            config['runtime']['modules'] = ['compiler']
            config_path.write_text(json.dumps(config, indent=2) + '\n')
        command = ['php', target / 'bin/scpp.php', 'run']
        if index == 0:
            command.append('--build-runtime')
        output = run(name + '-native', command, project)
        if not output.endswith(expected):
            raise RuntimeError(f'{name}: native trace differs from expected {expected!r}')
        print(name + ': native trace passed', flush=True)
    (results / 'summary.json').write_text(json.dumps(dict(passed=True, expected=EXPECTED), indent=2) + '\n')


if __name__ == '__main__':
    main()
