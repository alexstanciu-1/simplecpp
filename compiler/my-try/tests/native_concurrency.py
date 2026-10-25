#!/usr/bin/env python3
"""Real Clang overlap, bounded slots, stable link results, warm reuse and failure/retry."""
import argparse
import json
from pathlib import Path
import sys
import time

APP = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks

p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
out = p.parse_args().results.resolve()
out.mkdir(parents=True, exist_ok=False)
source = out / 'source'
source.mkdir()
(source / 'main.phs').write_text('return first() + second() + third();')
for name, value in [('first', 3), ('second', 4), ('third', 5)]:
    (source / (name + '.phs')).write_text(f'function {name}(): int {{ return {value}; }}')
proxy = out / 'clang.py'
proxy.write_text((APP / 'tests/fixtures/timed_clang.py').read_text())
proxy.chmod(0o700)
published = out / 'published'
observations = []
with CommandRunner(out / 'logs', APP.parents[1], jobs=12) as runner:
    def proof():
        runner.run('publish', ['php', APP / 'tests/fixtures/timed_publication.php', source, published])
        for jobs in [1, 4]:
            cache = out / ('cache-' + str(jobs))
            command = ['php', APP / 'tests/fixtures/native_cache_driver.php', published, cache,
                       APP.parents[1] / 'runtime/include', proxy, '--no-pch', '0', str(jobs)]
            before = len(proxy.with_suffix('.jsonl').read_text().splitlines()) if proxy.with_suffix('.jsonl').exists() else 0
            start = time.monotonic()
            result = json.loads(runner.run('cold-' + str(jobs), command, timeout=300,
                                          cli_reason='Managed concurrent Clang processes'))
            wall = time.monotonic() - start
            runner.run('execute-' + str(jobs), [result['executable']], expected=12)
            rows = [json.loads(line) for line in proxy.with_suffix('.jsonl').read_text().splitlines()[before:]]
            intervals = [r for r in rows if r['phase'] in ['dependency_scan', 'object_compile']]
            events = sorted([(r['started'], 1) for r in intervals] + [(r['ended'], -1) for r in intervals])
            active = maximum = 0
            for _, delta in events:
                active += delta
                maximum = max(maximum, active)
            if maximum > jobs or (jobs > 1 and maximum < 2):
                raise RuntimeError(f'Clang overlap mismatch: {maximum} for {jobs} jobs')
            warm = json.loads(runner.run('warm-' + str(jobs), command, timeout=300,
                                        cli_reason='Native cache revalidation'))
            if warm['compiled'] or warm['linked'] or warm['pch_built']:
                raise RuntimeError('Parallel scheduling changed warm cache identity')
            observations.append(dict(jobs=jobs, seconds=wall, maximum_overlap=maximum, result=result, warm=warm))

        # Add a validly published C++ compile failure without changing its ownership protocol.
        # The source-language pass still succeeds; this fixture deliberately edits the owned
        # artifact and its manifest digest together to exercise the native consumer boundary.
        manifest_path = published / '.scpp-output.manifest'
        original_manifest = manifest_path.read_bytes()
        manifest = json.loads(original_manifest)
        target = next(row for row in manifest['files'] if row['path'] == 'entry.cpp')
        artifact = published / target['path']
        original = artifact.read_bytes()
        import hashlib
        artifact.write_bytes(b'#error native_concurrency_failure\n' + original)
        target['sha256'] = hashlib.sha256(artifact.read_bytes()).hexdigest()
        changed = json.dumps(manifest, separators=(',', ':')).encode() + b'\n'
        manifest_path.write_bytes(changed)
        status = published / '.scpp-output.status'
        status.write_bytes(b'scpp-output-v1:ready:' + hashlib.sha256(changed).hexdigest().encode() + b'\n')
        failed_cache = out / 'failure-cache'
        command = ['php', APP / 'tests/fixtures/native_cache_driver.php', published, failed_cache,
                   APP.parents[1] / 'runtime/include', proxy, '--no-pch', '0', '4']
        runner.run('failure', command, expected=1, timeout=300, cli_reason='Concurrent failure and cleanup')
        if (failed_cache / '.scpp-native.status').read_text() != 'pending\n':
            raise RuntimeError('Failed parallel build became ready')
        if list(failed_cache.glob('.scpp-stage-*')):
            raise RuntimeError('Failed parallel build left staged outputs')
        deadline = time.monotonic() + 1
        while True:
            running = False
            for line in proxy.with_suffix('.started.jsonl').read_text().splitlines():
                row = json.loads(line)
                for key in ['proxy_pid', 'compiler_pid']:
                    status_file = Path('/proc') / str(row[key]) / 'stat'
                    try:
                        state = status_file.read_text().split(') ', 1)[1].split()[0]
                        running |= state != 'Z'
                    except FileNotFoundError:
                        pass
            if not running:
                break
            if time.monotonic() >= deadline:
                raise RuntimeError('Failed build left a child process running')
            time.sleep(.01)
        artifact.write_bytes(original)
        manifest_path.write_bytes(original_manifest)
        status.write_bytes(b'scpp-output-v1:ready:' + hashlib.sha256(original_manifest).hexdigest().encode() + b'\n')
        repaired = json.loads(runner.run('retry', command, timeout=300, cli_reason='Retry after concurrent failure'))
        runner.run('retry-execute', [repaired['executable']], expected=12)
        (out / 'summary.json').write_text(json.dumps(dict(passed=True, observations=observations,
            failure_retry=True, native_compiler_rebuilt=False), indent=2) + '\n')
    run_tasks([Task('native-concurrency', proof)], jobs=12)
