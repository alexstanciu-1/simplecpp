"""Exercise a compiled retained frontend over a real, app-local Linux Unix socket."""
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path
import signal
import socket
import stat
import struct
import subprocess
import sys
import tempfile
from threading import Barrier
import time

from translated_retained_frontend import frontend_project
from translated_vector_recipe import ROOT, fresh


def build_service(work, cxx, linker):
    tool = frontend_project(work, socket_service=True)
    transport = ROOT/'compiler/examples/retained_frontend/socket'
    declarations = [
        ('host_directory()', 'void'), ('host_listen()', 'void'), ('host_request()', 'string'),
        ('host_reply(string $response)', 'void'), ('host_close()', 'void'),
    ]
    (tool/'host.php').write_text('\n'.join(
        f'function {signature}: {result} {{ throw Exception::make("Native adapter required"); }}'
        for signature, result in declarations))
    bundle = work/'bundle'
    manifest = fresh(tool, bundle)
    host, = [unit for source, unit in manifest['sources'].items() if source.endswith('/host.php')]
    flags = [cxx, '-std=c++23', '-stdlib=libc++', '-I', ROOT/'runtime/generated/public']
    adapter = work/'host.o'
    subprocess.run([*flags, '-Wall', '-Wextra', '-Werror', '-include', (bundle/host).with_suffix('.hpp'),
                    '-c', transport/'host.cpp', '-o', adapter], check=True)
    executable = work/'service'
    units = [bundle/unit for unit in manifest['translation_units'] if unit != host]
    subprocess.run([*flags, f'--ld-path={linker}', *units, adapter, *manifest['link_inputs'], '-o', executable],
                   check=True)
    return executable


def exercise(work, exe):
    app = work/'app'
    app.mkdir()
    (app/'definition.php').write_text('function answer(): int { return 1; }')
    (app/'main.php').write_text('return answer();')
    state = app/'.scpp'
    endpoint, lock = state/'compiler.sock', state/'compiler.lock'
    children = []

    def start():
        child = subprocess.Popen([exe], cwd=app, stdin=subprocess.DEVNULL, stdout=subprocess.DEVNULL,
                                 stderr=subprocess.PIPE, start_new_session=True)
        children.append(child)
        return child

    def connect():
        peer = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
        peer.settimeout(4)
        try:
            peer.connect(str(endpoint))
        except OSError:
            peer.close()
            raise
        return peer

    def request(command, *, starting=False):
        deadline = time.monotonic() + 5
        while True:
            try:
                peer = connect()
                break
            except (ConnectionRefusedError, FileNotFoundError):
                if not starting or time.monotonic() >= deadline:
                    raise
                time.sleep(.005)
        with peer:
            pid, _, _ = struct.unpack('3i', peer.getsockopt(socket.SOL_SOCKET, socket.SO_PEERCRED, 12))
            peer.sendall(command + b'\n')
            with peer.makefile('rb') as stream:
                line = stream.readline()
                assert line.endswith(b'\n'), line
            return pid, line.rstrip(b'\n')

    try:
        gate = Barrier(8)
        def contender(_):
            gate.wait(timeout=5)
            start()
            return request(b'status', starting=True)
        with ThreadPoolExecutor(8) as pool:
            replies = list(pool.map(contender, range(8)))
        pid, generation = replies[0]
        assert generation == b'0' and all(reply == replies[0] for reply in replies), replies
        owner, = [c for c in children if c.pid == pid]
        for child in children:
            if child is not owner:
                assert child.wait(timeout=5) == 0
        assert stat.S_IMODE(endpoint.stat().st_mode) == 0o600
        assert stat.S_IMODE(state.stat().st_mode) == 0o700
        lock_identity = lock.stat().st_dev, lock.stat().st_ino
        cold = b'ok|1|definition.php,main.php,|definition.php,main.php,|'
        assert request(b'run') == (pid, cold)
        with connect() as peer:
            peer.sendall(b'sto')  # Disconnect halfway through a command.
        assert request(b'status') == (pid, b'1')
        with connect() as peer:
            peer.sendall(b'run\n')  # Abandon the reply to a complete request.
        assert request(b'status') == (pid, b'2')
        assert request(b'run') == (pid, b'ok|3|||definition.php,main.php,')
        assert request(b'stop') == (pid, b'stopped')
        assert owner.wait(timeout=5) == 0 and not endpoint.exists()
        assert (lock.stat().st_dev, lock.stat().st_ino) == lock_identity

        crashed = start()
        assert request(b'run', starting=True) == (crashed.pid, cold)
        crashed.kill()
        assert crashed.wait(timeout=5) == -signal.SIGKILL and endpoint.is_socket()
        (app/'definition.php').write_text('function answer(): int { return 9; }')
        restarted = start()
        assert request(b'status', starting=True) == (restarted.pid, b'0')
        assert request(b'run') == (restarted.pid, cold)
        assert b'72657475726e20393b' in request(b'snapshot')[1]  # Current source contains "return 9;".
        assert request(b'stop') == (restarted.pid, b'stopped')
        assert restarted.wait(timeout=5) == 0 and not endpoint.exists()
        assert (lock.stat().st_dev, lock.stat().st_ino) == lock_identity
        endpoint.write_text('keep')
        refused = start()
        assert refused.wait(timeout=5) != 0 and endpoint.read_text() == 'keep'
        print('8 starters, 1 owner; partial and complete disconnects survived; clean stop; '
              'SIGKILL left stale socket; restart parsed edited source; occupied regular file preserved.')
    finally:
        for child in children:
            if child.poll() is None:
                child.kill()
            child.wait(timeout=5)
            if child.returncode not in (0, -signal.SIGKILL):
                print(child.stderr.read().decode(errors='replace'), file=sys.stderr)
            child.stderr.close()


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-socket-') as tmp:
        work = Path(tmp)
        exercise(work, build_service(work, *sys.argv[1:]))
