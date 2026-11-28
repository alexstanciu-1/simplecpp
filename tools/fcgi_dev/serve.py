"""Development FastCGI supervisor. One public Unix socket and resident compiler per app."""
import argparse
import asyncio
from dataclasses import dataclass
import itertools
import json
import os
from pathlib import Path
import shutil
import signal
import socket
import struct
import sys
import tempfile


@dataclass(eq=False)
class Worker:
    path: Path
    process: asyncio.subprocess.Process
    busy: bool = False


async def stop(process, group=False):
    def send(sig):
        try:
            if group:
                os.killpg(process.pid, sig)
            elif process.returncode is None:
                process.send_signal(sig)
        except ProcessLookupError:
            pass

    send(signal.SIGTERM)
    try:
        await asyncio.wait_for(process.wait(), 2)
    except TimeoutError:
        send(signal.SIGKILL)
        await process.wait()


async def pump(reader, writer):
    try:
        while chunk := await reader.read(65536):
            writer.write(chunk)
            await writer.drain()
    finally:
        if not writer.is_closing():
            writer.write_eof()


async def failure(reader, writer, status, message):
    # Consume one non-multiplexed request so closing the socket won't discard our reply.
    while True:
        header = await reader.readexactly(8)
        version, kind, request, length, pad, _ = struct.unpack('!BBHHBB', header)
        if version != 1 or not request:
            return
        await reader.readexactly(length + pad)
        if kind == 5 and not length:
            break
    body = message.encode(errors='replace')[:16000]
    headers = f'Status: {status}\r\nContent-Type: text/plain\r\nContent-Length: {len(body)}\r\n\r\n'.encode()
    for kind, data in [(6, headers + body), (6, b''), (3, bytes(8))]:
        writer.write(struct.pack('!BBHHBB', 1, kind, request, len(data), 0, 0) + data)
    await writer.drain()


class Supervisor:
    def __init__(self, args, work):
        self.args = args
        self.work = work
        self.serial = itertools.count()
        self.compiler = None
        self.build = None
        self.active = []
        self.workers = set()
        self.retiring = {}
        self.clients = set()
        self.changed = asyncio.Condition()
        self.version = None

    async def compile(self):
        if self.compiler is None or self.compiler.returncode is not None:
            self.compiler = await asyncio.create_subprocess_exec(
                self.args.php, Path(__file__).with_name('compiler.php'), self.args.app,
                self.work, self.args.toolchain, stdin=asyncio.subprocess.PIPE,
                stdout=asyncio.subprocess.PIPE, start_new_session=True)
        try:
            async with asyncio.timeout(self.args.build_timeout):
                self.compiler.stdin.write(b'build\n')
                await self.compiler.stdin.drain()
                reply = json.loads(await self.compiler.stdout.readline())
        except BaseException:
            await stop(self.compiler, group=True)
            self.compiler = None
            raise
        if 'error' in reply:
            raise RuntimeError(reply['error'])
        return reply['ready']

    async def retire(self, worker):
        await stop(worker.process)
        worker.path.unlink(missing_ok=True)
        self.workers.discard(worker)

    def start_retirement(self, worker):
        if worker not in self.retiring:
            task = asyncio.create_task(self.retire(worker))
            self.retiring[worker] = task

            def finished(done):
                del self.retiring[worker]
                if not done.cancelled() and (error := done.exception()):
                    print(f'worker retirement failed for {worker.path}: {error}', file=sys.stderr)

            task.add_done_callback(finished)
        return self.retiring[worker]

    async def refresh(self):
        version = await self.compile()
        if version == self.version and all(w.process.returncode is None for w in self.active):
            return
        native = self.work / 'native'
        # The linker may replace program. All children exec an independent inode.
        image = native / f'worker-{next(self.serial)}'
        await asyncio.to_thread(shutil.copy2, native / 'program', image)
        fresh = []
        try:
            for _ in range(self.args.workers):
                path = self.work / f'w{next(self.serial)}.sock'
                process = await asyncio.create_subprocess_exec(image, path, stdin=asyncio.subprocess.DEVNULL)
                worker = Worker(path, process)
                self.workers.add(worker)
                fresh.append(worker)
            async with asyncio.timeout(self.args.start_timeout):
                while True:
                    if any(w.process.returncode is not None for w in fresh):
                        raise RuntimeError('Application worker exited during startup')
                    if all(w.path.is_socket() for w in fresh):
                        break
                    await asyncio.sleep(.01)
        except BaseException:
            await asyncio.gather(*(self.retire(w) for w in fresh))
            raise
        finally:
            image.unlink(missing_ok=True)
        async with self.changed:
            old, self.active = self.active, fresh
            self.version = version
            idle = [w for w in old if not w.busy]
            self.changed.notify_all()
        print(f'active {version} workers={len(fresh)}', file=sys.stderr, flush=True)
        for worker in idle:
            self.start_retirement(worker)

    async def acquire(self):
        if self.build is None or self.build.done():
            self.build = asyncio.create_task(self.refresh())
        await asyncio.shield(self.build)
        async with self.changed:
            while True:
                for worker in self.active:
                    if not worker.busy and worker.process.returncode is None:
                        worker.busy = True
                        return worker
                if all(w.process.returncode is not None for w in self.active):
                    raise RuntimeError('Application workers exited')
                await self.changed.wait()

    async def handle(self, reader, writer):
        task = asyncio.current_task()
        full = len(self.clients) >= self.args.max_clients
        self.clients.add(task)
        worker = backend = upload = None
        try:
            try:
                if full:
                    raise TimeoutError('Worker queue full')
                async with asyncio.timeout(self.args.wait_timeout):
                    worker = await self.acquire()
                    upstream, backend = await asyncio.open_unix_connection(worker.path)
            except Exception as exc:
                status = '503 Service Unavailable' if isinstance(exc, TimeoutError) else '500 Internal Server Error'
                async with asyncio.timeout(5):
                    await failure(reader, writer, status, str(exc) or status)
                return
            upload = asyncio.create_task(pump(reader, backend))
            try:
                await pump(upstream, writer)
            finally:
                upload.cancel()
                await asyncio.gather(upload, return_exceptions=True)
        except (ConnectionError, asyncio.IncompleteReadError, TimeoutError):
            pass
        finally:
            if backend:
                backend.close()
            if worker:
                async with self.changed:
                    worker.busy = False
                    retired = worker not in self.active
                    self.changed.notify_all()
                if retired:
                    self.start_retirement(worker)
            writer.close()
            self.clients.discard(task)

    async def close(self):
        for task in self.clients:
            task.cancel()
        await asyncio.gather(*self.clients, return_exceptions=True)
        if self.build:
            self.build.cancel()
            await asyncio.gather(self.build, return_exceptions=True)
        await asyncio.gather(*(self.start_retirement(w) for w in list(self.workers)))
        if self.compiler:
            await stop(self.compiler, group=True)


async def serve(args):
    done = asyncio.Event()
    for sig in (signal.SIGTERM, signal.SIGINT):
        asyncio.get_running_loop().add_signal_handler(sig, done.set)
    with tempfile.TemporaryDirectory(prefix='scpp-fcgi-') as folder, socket.socket(socket.AF_UNIX) as sock:
        supervisor = Supervisor(args, Path(folder))
        sock.bind(str(args.socket))
        identity = args.socket.stat()
        try:
            args.socket.chmod(0o660)
            sock.listen()
            sock.setblocking(False)
            server = await asyncio.start_unix_server(supervisor.handle, sock=sock)
            async with server:
                await done.wait()
        finally:
            await supervisor.close()
            if args.socket.exists() and os.path.samestat(identity, args.socket.stat()):
                args.socket.unlink()


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--app', type=Path, required=True)
    parser.add_argument('--socket', type=Path, required=True)
    parser.add_argument('--php', default='php')
    parser.add_argument('--toolchain', default='')
    for name, default in [('workers', 2), ('max-clients', 32), ('build-timeout', 120),
                          ('start-timeout', 10), ('wait-timeout', 150)]:
        parser.add_argument('--' + name, type=int, default=default)
    args = parser.parse_args()
    if min(args.workers, args.max_clients, args.build_timeout, args.start_timeout, args.wait_timeout) < 1:
        parser.error('counts and timeouts must be positive')
    asyncio.run(serve(args))
