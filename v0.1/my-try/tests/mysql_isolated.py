"""Private MariaDB lifecycle owned by one bounded verification command."""
import argparse
import json
import signal
import subprocess
import sys
import tempfile
import time
from pathlib import Path
APP = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(APP/'tools'))
from test_runner import CommandRunner


def interrupted(signum, frame):
    raise SystemExit(128 + signum)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    parser.add_argument('--executable', type=Path, required=True)
    parser.add_argument('--fail-after-ready', action='store_true')
    args = parser.parse_args()
    out = args.results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    for signum in (signal.SIGTERM, signal.SIGINT):
        signal.signal(signum, interrupted)
    state = {'ready': False, 'passed': False, 'stopped': False, 'removed': False}
    temporary = tempfile.TemporaryDirectory(prefix='scpp-mysql-')
    private = Path(temporary.name)
    socket = private/'db.sock'
    state['private_directory'] = str(private)
    server = None
    try:
        with CommandRunner(out/'logs', APP.parents[1]) as runner:
            runner.run('initialize', ['mariadb-install-db', '--no-defaults',
                       '--datadir='+str(private/'data'), '--auth-root-authentication-method=normal',
                       '--skip-test-db'], timeout=120)
            command = ['mariadbd', '--no-defaults', '--datadir='+str(private/'data'),
                       '--socket='+str(socket), '--pid-file='+str(private/'db.pid'),
                       '--skip-networking', '--skip-name-resolve',
                       '--log-error='+str(out/'server.log')]
            (out/'server-command.json').write_text(json.dumps(command, indent=2)+'\n')
            with (out/'server-console.log').open('wb') as log:
                # Inherit the runner's process group so a hard outer timeout also kills us.
                server = subprocess.Popen(command, stdout=log, stderr=log)
            state['pid'] = server.pid
            client = ['mariadb', '--no-defaults', '--protocol=SOCKET', '--socket='+str(socket),
                      '--user=root', '--batch', '--skip-column-names']
            deadline = time.monotonic() + 45
            while time.monotonic() < deadline:
                if server.poll() is not None:
                    raise RuntimeError('Private MariaDB exited before readiness; see server.log')
                if socket.exists():
                    probe = subprocess.run(client+['--execute=SELECT 1'], capture_output=True, timeout=5)
                    if probe.returncode == 0 and probe.stdout.strip() == b'1':
                        state['ready'] = True
                        break
                time.sleep(0.1)
            if not state['ready']:
                raise RuntimeError('Private MariaDB readiness timed out')
            if args.fail_after_ready:
                raise RuntimeError('Deliberate harness failure after readiness')
            sql = """CREATE DATABASE scpp_verification;
CREATE USER 'scpp_fixture'@'localhost' IDENTIFIED BY 'fixture-only';
GRANT SELECT ON scpp_verification.* TO 'scpp_fixture'@'localhost';
CREATE TABLE scpp_verification.sample (id INTEGER PRIMARY KEY, name VARCHAR(40));
INSERT INTO scpp_verification.sample VALUES (2,'second'),(1,'first');
SELECT @@skip_networking;
"""
            assert runner.run('seed', client, input=sql.encode()).strip() == b'1'
            actual = runner.run('real-provider', [args.executable.resolve(), socket])
            assert actual == b'connected; ordered assoc/row; repeated null exhaustion; close; rejected credentials\n'
            state['passed'] = True
    finally:
        if server is not None:
            if server.poll() is None:
                server.terminate()
                try:
                    server.wait(timeout=15)
                except subprocess.TimeoutExpired:
                    server.kill()
                    server.wait(timeout=5)
            state['server_exit'] = server.returncode
            state['stopped'] = server.poll() is not None
        else:
            state['stopped'] = True
        temporary.cleanup()
        state['removed'] = not private.exists()
        (out/'lifecycle.json').write_text(json.dumps(state, indent=2)+'\n')

if __name__ == '__main__':
    main()
