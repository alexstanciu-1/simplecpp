"""One held FastCGI exchange across a real Apache/PHP/native worker pool refresh."""
import argparse
from concurrent.futures import ThreadPoolExecutor
from contextlib import ExitStack
import http.client
import os
from pathlib import Path
import signal
import socket
import struct
import subprocess
import sys
import tempfile
import time


ROOT = Path(__file__).resolve().parents[2]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('apache')
parser.add_argument('modules')
parser.add_argument('--profile', action='store_true')
parser.add_argument('--toolchain', default='')
options = parser.parse_args()
apache, modules = options.apache, options.modules


def wait(check):
    deadline = time.monotonic() + 15
    while not check():
        if time.monotonic() >= deadline:
            raise AssertionError(f'timed out waiting for {check}')
        time.sleep(.02)


def record(kind, body):
    return struct.pack('!BBHHBB', 1, kind, 1, len(body), 0, 0) + body


with tempfile.TemporaryDirectory() as folder, ExitStack() as stack:
    work = Path(folder)
    app = work / 'app'
    app.mkdir()
    (app / 'module.json').write_text('{"general_api":true,"dependencies":{"runtime":{"modules":["fcgi"]}}}')
    entry = app / 'main.phs'
    original = (ROOT / 'compiler/tests/fixtures/resource_modules/fcgi.phs').read_text()
    source = original.replace('$body = $request->method', '$body = "A|".$request->method')
    entry.write_text(source)
    endpoint = work / 'public.sock'

    def launch(*cmd, stderr=None):
        proc = subprocess.Popen(cmd, stderr=stderr, env={**os.environ, 'TMPDIR': folder})
        def finish():
            if proc.poll() is None:
                proc.terminate()
                proc.wait(timeout=10)
        stack.callback(finish)
        return proc

    proxy = launch(sys.executable, ROOT / 'tools/fcgi_dev/serve.py', '--app', app, '--socket', endpoint,
                   '--toolchain', options.toolchain,
                   stderr=stack.enter_context((work / 'supervisor.log').open('w')) if options.profile else None)
    wait(endpoint.is_socket)
    with socket.socket() as probe:
        probe.bind(('127.0.0.1', 0))
        port = probe.getsockname()[1]
    conf = work / 'apache.conf'
    conf.write_text(''.join(f'LoadModule {m}_module "{modules}/mod_{m}.so"\n'
                            for m in ('mpm_event', 'authz_core', 'proxy', 'proxy_fcgi')) + f'''
ServerRoot "{work}"
DefaultRuntimeDir "{work}"
PidFile "{work}/httpd.pid"
ErrorLog "{work}/httpd.log"
Listen 127.0.0.1:{port}
ServerName localhost
ProxyFCGIBackendType GENERIC
ProxyPass "/app/" "unix:{endpoint}|fcgi://localhost/app/" enablereuse=off
<Location "/app/">
    Require all granted
</Location>
''')
    httpd = launch(apache, '-X', '-f', conf)
    def listening():
        assert httpd.poll() is None
        try:
            socket.create_connection(('127.0.0.1', port), .2).close()
            return True
        except OSError:
            return False
    wait(listening)

    def call():
        conn = http.client.HTTPConnection('127.0.0.1', port, timeout=130)
        try:
            conn.request('POST', '/app/check?q=1', b'x\0y', {'X-Probe': 'seen'})
            response = conn.getresponse()
            return response.status, response.headers.get_all('Set-Cookie'), response.read()
        finally:
            conn.close()

    def expected(version):
        return 201, ['first=1', 'second=2'], version + b'|POST|/app/check|q=1|seen|x\0y'

    def sockets():
        return set(work.glob('scpp-fcgi-*/w*.sock'))

    if options.profile:
        from profile_edits import measure
        measure(work, entry, source, proxy.pid, call, expected)
        sys.exit(0)

    assert (first := call()) == expected(b'A'), first
    old = sockets()
    assert len(old) == 2
    held = stack.enter_context(socket.socket(socket.AF_UNIX))
    held.settimeout(15)
    held.connect(str(endpoint))
    held.sendall(record(1, struct.pack('!HB5x', 1, 0)))
    # The old worker accepted the connection and is waiting for the remaining records.
    def accepted():
        return any(len(fields) == 8 and fields[5] == '03' and Path(fields[7]) in old
                   for fields in (line.split() for line in Path('/proc/net/unix').read_text().splitlines()))
    wait(accepted)
    stamp = entry.stat()
    entry.write_text(source.replace('"A|"', '"B|"'))
    os.utime(entry, ns=(stamp.st_atime_ns, stamp.st_mtime_ns))
    with ThreadPoolExecutor(2) as callers:
        assert list(callers.map(lambda _: call(), range(2))) == [expected(b'B')] * 2
    fresh = sockets() - old
    assert len(fresh) == 2
    wait(lambda: len(sockets() & old) == 1)
    assert call() == expected(b'B') and sockets() - old == fresh
    held.sendall(record(4, b'') + record(5, b''))
    reply = bytearray()
    while chunk := held.recv(65536):
        reply.extend(chunk)
    assert b'A|GET|/' in reply, reply
    wait(lambda: sockets() == fresh)
    entry.write_text('return unknown_name();')
    status, _, error = call()
    assert status == 500 and b'unknown_name' in error, (status, error)
    entry.write_text(source.replace('"A|"', '"C|"'))
    assert call() == expected(b'C')
    # Kill only the resident compiler. The next check must start another one.
    children = Path(f'/proc/{proxy.pid}/task/{proxy.pid}/children').read_text().split()
    compiler, = [int(pid) for pid in children if b'compiler.php' in Path(f'/proc/{pid}/cmdline').read_bytes()]
    os.kill(compiler, signal.SIGKILL)
    wait(lambda: not Path(f'/proc/{compiler}').exists())
    assert call() == expected(b'C')
