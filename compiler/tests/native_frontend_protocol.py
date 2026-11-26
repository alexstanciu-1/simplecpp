"""Cross the actual PHP CLI and native bootstrap service in both directions."""
from concurrent.futures import ThreadPoolExecutor
import json
import os
from pathlib import Path
import socket
import signal
import subprocess
import sys
import tempfile
import time

ROOT = Path(__file__).resolve().parents[2]


def raw_errors(app):
    messages = [b'{"command":"missing"}\n', b'{}\n', b'{"command":7}\n', b'{bad}\n', b'{"command":']
    errors = []
    directory = os.open(app, os.O_DIRECTORY)
    try:
        for msg in messages:
            with socket.socket(socket.AF_UNIX) as peer:
                peer.settimeout(5)
                peer.connect(f'/proc/self/fd/{directory}/.scpp/compiler.sock')
                peer.sendall(msg)
                peer.shutdown(socket.SHUT_WR)
                with peer.makefile('rb') as stream:
                    errors.append(json.loads(stream.readline())['error'])
    finally:
        os.close(directory)
    return errors


def exercise(work, executable):
    # Quotes, spaces and a long absolute socket name exercise JSON and cwd-relative addressing.
    app = work/('service "project" ' * 7)/'app'
    app.mkdir(parents=True)
    (app/'module.json').write_text('{}')
    definition = app/'definition.php'
    definition.write_text('function answer(): int { return 1; }')
    main = app/'main.php'
    main.write_text('return answer();')
    native = [executable, 'frontend', app]
    php = ['php', ROOT/'compiler/main.php', 'frontend', app]
    def invoke(client, command=None, *, error=False):
        result = subprocess.run(client + ([command] if command else []), text=True, capture_output=True, timeout=10)
        if error:
            assert result.returncode == 1 and result.stderr and not result.stdout, result
            return result.stderr
        assert result.returncode == 0, result
        return json.loads(result.stdout)

    def stopped():
        for _ in range(200):
            if not (app/'.scpp/compiler.sock').exists():
                return
            time.sleep(.005)
        raise AssertionError('Socket still present after stop')

    def comparable(reply):
        report = reply.copy()
        assert report.pop('pid') > 0 and report.pop('frontend_ms') >= 0
        assert report['application'] == str(app) and report['idle_seconds'] == 3600
        return report

    assert invoke(native, 'status') == invoke(native, 'stop') == {'running': False}
    assert not (app/'.scpp').exists()
    try:
        # Start the real PHP service with its own CLI. The native CLI must join it.
        reference = invoke(php)
        pid = reference['pid']
        assert invoke(native, 'status') == invoke(php, 'status')
        wire_errors = raw_errors(app)
        warm_php = invoke(native)
        assert warm_php['pid'] == pid and warm_php['generation'] == 2
        main.write_text('// syntax error follows\nreturn answer()')
        php_error = invoke(php, error=True)
        assert invoke(native, error=True) == php_error
        assert invoke(native, 'status')['generation'] == 2
        assert invoke(native, 'stop')['running'] is False
        stopped()
        main.write_text('return answer();')

        # Only the requesters are launched by the harness. They must find one detached native owner.
        with ThreadPoolExecutor(4) as pool:
            starts = sorted(pool.map(lambda _: invoke(native), range(4)), key=lambda r: r['generation'])
        assert [r['generation'] for r in starts] == [1, 2, 3, 4]
        pid = starts[0]['pid']
        assert all(r['pid'] == pid for r in starts)
        assert Path(f'/proc/{pid}/exe').resolve() == executable
        assert comparable(starts[0]) == comparable(reference), (starts[0], reference)
        assert comparable(starts[1]) == comparable(warm_php)
        assert invoke(php, 'status') == invoke(native, 'status')
        assert raw_errors(app) == wire_errors
        definition.write_text('function answer(): int { return 20; }')
        edit = invoke(php)
        assert edit['pid'] == pid and edit['generation'] == 5
        assert edit['counts'] == {'added': 0, 'changed': 1, 'removed': 0, 'reused': 1}
        main.write_text('// syntax error follows\nreturn answer()')
        assert invoke(php, error=True) == php_error
        assert invoke(php, 'status')['generation'] == 5
        main.write_text('return answer();')
        assert invoke(php)['generation'] == 6
        os.kill(pid, signal.SIGKILL)
        # Wait for process death so the next request starts with an actually stale endpoint.
        for _ in range(200):
            if not Path(f'/proc/{pid}/exe').exists():
                break
            time.sleep(.005)
        again = invoke(native)
        assert again['pid'] != pid and comparable(again) == comparable(reference)
        assert invoke(php, 'stop') == {**invoke_status(again), 'running': False}
        stopped()
        assert invoke(native, 'status') == {'running': False}
    finally:
        invoke(native, 'stop')


def invoke_status(report):
    return {k: report[k] for k in ('pid', 'application', 'idle_seconds', 'generation')}


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-wire-') as tmp:
        work = Path(tmp)
        exercise(work, Path(sys.argv[1]).resolve(strict=True))
