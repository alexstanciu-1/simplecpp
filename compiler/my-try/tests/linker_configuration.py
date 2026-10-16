#!/usr/bin/env python3
"""Shared linker selection and link-only invalidation in both native consumers."""
import argparse
import json
import os
from pathlib import Path
import shutil
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    parser.add_argument('--mold', type=Path, required=True)
    args = parser.parse_args()
    out = args.results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    mold = args.mold.resolve()
    with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
        def proof(kind):
            folder = out / kind
            folder.mkdir()
            source = folder / 'source'
            source.mkdir()
            (source / 'main.phs').write_text('return answer();')
            (source / 'answer.phs').write_text('function answer(): int { return 7; }')
            published = folder / 'published'
            build = folder / 'build'
            compiler = folder / 'clang.py'
            shutil.copyfile(APP / 'tests/fixtures/timed_clang.py', compiler)
            compiler.chmod(0o700)
            log = compiler.with_suffix('.jsonl')
            selected = folder / 'selected-linker'
            shutil.copyfile(mold, selected)
            selected.chmod(0o700)
            runner.run(kind + '-publish', ['php', APP / 'tests/fixtures/timed_publication.php', source, published],
                       cli_reason='Independent linker fixture publication')
            rows = []

            def run(name, linker, threads, expected_link, cold=False, fail=False):
                before = len(log.read_text().splitlines()) if log.exists() else 0
                if kind == 'ninja':
                    command = ['php', APP / 'tools/build_ninja.php', published, build,
                               '--compiler=' + str(compiler), '--jobs=2', '--linker=' + str(linker),
                               '--linker-threads=' + str(threads)]
                    if not cold:
                        command.append('--reuse')
                else:
                    command = ['php', APP / 'tests/fixtures/native_cache_driver.php', published, build,
                               ROOT / 'runtime/include', compiler, linker, str(threads)]
                output = runner.run(kind + '-' + name, command, expected=1 if fail else 0, timeout=300,
                                    cli_reason='Linker configuration and cache proof')
                calls = [json.loads(line) for line in log.read_text().splitlines()[before:]]
                counts = {phase: sum(c['phase'] == phase for c in calls)
                          for phase in ['object_compile', 'pch_creation', 'link']}
                assert counts == dict(object_compile=5 if cold else 0, pch_creation=1 if cold else 0,
                                      link=expected_link), (kind, name, counts)
                for call in calls:
                    if call['phase'] == 'link':
                        assert '-fuse-ld=' + str(linker) in call['arguments']
                        assert ('--threads=' + str(threads) in call['arguments']) == (threads > 0)
                status = build / ('.scpp-ninja.status' if kind == 'ninja' else '.scpp-native.status')
                if fail:
                    assert status.read_text() == 'pending\n'
                else:
                    result = json.loads(output)
                    runner.run(kind + '-' + name + '-execute', [result['executable']], expected=7)
                    assert json.loads(status.read_text())['format'].endswith('-ready-v1')
                rows.append(dict(name=name, counts=counts, failed_as_expected=fail))
                (folder / 'observations.json').write_text(json.dumps(rows, indent=2))

            run('default-gnu', '/usr/bin/ld', 0, 1, cold=True)
            run('default-warm', '/usr/bin/ld', 0, 0)
            run('mold-eight', selected, 8, 1)
            run('mold-warm', selected, 8, 0)
            run('threads-two', selected, 2, 1)
            run('threads-eight-revert', selected, 8, 1 if kind == 'ninja' else 0)
            stamp = selected.stat()
            selected.write_bytes(selected.read_bytes() + b'\0')
            os.utime(selected, ns=(stamp.st_atime_ns, stamp.st_mtime_ns))
            run('replace-same-path-preserved-mtime', selected, 8, 1)
            run('replacement-warm', selected, 8, 0)
            alternate = folder / 'alternate-linker'
            shutil.copyfile(selected, alternate)
            alternate.chmod(0o700)
            selected.unlink()
            selected.symlink_to(alternate)
            run('retarget-same-bytes', selected, 8, 1)
            selected.unlink()
            selected.write_text('#!/bin/sh\nexit 37\n')
            selected.chmod(0o700)
            run('failed-link', selected, 8, 1, fail=True)
            shutil.copyfile(mold, selected)
            run('recover', selected, 8, 1 if kind == 'ninja' else 0)
            run('gnu-revert', '/usr/bin/ld', 0, 1 if kind == 'ninja' else 0)
            run('final-warm', '/usr/bin/ld', 0, 0)
        run_tasks([Task(kind + '-linker-config', lambda k=kind: proof(k)) for kind in ['ninja', 'cache']])


if __name__ == '__main__':
    main()
