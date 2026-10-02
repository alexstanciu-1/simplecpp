"""Private, reusable FPM execution for host PHP scripts (not CLI emulation)."""
import atexit
import json
import os
from pathlib import Path
import pwd
import queue
import re
import shutil
import signal
import subprocess
import tempfile
import threading
import time


class PhpExecutor:
    def __init__(self, logs, jobs=12, mode=None):
        mode = mode or os.environ.get('MY_TRY_PHP_EXECUTOR', 'auto')
        if mode not in ('auto', 'fpm', 'cli'):
            raise ValueError('PHP executor must be auto, fpm or cli')
        self.logs, self.jobs, self.mode = Path(logs), jobs, mode
        self.lock = threading.Lock()
        self.pools = {}
        atexit.register(self.close)

    def select(self, command, cli_reason=None):
        if not re.fullmatch(r'php(?:\d+(?:\.\d+)*)?', Path(command[0]).name):
            return None, 'subprocess'
        if cli_reason or self.mode == 'cli':
            return None, cli_reason or 'CLI explicitly selected'
        if len(command) < 2 or (command[1].startswith('-') and command[1] != '-r'):
            return None, 'PHP CLI option/interactive contract'
        with self.lock:
            binary = shutil.which(command[0]) or command[0]
            if binary not in self.pools:
                try:
                    self.pools[binary] = FpmPool(binary, self.logs / f'fpm-{len(self.pools)}', self.jobs)
                except (OSError, RuntimeError, subprocess.SubprocessError) as error:
                    if self.mode == 'fpm':
                        raise
                    self.pools[binary] = str(error)
                    print(f'PHP executor: CLI fallback: {error}', flush=True)
            pool = self.pools[binary]
        if isinstance(pool, str):
            return None, pool
        return pool, 'private FPM pool with OPcache'

    def close(self):
        for pool in self.pools.values():
            if not isinstance(pool, str):
                pool.close()
        atexit.unregister(self.close)


class FpmPool:
    def __init__(self, php, logs, jobs):
        self.process = None
        self.temporary = None
        self.log = None
        self.slots = queue.Queue()
        for slot in range(jobs):
            self.slots.put(slot)
        logs.mkdir(parents=True, exist_ok=True)
        try:
            metadata = json.loads(subprocess.check_output([php, '-r',
                'echo json_encode([PHP_VERSION, php_ini_loaded_file(), php_ini_scanned_files()]);'], timeout=10))
            version, ini, scanned = metadata
            short = '.'.join(version.split('.')[:2])
            candidates = [shutil.which('php-fpm' + short), '/usr/sbin/php-fpm' + short,
                          shutil.which('php-fpm')]
            fpm = next((p for p in candidates if p and Path(p).is_file()), None)
            self.client = shutil.which('cgi-fcgi')
            if not fpm or not self.client:
                raise RuntimeError('matching php-fpm or cgi-fcgi is not installed')
            reported = subprocess.check_output([fpm, '-v'], stderr=subprocess.STDOUT, timeout=10).decode()
            if not reported.startswith('PHP ' + version + ' '):
                raise RuntimeError('PHP CLI/FPM versions differ')
            self.temporary = tempfile.TemporaryDirectory(prefix='my-try-fpm-')
            self.folder = Path(self.temporary.name)
            self.sockets = [self.folder / f'{slot}.sock' for slot in range(jobs)]
            config = self.folder / 'fpm.conf'
            configuration = f'[global]\nerror_log = {self.folder / "error.log"}\ndaemonize = no\n'
            # Each leased slot has one worker: cancellation cannot hit a worker that
            # has already accepted a different request. Pools share one OPcache.
            for slot, socket in enumerate(self.sockets):
                configuration += f'''[runner{slot}]
user = {pwd.getpwuid(os.getuid()).pw_name}
listen = {socket}
listen.mode = 0600
pm = static
pm.max_children = 1
clear_env = no
catch_workers_output = yes
security.limit_extensions = .php
'''
            config.write_text(configuration)
            # Match the CLI's loaded extension configuration, with Xdebug disabled.
            scan_dirs = list(dict.fromkeys(str(Path(p.strip()).parent)
                                         for p in (scanned or '').split(',') if p.strip()))
            environment = dict(os.environ, XDEBUG_MODE='off', PHP_INI_SCAN_DIR=os.pathsep.join(scan_dirs))
            settings = ['opcache.enable=1', 'opcache.jit=disable', 'opcache.validate_timestamps=1',
                        'opcache.revalidate_freq=0', 'opcache.file_update_protection=2',
                        'display_errors=0', 'log_errors=1', 'html_errors=0', 'max_execution_time=0',
                        'output_buffering=0', 'auto_prepend_file=', 'auto_append_file=']
            command = [fpm, '-F', '-y', str(config)] + (['-c', ini] if ini else ['-n'])
            for setting in settings:
                command.extend(['-d', setting])
            self.log = (logs / 'daemon.log').open('wb')
            self.process = subprocess.Popen(command, env=environment, stdout=self.log,
                                            stderr=self.log, start_new_session=True)
            deadline = time.monotonic() + 10
            while not all(socket.exists() for socket in self.sockets):
                if self.process.poll() is not None or time.monotonic() > deadline:
                    detail = (self.folder / 'error.log').read_text() if (self.folder / 'error.log').exists() else ''
                    raise RuntimeError('FPM startup failed: ' + detail.strip())
                time.sleep(.01)
            (logs / 'configuration.json').write_text(json.dumps(dict(command=command,
                version=version, scan_dirs=scan_dirs, jobs=jobs), indent=2))
            # Verify POSIX cancellation and request-specific sys_temp_dir before dispatching user work.
            with tempfile.TemporaryDirectory(prefix='fpm-health-') as temporary:
                out, err, code, failure = self.run([php, '-r',
                    'echo PHP_SAPI, "\\n", sys_get_temp_dir();'], Path.cwd(), Path(temporary), 10)
                if failure or code != 0 or out != ('fpm-fcgi\n' + temporary).encode() or err:
                    raise RuntimeError(f'FPM health check failed: {failure}: {out!r} {err!r}')
        except BaseException:
            self.close()
            raise

    def run(self, command, cwd, folder, timeout, input=None):
        slot = self.slots.get()
        try:
            return self._request(command, cwd, folder, timeout, self.sockets[slot], input)
        finally:
            self.slots.put(slot)

    def _request(self, command, cwd, folder, timeout, socket, input):
        (folder / 'stdin').write_bytes(input or b'')
        inline = command[1] == '-r'
        arguments = ['Standard input code', *command[3:]] if inline else command[1:]
        script = None if inline else str((cwd / command[1]).resolve())
        (folder / 'request.json').write_text(json.dumps(dict(cwd=str(cwd.resolve()), argv=arguments,
            script=script, code=command[2] if inline else None, temporary=str(folder))))
        entry = Path(__file__).with_name('php_request.php')
        environment = dict(os.environ, SCRIPT_FILENAME=str(entry), SCRIPT_NAME='/php_request.php',
            REQUEST_METHOD='GET', QUERY_STRING='', SERVER_PROTOCOL='HTTP/1.1',
            GATEWAY_INTERFACE='CGI/1.1', REDIRECT_STATUS='200', MY_TRY_REQUEST=str(folder),
            PHP_ADMIN_VALUE=f'sys_temp_dir={folder}\nerror_log={folder / "stderr"}',
            TMPDIR=str(folder), TMP=str(folder), TEMP=str(folder))
        error = None
        process = subprocess.Popen([self.client, '-bind', '-connect', str(socket)],
            env=environment, stdout=subprocess.PIPE, stderr=subprocess.PIPE, start_new_session=True)
        try:
            response, transport_error = process.communicate(timeout=timeout)
        except subprocess.TimeoutExpired:
            # Workers establish their own process group before publishing their PID.
            pidfile = folder / 'pid'
            if pidfile.exists():
                pid = int(pidfile.read_text())
                try:
                    os.killpg(pid, signal.SIGKILL)
                except ProcessLookupError:
                    pass
            else:
                # No acknowledged worker: stop this pool to prevent queued work running later.
                self.close()
            os.killpg(process.pid, signal.SIGKILL)
            response, transport_error = process.communicate()
            error = f'timeout after {timeout}s'
        result = folder / 'result.json'
        code = None
        if result.exists():
            status = json.loads(result.read_text())
            code, error = status['code'], error or status.get('error')
        if error is None and (process.returncode != 0 or not result.exists()):
            error = f'FPM request did not complete: {transport_error!r} {response[:500]!r}'
        headers = response.partition(b'\r\n\r\n')[0]
        if code == 0 and re.search(rb'Status: [45]\d\d', headers):
            code, error = 255, 'FPM returned an HTTP error after script completion'
        stdout = (folder / 'stdout').read_bytes() if (folder / 'stdout').exists() else b''
        stderr = (folder / 'stderr').read_bytes() if (folder / 'stderr').exists() else b''
        return stdout, stderr, code, error

    def close(self):
        if self.process is not None and self.process.poll() is None:
            self.process.terminate()
            try:
                self.process.wait(timeout=5)
            except subprocess.TimeoutExpired:
                os.killpg(self.process.pid, signal.SIGKILL)
                self.process.wait()
        if self.log is not None:
            self.log.close()
        if self.temporary is not None:
            self.temporary.cleanup()
