#!/usr/bin/env python3
"""Isolated native append-alias investigation; does not enable compiler features."""
import argparse
from concurrent.futures import ThreadPoolExecutor
import json
import os
from pathlib import Path
import subprocess
import tempfile

HERE = Path(__file__).resolve().parent


def run(command, env=None):
    result = subprocess.run([str(part) for part in command], text=True, capture_output=True, timeout=120, env=env)
    if result.returncode:
        raise RuntimeError(f"Command failed: {command}\n{result.stdout}\n{result.stderr}")
    return result.stdout


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--config', type=Path, default=HERE.parents[1] / 'config.json')
    args = parser.parse_args()
    config_path = args.config.resolve()
    config = json.loads(config_path.read_text())
    common = [config['clang'], '-std=' + config['standard'], '-g', '-Wall', '-Wextra', '-Werror']
    common += ['-I' + str((config_path.parent / folder).resolve()) for folder in config['include_directories']]
    if config.get('target'):
        common += ['--target=' + config['target']]
    modes = [('O0', ['-O0']), ('O1', ['-O1']),
             ('sanitized', ['-O1', '-fsanitize=address,undefined', '-fno-omit-frame-pointer', '-fno-sanitize-recover=all'])]
    with tempfile.TemporaryDirectory(prefix='scpp-append-alias-') as folder:
        def check(mode):
            name, flags = mode
            executable = Path(folder) / name
            run([*common, *flags, HERE / 'probe.cpp', '-o', executable])
            # LeakSanitizer cannot inspect processes under the sandbox's ptrace.
            # Address/UB checks remain enabled; the fixture separately counts live objects.
            env = dict(os.environ, ASAN_OPTIONS='detect_leaks=0') if name == 'sanitized' else None
            return name, run([executable], env)
        with ThreadPoolExecutor(max_workers=3) as pool:
            results = dict(pool.map(check, modes))
    print(json.dumps({'clang': run([config['clang'], '--version']).splitlines()[0],
                      'config': str(config_path), 'leak_sanitizer': 'disabled (ptrace sandbox)', 'results': results}, indent=2))


if __name__ == '__main__':
    main()
