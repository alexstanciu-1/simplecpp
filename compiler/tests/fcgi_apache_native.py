"""Run a compiled Simple C++ FastCGI application behind an isolated Apache virtual host."""
import http.client
import json
from pathlib import Path
import socket
import subprocess
import sys
import tempfile
import time

from app_namespace_native import invoke, service

root = Path(__file__).resolve().parents[2]
clang, apache, module_dir = sys.argv[1:]
with tempfile.TemporaryDirectory(prefix='scpp-fcgi-') as directory:
    work = Path(directory)
    app, out = work/'app', work/'out'
    app.mkdir()
    manifest, entry = app/'module.json', app/'main.phs'
    manifest.write_text('{}')
    entry.write_text('return 0;')
    proc = service(app, out)
    try:
        assert 'error' not in invoke(proc)
        assert all('fcgi' not in item for item in json.loads((out/'manifest.json').read_text())['link_inputs'])
        assert all('fcgi' not in unit.read_text() for unit in (out/'units').glob('*.cpp'))
        manifest.write_text('{"general_api":true}')
        entry.write_text('fcgi_listen("/unused",32); return 0;')
        assert 'requires module runtime-fcgi' in invoke(proc).get('error', '')
        manifest.write_text('{"general_api":true,"dependencies":{"runtime":{"modules":["fcgi"]}}}')
        entry.write_text((root/'compiler/tests/fixtures/resource_modules/fcgi.phs').read_text())
        result = invoke(proc)
        assert 'error' not in result, result
        bundle = json.loads((out/'manifest.json').read_text())
        subprocess.run([clang, '-std=c++23', '-stdlib=libc++', '-I', root/'runtime/generated/public',
                        *(out/u for u in bundle['translation_units']), *bundle['link_inputs'], '-o', work/'server'], check=True)
    finally:
        proc.stdin.close()
        proc.wait()
    path = work/'backend.sock'
    path.write_text('keep')
    collision = subprocess.run([work/'server', path], capture_output=True, timeout=3)
    assert collision.returncode and path.read_text() == 'keep'
    path.unlink()
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    conf = work/'httpd.conf'
    modules = ['mpm_event', 'authz_core', 'proxy', 'proxy_fcgi']
    conf.write_text(''.join(f'LoadModule {m}_module "{module_dir}/mod_{m}.so"\n' for m in modules) +
                    f'''ServerRoot "{work}"
DefaultRuntimeDir "{work}"
PidFile "{work}/httpd.pid"
ErrorLog "{work}/apache.log"
Listen 127.0.0.1:{port}
ServerName localhost
ProxyFCGIBackendType GENERIC
ProxyPass "/app/" "unix:{path}|fcgi://localhost/app/"
<Location "/app/">
    Require all granted
</Location>
''')
    def call(route, body=b''):
        conn = http.client.HTTPConnection('127.0.0.1', port, timeout=4)
        try:
            conn.request('POST', route, body, {'X-Probe': 'seen'})
            response = conn.getresponse()
            return response.status, response.headers.get_all('Set-Cookie'), response.read()
        finally:
            conn.close()

    server = subprocess.Popen([work/'server', path], stderr=subprocess.PIPE)
    proxy = subprocess.Popen([apache, '-X', '-f', conf], stderr=subprocess.PIPE)
    try:
        for attempt in range(100):
            assert proxy.poll() is None, proxy.stderr.read()
            assert server.poll() is None, server.stderr.read()
            try:
                with socket.create_connection(('127.0.0.1', port), timeout=.2):
                    if path.exists(): break
            except OSError:
                pass
            time.sleep(.02)
        assert call('/app/check?q=%2F&q=2', b'a\0b') == (
            201, ['first=1', 'second=2'], b'POST|/app/check|q=%2F&q=2|seen|a\0b')
        assert call('/app/previous') == (200, None, b'a\0b')
        assert call('/app/large', b'x'*33)[0] == 413
        assert call('/app/fail') == (500, None, b'')
        assert call('/app/stop')[0] == 201
        assert server.wait(timeout=4) == 0 and not path.exists()
        server = subprocess.Popen([work/'server', path], stderr=subprocess.PIPE)
        for attempt in range(100):
            if path.exists(): break
            time.sleep(.02)
        assert call('/app/double')[0] == 201
        assert server.wait(timeout=4) != 0 and b'FastCGI exchange has finished' in server.stderr.read()
    finally:
        for child in (proxy, server):
            if child.poll() is None:
                child.terminate()
                try:
                    child.wait(timeout=4)
                except subprocess.TimeoutExpired:
                    child.kill()
                    child.wait()
