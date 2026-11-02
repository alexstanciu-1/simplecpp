"""Separate-process frontend service proof using a disposable my-try-simple copy."""
import concurrent.futures
import json
import os
from pathlib import Path
import shutil
import signal
import subprocess
import tempfile
import time

compiler = Path(__file__).resolve().parents[1]
sample = compiler.parent / 'samples/my-try-simple'
php = os.environ.get('PHP_EXECUTABLE') or shutil.which('php')
env = dict(os.environ, XDEBUG_MODE='off')
records = []

def invoke(app, action=None, success=True):
    args = [php, str(compiler / 'main.php'), 'frontend', str(app)]
    if action:
        args.append(action)
    result = subprocess.run(args, env=env, capture_output=True, text=True, timeout=15)
    if not success:
        assert result.returncode == 1 and not result.stdout, result
        return result.stderr
    assert result.returncode == 0, result.stderr
    return json.loads(result.stdout)

with tempfile.TemporaryDirectory(prefix='scpp-service-test-') as temporary:
    app = Path(temporary) / 'app'
    app.mkdir()
    manifest = json.loads((sample / 'module.json').read_text())
    for name in manifest['sources']:
        target = app / name
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(sample / name, target)
    (app / 'module.json').write_text(json.dumps(manifest))
    try:
        assert invoke(app, 'status') == {'running': False}
        # Concurrent initial clients must find one detached owner and serialized generations.
        with concurrent.futures.ThreadPoolExecutor(max_workers=4) as pool:
            initial = list(pool.map(lambda _: invoke(app), range(4)))
        assert len({r['pid'] for r in initial}) == 1
        assert sorted(r['generation'] for r in initial) == [1, 2, 3, 4]
        first = min(initial, key=lambda r: r['generation'])
        assert first['mode'] == 'full' and first['counts'] == {'added': 257, 'changed': 0, 'removed': 0, 'reused': 0}
        assert first['idle_seconds'] == 3600
        records.append(first)
        pid = first['pid']
        for r in initial:
            if r['generation'] != 1:
                assert r['counts']['reused'] == 257
        path = next(p for p in manifest['sources'] if p != 'main.phs')
        original = (app / path).read_text()
        (app / path).write_text(original.replace('= 1;', '= 999;'))
        edited = invoke(app)
        assert edited['pid'] == pid and edited['generation'] == 5
        assert edited['counts'] == {'added': 0, 'changed': 1, 'removed': 0, 'reused': 256}
        records.append(edited)
        unchanged = invoke(app)
        assert unchanged['pid'] == pid and unchanged['counts']['reused'] == 257
        records.append(unchanged)
        (app / path).write_text('this is invalid syntax')
        assert invoke(app, success=False)
        assert invoke(app, 'status')['generation'] == 6
        (app / path).write_text(original)
        retry = invoke(app)
        assert retry['pid'] == pid and retry['generation'] == 7 and retry['counts']['changed'] == 1
        # Separate application, separate process and publication.
        other = Path(temporary) / 'other'
        other.mkdir()
        (other / 'main.php').write_text('return 8;')
        (other / 'module.json').write_text('{}')
        try:
            independent = invoke(other)
            assert independent['pid'] != pid and independent['counts']['added'] == 1
        finally:
            invoke(other, 'stop')
        assert invoke(app, 'stop')['running'] is False
        for _ in range(100):
            if not (app / '.scpp/compiler.sock').exists(): break
            time.sleep(.02)
        assert not (app / '.scpp/compiler.sock').exists()
        restarted = invoke(app)
        assert restarted['pid'] != pid and restarted['generation'] == 1 and restarted['mode'] == 'full'
        # Crash leaves a stale socket; a later invocation recovers it under the lifetime lock.
        os.kill(restarted['pid'], signal.SIGKILL)
        time.sleep(.1)
        recovered = invoke(app)
        assert recovered['pid'] != restarted['pid'] and recovered['mode'] == 'full'
        invoke(app, 'stop')
        for _ in range(100):
            if not (app / '.scpp/compiler.sock').exists(): break
            time.sleep(.02)
        # Exercise the same idle loop with a one-second deadline, not a test-specific exit path.
        code = 'require $argv[1]; require $argv[2]; \\scpp\\compiler\\Frontend_Service::serve($argv[3], 1);'
        idle = subprocess.Popen([php, '-r', code, str(compiler / 'boot.php'),
                                 str(compiler / 'host/frontend_service.php'), str(app)], env=env,
                                stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
        try:
            for _ in range(100):
                if (app / '.scpp/compiler.sock').exists(): break
                time.sleep(.01)
            assert invoke(app, 'status')['idle_seconds'] == 1
            time.sleep(.65)
            assert invoke(app, 'status')['running'] is True  # refreshes inactivity
            time.sleep(.65)
            assert idle.poll() is None
            stdout, stderr = idle.communicate(timeout=3)
            assert idle.returncode == 0, stderr
            assert not (app / '.scpp/compiler.sock').exists()
            assert invoke(app, 'status') == {'running': False}
        finally:
            if idle.poll() is None:
                idle.kill()
                idle.communicate()
    finally:
        invoke(app, 'stop')
print(json.dumps({'checks': 'startup race, edit, reuse, failure retry, app isolation, stop/restart, stale socket, idle expiry passed',
                  'invocations': records}, indent=2))
