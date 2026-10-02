#!/usr/bin/env python3
"""Compare local PHP CLI and a private FPM pool on my-try workloads.

FPM modes require matching CLI/FPM versions and cgi-fcgi; CLI-only modes need no daemon.
Existing FPM services are untouched.
Each measured request includes client process startup, output capture and verification.
"""
import argparse
from contextlib import contextmanager
import hashlib
import json
import os
from pathlib import Path
import pwd
import random
import statistics
import subprocess
import time
from urllib.parse import urlencode

from test_runner import DEFAULT_JOBS, Task, positive_jobs, run_tasks

ROOT = Path(__file__).resolve().parents[3]
APP = ROOT / 'compiler/my-try'
MODES = ('cli-current', 'cli-no-xdebug', 'cli-opcache', 'cli-file-cache',
         'cli-file-cache-only', 'fpm-cache-0', 'fpm-cache-1')


def php_literal(value):
    return "'" + str(value).replace('\\', '\\\\').replace("'", "\\'") + "'"


@contextmanager
def fpm_pool(args, cache):
    folder = args.results / ('fpm-' + str(cache))
    folder.mkdir()
    socket = folder / 'pool.sock'
    config = folder / 'fpm.conf'
    config.write_text(f'''[global]
error_log = {folder / 'error.log'}
pid = {folder / 'pid'}
daemonize = no
[benchmark]
user = {pwd.getpwuid(os.getuid()).pw_name}
listen = {socket}
listen.mode = 0600
pm = static
pm.max_children = {args.jobs}
clear_env = no
catch_workers_output = yes
security.limit_extensions = .php
php_admin_flag[display_errors] = on
''')
    environment = dict(os.environ, XDEBUG_MODE='off', PHP_INI_SCAN_DIR=args.scan_dir)
    command = [args.fpm, '-F', '-y', str(config), '-c', args.ini,
               '-d', f'opcache.enable={cache}', '-d', 'opcache.jit=disable',
               '-d', 'opcache.file_update_protection=0', '-d', 'opcache.validate_timestamps=1']
    started = time.perf_counter()
    with (folder / 'daemon.log').open('wb') as log:
        process = subprocess.Popen(command, env=environment, stdout=log, stderr=log)
        try:
            deadline = time.monotonic() + 15
            while not socket.exists():
                if process.poll() is not None or time.monotonic() > deadline:
                    raise RuntimeError(f'FPM failed to start; see {folder}')
                time.sleep(0.01)
            yield socket, (time.perf_counter() - started) * 1000
        finally:
            process.terminate()
            try:
                process.wait(timeout=10)
            except subprocess.TimeoutExpired:
                process.kill()
                process.wait()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', required=True, type=Path)
    parser.add_argument('--programs', required=True, type=Path, help='S2S programs.json manifest')
    parser.add_argument('--modes', nargs='+', choices=MODES,
                        default=['cli-current', 'cli-no-xdebug', 'fpm-cache-0', 'fpm-cache-1'])
    parser.add_argument('--php', default='php8.5')
    parser.add_argument('--fpm', default='/usr/sbin/php-fpm8.5')
    parser.add_argument('--ini', default='/etc/php/8.5/cli/php.ini')
    parser.add_argument('--scan-dir', default='/etc/php/8.5/cli/conf.d')
    parser.add_argument('--jobs', type=positive_jobs, default=DEFAULT_JOBS)
    parser.add_argument('--rounds', type=positive_jobs, default=3)
    parser.add_argument('--repeat', type=positive_jobs, default=48, help='Requests per no-op/suite round')
    args = parser.parse_args()
    args.results = args.results.resolve()
    args.results.mkdir(parents=True)
    cache_paths = {}
    for mode in args.modes:
        if mode.startswith('cli-file-cache'):
            cache_paths[mode] = args.results / mode
            cache_paths[mode].mkdir()
    programs = json.loads(args.programs.read_text())['valid']
    fixtures = args.results / 'fixtures'
    fixtures.mkdir()
    for name, (source, _) in programs.items():
        folder = fixtures / name
        folder.mkdir()
        (folder / 'main.phs').write_text(source)
    entry = args.results / 'entry.php'
    entry.write_text('''<?php
namespace scpp\\compiler;
$work = PHP_SAPI === 'cli' ? $argv[1] : $_GET['work'];
$value = PHP_SAPI === 'cli' ? ($argv[2] ?? '') : ($_GET['value'] ?? '');
if ($work === 'noop') { echo "ok"; return; }
if ($work === 'meta') {
    $status = opcache_get_status(false);
    echo json_encode(['version' => PHP_VERSION, 'sapi' => PHP_SAPI,
        'ini' => php_ini_loaded_file(), 'scanned' => php_ini_scanned_files(),
        'extensions' => get_loaded_extensions(), 'opcache' => ini_get('opcache.enable'),
        'opcache_cli' => ini_get('opcache.enable_cli'), 'jit' => ini_get('opcache.jit'),
        'file_cache' => ini_get('opcache.file_cache'), 'file_cache_only' => ini_get('opcache.file_cache_only'),
        'xdebug_env' => getenv('XDEBUG_MODE'),
        'cached_scripts' => $status === false ? 0 : ($status['opcache_statistics']['num_cached_scripts'] ?? 0)]);
    return;
}
if ($work === 'suite') {
    require ''' + php_literal(APP / 'tests/operators.php') + ''';
    return;
}
require ''' + php_literal(APP / 'boot.php') + ''';
require ''' + php_literal(APP / 'tests/s2s_proof.php') + ''';
S2S_Proof::run();
$compiler = new Compiler();
$compiler->init([''' + php_literal(fixtures) + ''' . '/' . $value]);
$compiler->exec_cpp();
$compiler->prepare();
$compiler->cpp();
echo Model::$cpp_files[0]->text;
''')
    references = {}
    rows = []
    metadata = {}
    startup = {}

    def request(mode, socket, work, value=''):
        environment = dict(os.environ, PHP_INI_SCAN_DIR=args.scan_dir)
        if mode != 'cli-current':
            environment['XDEBUG_MODE'] = 'off'
        if socket is None:
            enabled = mode in ('cli-opcache', 'cli-file-cache', 'cli-file-cache-only')
            settings = ['opcache.enable=1', f'opcache.enable_cli={int(enabled)}',
                        'opcache.jit=disable', 'opcache.file_update_protection=0',
                        'opcache.validate_timestamps=1',
                        f'opcache.file_cache={cache_paths.get(mode, "")}',
                        f'opcache.file_cache_only={int(mode == "cli-file-cache-only")}']
            command = [args.php, '-c', args.ini]
            for setting in settings:
                command.extend(['-d', setting])
            command.extend([str(entry), work, value])
        else:
            environment.update(SCRIPT_FILENAME=str(entry), SCRIPT_NAME='/entry.php',
                               REQUEST_METHOD='GET', QUERY_STRING=urlencode(dict(work=work, value=value)),
                               SERVER_PROTOCOL='HTTP/1.1', GATEWAY_INTERFACE='CGI/1.1', REDIRECT_STATUS='200')
            command = ['cgi-fcgi', '-bind', '-connect', str(socket)]
        result = subprocess.run(command, cwd=ROOT, env=environment, capture_output=True, timeout=120)
        if result.returncode or result.stderr:
            raise RuntimeError(f'{mode} {work}/{value}: {result.returncode}: {result.stderr!r}')
        body = result.stdout
        if socket is not None:
            headers, separator, body = body.partition(b'\r\n\r\n')
            if not separator or b'Status:' in headers:
                raise RuntimeError(f'Unexpected FastCGI response: {result.stdout[:1000]!r}')
        if work != 'meta':
            key = (work, value)
            digest = hashlib.sha256(body).hexdigest()
            if key not in references:
                references[key] = digest
            if references[key] != digest:
                raise RuntimeError(f'{mode} output mismatch for {key}')
        return body

    def measure(mode, socket):
        metadata[mode] = json.loads(request(mode, socket, 'meta'))
        if metadata[mode]['version'] != next(iter(metadata.values()))['version']:
            raise RuntimeError('CLI and FPM versions differ')
        for work in ['noop', 'fixture', 'suite']:
            values = list(programs) if work == 'fixture' else [''] * args.repeat
            # Serial first-touch sweep verifies output and warms opcode/filesystem caches.
            started = time.perf_counter()
            for value in dict.fromkeys(values):
                request(mode, socket, work, value)
            startup[mode + '-' + work + '-first_sweep_seconds'] = time.perf_counter() - started
            for jobs in dict.fromkeys((1, args.jobs)):
                for repeat in range(args.rounds):
                    ordered = list(values)
                    random.Random(repeat).shuffle(ordered)
                    def timed(value):
                        start = time.perf_counter()
                        request(mode, socket, work, value)
                        return time.perf_counter() - start
                    started = time.perf_counter()
                    outcomes = run_tasks([Task(str(i), lambda value=value: timed(value))
                                          for i, value in enumerate(ordered)], jobs=jobs, report=False)
                    wall = time.perf_counter() - started
                    latencies = [row.value for row in outcomes]
                    row = dict(mode=mode, work=work, jobs=jobs, round=repeat + 1,
                               requests=len(values), wall_seconds=wall, requests_per_second=len(values) / wall,
                               median_ms=statistics.median(latencies) * 1000,
                               p95_ms=sorted(latencies)[int((len(latencies) - 1) * .95)] * 1000)
                    rows.append(row)
                    (args.results / 'measurements.json').write_text(json.dumps(rows, indent=2) + '\n')
                    print(f'{mode} {work} jobs={jobs} round={repeat + 1}: {wall:.3f}s, '
                          f'{row["requests_per_second"]:.1f} requests/s', flush=True)
        metadata[mode + '-after'] = json.loads(request(mode, socket, 'meta'))

    for mode in args.modes:
        if mode.startswith('cli-'):
            measure(mode, None)
        else:
            cache = int(mode.rsplit('-', 1)[1])
            with fpm_pool(args, cache) as (socket, elapsed):
                startup[f'fpm-{cache}-start_ms'] = elapsed
                measure(mode, socket)
    cache_files = {mode: dict(files=len(list(path.rglob('*.bin'))),
                             bytes=sum(p.stat().st_size for p in path.rglob('*.bin')))
                   for mode, path in cache_paths.items()}
    if any(info['files'] == 0 for info in cache_files.values()):
        raise RuntimeError('A requested persistent OPcache produced no cache files')
    (args.results / 'summary.json').write_text(json.dumps(dict(
        metadata=metadata, startup=startup, rounds=args.rounds, jobs=args.jobs,
        output_parity=True, cache_files=cache_files, measurements=rows), indent=2) + '\n')


if __name__ == '__main__':
    main()
