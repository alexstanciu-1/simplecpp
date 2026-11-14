#!/usr/bin/env python3
"""Loopback-only compiler workbench; each request uses disposable files and bounded subprocesses."""
import argparse
import hmac
import json
import os
from pathlib import Path
import re
import secrets
import select
import signal
import shutil
import subprocess
import tempfile
import threading
import time
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from native_build import qualified_ninja, write_build
from pch_cache import runtime_pch
from contextlib import nullcontext

HERE = Path(__file__).resolve().parent
ROOT = HERE.parents[1]
TOKEN = secrets.token_urlsafe(32)
LOCK = threading.Lock()
LIMIT = 256 * 1024


def process(command, cwd, timeout, env=None):
    start = time.perf_counter()
    with tempfile.TemporaryFile() as out, tempfile.TemporaryFile() as err:
        # prlimit constrains compiler/program output, address space and core dumps without a shell.
        args = ['prlimit', '--fsize=8388608', '--as=2147483648', '--core=0', '--', *map(str, command)]
        child = subprocess.Popen(args, cwd=cwd, stdout=out, stderr=err, stdin=subprocess.DEVNULL, start_new_session=True, env=env)
        timed_out = False
        try:
            # A timeout-based Popen.wait polls with backoff on POSIX, delaying
            # observation of fast builds. pidfd readiness reports actual exit.
            descriptor = os.pidfd_open(child.pid)
            try:
                if not select.select([descriptor], [], [], timeout)[0]:
                    raise subprocess.TimeoutExpired(args, timeout)
                code = child.wait()
            finally:
                os.close(descriptor)
        except subprocess.TimeoutExpired:
            timed_out = True
            os.killpg(child.pid, signal.SIGKILL)
            code = child.wait()
        finally:
            # Also retire any descendants left after the leader exits.
            try:
                os.killpg(child.pid, signal.SIGKILL)
            except ProcessLookupError:
                pass
        sizes = [os.fstat(f.fileno()).st_size for f in (out, err)]
        out.seek(0)
        err.seek(0)
        return {'ms': (time.perf_counter() - start) * 1000, 'exit_code': code, 'timed_out': timed_out,
                'stdout': out.read(8 * 1024 * 1024).decode('utf-8', 'replace'),
                'stderr': err.read(LIMIT).decode('utf-8', 'replace'), 'output_bytes': sizes,
                'stderr_truncated': sizes[1] > LIMIT}


def validate(data):
    if not isinstance(data, dict) or not isinstance(data.get('files'), list) or not 1 <= len(data['files']) <= 24:
        raise ValueError('Provide 1–24 source files.')
    names = set()
    total = 0
    for file in data['files']:
        if not isinstance(file, dict) or not isinstance(file.get('name'), str) or not re.fullmatch(r'[A-Za-z0-9_][A-Za-z0-9_.-]{0,79}', file['name']):
            raise ValueError('File names must be simple names, without directories.')
        if file['name'] in names or file.get('flavor') not in ('cpp', 'php', 'js') or not isinstance(file.get('source'), str):
            raise ValueError('Use unique file names, a supported flavor and source text.')
        names.add(file['name'])
        total += len(file['source'].encode())
    if total > LIMIT or data.get('entry') not in names or data.get('mode') not in ('inspect', 'build', 'run'):
        raise ValueError('Select an entry, valid action and at most 256 KiB of source.')
    if data.get('optimization', 'O2') not in ('O0', 'O1', 'O2') or not isinstance(data.get('pch', False), bool) or not isinstance(data.get('validate_pch', True), bool):
        raise ValueError('Choose O0, O1 or O2 and a boolean PCH option.')
    return data


def compile_report(data, php, cache_root=None):
    with tempfile.TemporaryDirectory(prefix='scpp-panel-') as directory:
        work = Path(directory)
        (work / 'sources').mkdir()
        for file in data['files']:
            (work / 'sources' / file['name']).write_text(file['source'])
        request = work / 'request.json'
        request.write_text(json.dumps(data))
        # Compiler timing must not inherit debugger/profiler instrumentation.
        result = process([php, '-d', 'memory_limit=512M', HERE / 'report.php', request], work, 30,
                         env={**os.environ, 'XDEBUG_MODE': 'off'})
        try:
            if result['exit_code'] != 0:
                raise ValueError('Compiler report process failed')
            report = json.loads(result['stdout'])
        except (ValueError, json.JSONDecodeError):
            return {'ok': False, 'stages': [{'name': 'Compiler report process', 'status': 'error', **result}]}
        report['compiler_process_ms'] = result['ms']
        if not report['ok'] or data['mode'] == 'inspect':
            return report
        binary = work / 'program'
        native_start = time.perf_counter()
        use_pch = data.get('pch', False)
        cache = runtime_pch(cache_root or ROOT / 'build/compiler-panel-pch', work,
                            data.get('optimization', 'O2')) if use_pch else nullcontext((work, None))
        with cache as (build_dir, cache_report):
            command = write_build(build_dir, [work / 'generated' / unit for unit in report.pop('units')],
                                  data.get('optimization', 'O2'), use_pch,
                                  validate_pch=data.get('validate_pch', True))
            graph = (build_dir / 'build.ninja').read_text()
            qualification_start = time.perf_counter()
            with qualified_ninja(command, build_dir) as invocation:
                qualification_ms = (time.perf_counter() - qualification_start) * 1000
                cached_in_process = invocation is not command
                build = process(invocation, build_dir, 60)
            ninja_ms = build['ms']
            if cache_report is not None:
                cache_report['success'] = build['exit_code'] == 0 and not build['timed_out']
                if cache_report['success']:
                    shutil.copy2(build_dir / 'program', binary)
        build['ms'] = (time.perf_counter() - native_start) * 1000
        report['stages'].append({'name': 'Native build · -' + data.get('optimization', 'O2'), 'status': 'ok' if build['exit_code'] == 0 else 'error',
                                 'command': list(map(str, command)), 'build_graph': graph,
                                 'pch': use_pch, 'pch_cache': cache_report, 'ninja_ms': ninja_ms,
                                 'qualification': 'in-process cache hit' if cached_in_process else 'Ninja',
                                 'launcher_check_ms': qualification_ms,
                                 'validate_pch': data.get('validate_pch', True),
                                 'jobs': 4, 'linker': 'mold', **build})
        report['ok'] = build['exit_code'] == 0
        if not report['ok']:
            return report
        report['stages'][-1]['executable_bytes'] = binary.stat().st_size
        if data['mode'] == 'run':
            run = process([binary], work, 3)
            # A nonzero positive code is a valid source return value, e.g. return 8.
            report['ok'] = not run['timed_out'] and run['exit_code'] >= 0
            report['stages'].append({'name': 'Execution', 'status': 'ok' if report['ok'] else 'error', **run})
        return report


class Handler(BaseHTTPRequestHandler):
    """Exact routes with peer, Host, Origin and per-server token checks; never serves repository files."""
    def send(self, status, body, kind='application/json'):
        content = json.dumps(body).encode() if kind == 'application/json' else body
        self.send_response(status)
        self.send_header('Content-Type', kind + '; charset=utf-8')
        self.send_header('Content-Length', str(len(content)))
        self.send_header('Cache-Control', 'no-store')
        self.send_header('X-Content-Type-Options', 'nosniff')
        self.send_header('Content-Security-Policy', "default-src 'self'; style-src 'self'; script-src 'self'; frame-ancestors 'none'; base-uri 'none'; form-action 'self'")
        self.end_headers()
        self.wfile.write(content)

    def local(self):
        hosts = {f'127.0.0.1:{self.server.server_port}', f'localhost:{self.server.server_port}'}
        return self.client_address[0] == '127.0.0.1' and self.headers.get('Host') in hosts

    def do_GET(self):
        if not self.local():
            return self.send(403, {'error': 'Localhost access only.'})
        if self.path == '/config':
            return self.send(200, {'token': TOKEN, 'php': self.server.php, 'runtime': str(ROOT / 'build/clang/libscpp_runtime.a')})
        routes = {'/': ('index.html', 'text/html'), '/app.js': ('app.js', 'text/javascript'), '/style.css': ('style.css', 'text/css')}
        if self.path not in routes:
            return self.send(404, {'error': 'Not found'})
        filename, kind = routes[self.path]
        self.send(200, (HERE / 'web' / filename).read_bytes(), kind)

    def do_POST(self):
        origin = 'http://' + self.headers.get('Host', '')
        if not self.local() or self.headers.get('Origin') != origin or not hmac.compare_digest(self.headers.get('X-Panel-Token', ''), TOKEN):
            return self.send(403, {'error': 'Only the local panel may submit a run.'})
        if self.path != '/run':
            return self.send(404, {'error': 'Not found'})
        if self.headers.get('Content-Type') != 'application/json':
            return self.send(415, {'error': 'JSON required'})
        try:
            size = int(self.headers.get('Content-Length', '0'))
            if not 0 < size <= 2 * LIMIT:
                raise ValueError('Request too large or empty')
            data = validate(json.loads(self.rfile.read(size)))
        except (ValueError, TypeError) as error:
            return self.send(400, {'error': str(error)})
        if not LOCK.acquire(blocking=False):
            return self.send(409, {'error': 'A compiler run is already active.'})
        try:
            self.send(200, compile_report(data, self.server.php, self.server.pch_cache))
        except Exception as error:
            self.send(500, {'error': str(error)})
        finally:
            LOCK.release()

    def setup(self):
        super().setup()
        self.connection.settimeout(120)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--port', type=int, default=8765)
    parser.add_argument('--php', default=os.environ.get('PHP_EXECUTABLE', 'php'))
    parser.add_argument('--pch-cache', type=Path, default=ROOT / 'build/compiler-panel-pch')
    args = parser.parse_args()
    server = ThreadingHTTPServer(('127.0.0.1', args.port), Handler)
    server.php = args.php
    server.pch_cache = args.pch_cache.resolve()
    print(f'Compiler panel: http://localhost:{server.server_port} (loopback only)', flush=True)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        pass
    finally:
        server.server_close()

if __name__ == '__main__':
    main()
