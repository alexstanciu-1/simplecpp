"""Build and exercise the PHP-flavor developer walkthroughs on Linux."""
import argparse
import fcntl
import json
import os
from pathlib import Path
import shutil
import socket
import subprocess
import tempfile
import time
from http.client import HTTPConnection
from http.server import BaseHTTPRequestHandler, HTTPServer
from threading import Thread

suite = Path(__file__).resolve().parent
root = suite.parents[1]


def build(app, work):
    toolchain = work / 'toolchain.json'
    toolchain.write_text(json.dumps({'cxx': 'clang++', 'flags': [
        '-std=c++23', '-O2', '-stdlib=libc++', '-I', str(root/'runtime/generated/public')]}))
    command = ['php', root/'compiler/main.php', 'build', app, root/'build/clang/native-layout/facts.json',
               work/'generated', work/'native', toolchain]
    result = subprocess.run(command, capture_output=True, timeout=90)
    if result.returncode:
        raise RuntimeError((result.stderr or result.stdout).decode(errors='replace'))
    return work/'native/program'


def check_cli(exe, args, code, text, env=None):
    result = subprocess.run([exe, *args], capture_output=True, timeout=10, env=env)
    actual = (result.returncode, result.stdout, result.stderr)
    expected = (code, text.encode(), b'')
    if actual != expected:
        raise AssertionError(f'{args}: expected {expected!r}, got {actual!r}')


def invoice_summary(exe, work):
    check_cli(exe, [], 2, 'usage: invoice-summary FILE\n')
    path = work/'input with spaces'
    check_cli(exe, [path], 1, 'unreadable\n')
    path.write_text('')
    check_cli(exe, [path], 0, 'invalid=0\n')
    path.write_text('north|120\nsouth|0\nnorth|003\nempty|\nbad\n|4\nnorth|7x\nwest|999999999\n')
    check_cli(exe, [path], 0, 'north=123\nsouth=0\ninvalid=5\n')


def link_report(exe, work):
    check_cli(exe, [], 0, 'skipped 0\n')
    check_cli(exe, ['/docs/a?x=1', '/', '/docs', '?skip', 'invalid', '/jobs?a=/docs'], 0,
              '/docs 2\n/ 1\n/jobs 1\nskipped 2\n')


def form_preview(exe, work):
    for raw, line in [
        ('label=0', '{"label":"0","notify":false}'),
        ('%6cabel=A+B%2BC&notify=on', '{"label":"A B+C","notify":true}'),
        ('notify=off&label=a%26b%3Dc', '{"label":"a&b=c","notify":false}'),
        ('label=%c3%a9', '{"label":"é","notify":false}'),
        ('label=a=b', '{"label":"a=b","notify":false}'),
    ]:
        check_cli(exe, [raw], 0, line+'\n')
    for raw, line in [
        ('title=A', 'unknown field'), ('notify=on', 'label required'), ('label=', 'label required'),
        ('label=one&label=two', 'duplicate field'), ('label=%', 'invalid escape'),
        ('label=%XY', 'invalid escape'), ('label=%FF', 'invalid UTF-8'), ('label', 'expected key=value'),
        ('label=A&notify=1', 'invalid notify'), ('label='+'é'*81, 'label too long'),
        ('label='+'x'*4096, 'form too long'),
    ]:
        check_cli(exe, [raw], 1, line+'\n')


def event_journal(exe, work):
    journal = work/'events'
    check_cli(exe, [journal, ''], 2, 'invalid event\n')
    check_cli(exe, [journal, 'a\nb'], 2, 'invalid event\n')
    check_cli(exe, [work/'absent/events', 'open'], 1, 'lock failed\n')
    # A separate participant holds the same stable lock file. A busy result
    # must not create the data file or append even a partial event.
    lock = work/'events.lock'
    with lock.open('wb') as token:
        fcntl.flock(token, fcntl.LOCK_EX)
        check_cli(exe, [journal, 'skip'], 3, 'busy\n')
        if journal.exists():
            raise AssertionError('contending client created the journal')
    for event in ('0', 'opened', 'arrivé'):
        check_cli(exe, [journal, event], 0, f'{len(event.encode())+1}\n')
    if journal.read_bytes() != '0\nopened\narrivé\n'.encode() or not lock.exists():
        raise AssertionError('journal contents or lock identity changed unexpectedly')
    blocked = work/'directory'
    blocked.mkdir()
    check_cli(exe, [blocked, 'retry'], 1, 'open failed\n')
    with (work/'directory.lock').open('rb') as token:
        fcntl.flock(token, fcntl.LOCK_EX | fcntl.LOCK_NB)


def member_export(exe, work):
    inputs = ['member:Ada', 'unknown:skip', 'admin:B:2', 'member:', 'bad', 'member:0']
    check_cli(exe, [], 2, 'usage: member-export text|jsonl ROLE:NAME...\n')
    check_cli(exe, ['other'], 2, 'unknown format\n')
    check_cli(exe, ['text', *inputs], 0, 'member: Ada\nadmin: B:2\nmember: 0\n')
    check_cli(exe, ['jsonl', *inputs], 0,
              '{"name":"Ada","role":"member"}\n{"name":"B:2","role":"admin"}\n{"name":"0","role":"member"}\n')
    check_cli(exe, ['jsonl'], 0, '')


def settings_patch(exe, work):
    control = ['php', '-n', suite/'stock-php/settings-patch.php']
    cases = [
        ('{}', 0, '{"enabled":true,"limit":10}'),
        ('{"enabled":false,"limit":0}', 0, '{"enabled":false,"limit":0}'),
        ('{"limit":null}', 0, '{"enabled":true,"limit":null}'),
        ('{"limit":100,"enabled":true}', 0, '{"enabled":true,"limit":100}'),
        ('[]', 1, 'expected object'), ('null', 1, 'expected object'), ('{', 1, 'invalid JSON'),
        ('{"enabled":0}', 1, 'invalid enabled'), ('{"limit":"0"}', 1, 'invalid limit'),
        ('{"limit":1e0}', 1, 'invalid limit'), ('{"limit":101}', 1, 'invalid limit'),
        ('{"limit":-1}', 1, 'invalid limit'), ('{"other":null}', 1, 'unknown field'),
    ]
    for data, code, line in cases:
        check_cli(exe, [data], code, line+'\n')
        check_cli(control[0], [*control[1:], data], code, line+'\n')
    repeated = '{"limit":2,"limit":3}'
    check_cli(exe, [repeated], 1, 'invalid JSON\n')
    check_cli(control[0], [*control[1:], repeated], 0, '{"enabled":true,"limit":3}\n')


def ticket_report(exe, work):
    # An isolated database on a private Unix socket. No system service or TCP listener.
    address, data = work/'m.sock', work/'db'
    env = dict(os.environ, TICKETS_DB_USER='root', TICKETS_DB_PASSWORD='')
    check_cli(exe, [], 2, 'usage: ticket-report SOCKET STATE...\n', env)
    check_cli(exe, [address, 'open'], 1, 'connection failed\n', env)
    subprocess.run(['mariadb-install-db', '--no-defaults', '--datadir='+str(data),
                    '--auth-root-authentication-method=normal'], capture_output=True, timeout=60, check=True)
    with (work/'db.log').open('w') as log:
        server = subprocess.Popen(['mariadbd', '--no-defaults', '--datadir='+str(data), '--skip-networking',
                                   '--socket='+str(address), '--pid-file='+str(work/'pid')], stderr=log, stdout=log)
        try:
            client = ['mariadb', '--no-defaults', '--socket='+str(address), '-uroot']
            for _ in range(200):
                if server.poll() is not None:
                    raise RuntimeError((work/'db.log').read_text()[-1200:])
                if address.is_socket() and subprocess.run([*client, '-e', 'SELECT 1'], capture_output=True).returncode == 0:
                    break
                time.sleep(.05)
            else:
                raise TimeoutError('private MariaDB startup')
            subprocess.run([*client, '-e', 'CREATE DATABASE tickets'], capture_output=True, timeout=10, check=True)
            variant = work/'checked'
            app = variant/'app'
            app.mkdir(parents=True)
            (app/'module.json').write_text('{"general_api":true,"dependencies":{"runtime":{"modules":["mysqli"]}}}')
            shutil.copy(suite/'ticket-report/checked-count.php', app/'main.php')
            checked = build(app, variant)
            check_cli(checked, [address, 'open'], 1, 'prepare failed\n', env)
            check_cli(exe, [address, 'open'], 1, 'prepare failed\n', env)
            sql = """USE tickets;
CREATE TABLE tickets (queue VARCHAR(32), state VARCHAR(32));
INSERT INTO tickets VALUES ('mail','open'),('mail','open'),('api','open'),('api','done'),
    (NULL,'triage'),('','triage');
"""
            subprocess.run(client, input=sql.encode(), capture_output=True, timeout=10, check=True)
            check_cli(checked, [address, 'open'], 0, '3\n', env)
            check_cli(checked, [address, 'unknown'], 0, '0\n', env)
            shutil.copy(suite/'ticket-report/query-states.php', app/'main.php')
            alternate = build(app, variant)
            check_cli(alternate, [address], 0, 'error\ncommand\ncount 6\n', env)
            check_cli(exe, [address, 'triage'], 0, 'triage:\n  (unassigned) 1\n   1\n', env)
            check_cli(exe, [address, 'open', 'done', 'unknown'], 0,
                      'open:\n  api 1\n  mail 2\ndone:\n  api 1\nunknown:\n', env)
        finally:
            server.terminate()
            try:
                server.wait(timeout=10)
            except subprocess.TimeoutExpired:
                server.kill()
                server.wait()


def delivery_replay(exe, work):
    path = work/'deliveries'
    check_cli(exe, [path], 1, 'unreadable\n')
    path.write_text('')
    check_cli(exe, [path], 0, 'retries=0 invalid=0\n')
    path.write_bytes(b'1970-01-01T00:00:00Z mail ok\r\n'
                     b'2024-02-29T23:59:59Z feed retry\n'
                     b'2024-01-02T10:00:00Z mail ok\n'
                     b'2023-01-02T10:00:00Z mail ok\n'
                     b'\nunknown job ok\nbad\n1970-01-01T00:00:00Z sms ok')
    check_cli(exe, [path], 0, 'mail 2024-01-02T10:00:00Z\nsms 1970-01-01T00:00:00Z\nretries=1 invalid=2\n')


def catalog_view(exe, work):
    path = work/'catalog.json'
    check_cli(exe, [path], 1, 'unreadable\n')
    cases = [
        ('[]', 0, '<ul>\n</ul>\n'),
        ('[{"label":"A&B <sale>","units":0},{"label":"0","units":2}]', 0,
         '<ul>\n<li>A&amp;B &lt;sale&gt; (0)</li>\n<li>0 (2)</li>\n</ul>\n'),
        ('{}', 1, 'expected array\n'),
        ('[{"label":"first","units":1},{"label":"missing"}]', 1, 'invalid product 1\n'),
        ('[{"label":"bad","units":"1"}]', 1, 'invalid product 0\n'),
        ('[{"label":"bad","units":-1}]', 1, 'invalid product 0\n'),
        ('{"a":1,"a":2}', 1, 'json duplicate_key at byte 7\n'),
    ]
    for data, code, text in cases:
        path.write_text(data)
        check_cli(exe, [path], code, text)


def upstream_responses(exe, env):
    # Exercise the client independently of the endpoint's response schema.
    cases = [
        (200, b'{}', 'invalid response 200'),
        (200, b'{"scope":"default","enabled":0}', 'invalid response 200'),
        (200, b'{"scope":false,"enabled":false}', 'invalid response 200'),
        (200, b'null', 'invalid response 200'),
        (200, b'{"scope":"team","enabled":false,"enabled":true}', 'invalid response 200'),
        (502, b'<html>proxy unavailable</html>', 'invalid response 502'),
        (503, b'{"error":42}', 'invalid response 503'),
        (500, b'{}', 'invalid response 500'),
    ]

    class Reply(BaseHTTPRequestHandler):
        def do_POST(self):
            self.rfile.read(int(self.headers['Content-Length']))
            status, body, _ = cases[int(self.path[1:])]
            self.send_response(status)
            self.send_header('Content-Length', str(len(body)))
            self.end_headers()
            self.wfile.write(body)

        def log_message(self, *_):
            pass

    with HTTPServer(('127.0.0.1', 0), Reply) as server:
        worker = Thread(target=server.serve_forever, kwargs={'poll_interval': .05})
        worker.start()
        try:
            for i, (_, _, text) in enumerate(cases):
                check_cli(exe, [f'http://127.0.0.1:{server.server_port}/{i}', 'true'], 1, text+'\n', env)
        finally:
            server.shutdown()
            worker.join()


def preference_client(exe, work):
    # Exercise the caller and server together behind a private loopback Apache instance.
    backend = work/'backend'
    backend.mkdir()
    app = backend/'app'
    shutil.copytree(suite/'preferences-api', app, ignore=shutil.ignore_patterns('module.sources.json'))
    server_exe = build(app, backend)
    address = work/'prefs.sock'
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    modules = Path('/usr/lib/apache2/modules')
    conf = work/'httpd.conf'
    conf.write_text(''.join(f'LoadModule {m}_module "{modules}/mod_{m}.so"\n'
                            for m in ('mpm_event', 'authz_core', 'proxy', 'proxy_fcgi')) + f"""
ServerRoot "{work}"
DefaultRuntimeDir "{work}"
PidFile "{work}/httpd.pid"
ErrorLog "{work}/apache.log"
Listen 127.0.0.1:{port}
ServerName localhost
ProxyFCGIBackendType GENERIC
ProxyPass "/" "unix:{address}|fcgi://localhost/"
<Location "/">
    Require all granted
</Location>
""")
    url = f'http://127.0.0.1:{port}'
    env = dict(os.environ, no_proxy='127.0.0.1', NO_PROXY='127.0.0.1')
    with (work/'children.log').open('w') as log:
        backend_proc = subprocess.Popen([server_exe, address], cwd=work, stderr=log)
        httpd = subprocess.Popen(['apache2', '-X', '-f', conf], cwd=work, stderr=log)
        try:
            for _ in range(200):
                if backend_proc.poll() is not None or httpd.poll() is not None:
                    raise RuntimeError((work/'children.log').read_text())
                if address.is_socket():
                    try:
                        with socket.create_connection(('127.0.0.1', port), timeout=.2):
                            break
                    except OSError:
                        pass
                time.sleep(.02)
            else:
                raise TimeoutError('private Apache or FastCGI startup')
            # The native application owns a persistent loop. Two handled
            # requests increment its process-local counter; a provider-level
            # rejection never reaches the handler.
            for body, status, counter in [(b'true', 200, '1'), (b' '*4097, 413, None), (b'null', 422, '2')]:
                connection = HTTPConnection('127.0.0.1', port, timeout=5)
                try:
                    connection.request('POST', '/preference', body, {'Content-Type':'application/json'})
                    response = connection.getresponse()
                    response.read()
                    if (response.status, response.getheader('X-Request-Number')) != (status, counter):
                        raise AssertionError((response.status, response.getheaders()))
                finally:
                    connection.close()
            for path, body, code, text in [
                ('/preference', 'false', 0, 'default false'),
                ('/preference?scope=team', 'true', 0, 'team true'),
                ('/preference', 'null', 1, '422 expected boolean'),
                ('/preference', ' '*4092+'true', 0, 'default true'),
                ('/preference', ' '*4093+'true', 1, '413 request too large'),
                ('/preference', 'false', 0, 'default false'),
                ('/preference', '{', 1, '400 syntax'),
                ('/gone', 'true', 1, '404 route'),
            ]:
                check_cli(exe, [url+path, body], code, text+'\n', env)
        finally:
            for proc in (httpd, backend_proc):
                proc.terminate()
                try:
                    proc.wait(timeout=5)
                except subprocess.TimeoutExpired:
                    proc.kill()
                    proc.wait()
    check_cli(exe, [url+'/preference', 'true'], 1, 'transport error\n', env)
    upstream_responses(exe, env)


def preferences_api(exe, work):
    client = shutil.which('cgi-fcgi')
    if not client:
        raise RuntimeError('preferences-api needs the cgi-fcgi client')
    address = work/'app.sock'
    cases = [
        ('POST', '/preference', '', b'false', 200, {'scope': 'default', 'enabled': False}),
        ('POST', '/preference', 'scope=team', b'true', 200, {'scope': 'team', 'enabled': True}),
        ('POST', '/preference', '', b'null', 422, {'error': 'expected boolean'}),
        ('POST', '/preference', '', b'{', 400, {'error': 'syntax'}),
        ('POST', '/preference', '', b'"\xff"', 400, {'error': 'syntax'}),
        ('POST', '/preference', 'scope=', b'true', 400, {'error': 'scope'}),
        ('GET', '/preference', '', b'', 405, {'error': 'method'}),
        ('POST', '/absent', '', b'true', 404, {'error': 'route'}),
    ]
    for method, path, query, body, status, response in cases:
        server = subprocess.Popen([exe, address, '--once'], cwd=work, stderr=subprocess.PIPE)
        try:
            for _ in range(200):
                if server.poll() is not None:
                    raise RuntimeError(server.stderr.read().decode())
                if address.is_socket():
                    break
                time.sleep(.01)
            env = {'REQUEST_METHOD': method, 'REQUEST_URI': path + ('?'+query if query else ''),
                   'QUERY_STRING': query, 'CONTENT_LENGTH': str(len(body)), 'CONTENT_TYPE': 'application/json'}
            client_result = subprocess.run([client, '-bind', '-connect', address], env=env, input=body,
                                           capture_output=True, timeout=5, check=True)
            headers, data = client_result.stdout.split(b'\r\n\r\n', 1)
            fields = dict(line.decode().split(': ', 1) for line in headers.split(b'\r\n'))
            actual = (int(fields['Status'].split()[0]), json.loads(data))
            if (fields.get('Content-Type', '').split(';')[0] != 'application/json'
                    or int(fields['Content-Length']) != len(data) or fields.get('X-Request-Number') != '1'):
                raise AssertionError((fields, data))
            if actual != (status, response) or (status == 405 and fields.get('Allow') != 'POST'):
                raise AssertionError((method, path, query, body, actual, fields))
            if server.wait(timeout=5) or address.exists():
                raise RuntimeError('FastCGI app did not exit and close the socket cleanly')
        finally:
            if server.poll() is None:
                server.kill()
            server.communicate(timeout=5)


apps = {'invoice-summary': invoice_summary, 'link-report': link_report, 'preferences-api': preferences_api,
        'catalog-view': catalog_view, 'delivery-replay': delivery_replay, 'ticket-report': ticket_report,
        'settings-patch': settings_patch, 'preference-client': preference_client,
        'member-export': member_export, 'form-preview': form_preview, 'event-journal': event_journal}
if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('apps', nargs='*', help='default: all apps')
    args = parser.parse_args()
    for name in args.apps or apps:
        with tempfile.TemporaryDirectory(prefix='qos-') as directory:
            work = Path(directory)
            app = work/'app'
            shutil.copytree(suite/name, app, ignore=shutil.ignore_patterns('module.sources.json'))
            apps[name](build(app, work), work)
            print(f'{name}: passed', flush=True)
