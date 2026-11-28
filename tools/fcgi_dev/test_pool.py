"""Fast deterministic coverage for sharing one build check between simultaneous requests."""
import asyncio
from pathlib import Path
from tempfile import TemporaryDirectory
from types import SimpleNamespace
from unittest.mock import AsyncMock, patch

from serve import Supervisor, Worker


async def prove():
    pool = Supervisor(SimpleNamespace(), Path('.'))
    started = asyncio.Event()
    finish = asyncio.Event()
    checks = 0

    async def build():
        nonlocal checks
        checks += 1
        started.set()
        await finish.wait()
        pool.active = [Worker(Path(str(i)), SimpleNamespace(returncode=None)) for i in range(2)]

    pool.refresh = build
    first = asyncio.create_task(pool.acquire())
    await started.wait()
    second = asyncio.create_task(pool.acquire())
    await asyncio.sleep(0)
    assert not second.done()
    finish.set()
    left, right = await asyncio.gather(first, second)
    assert checks == 1 and left is not right and left.busy and right.busy


async def retirement():
    with TemporaryDirectory() as directory:
        work = Path(directory)
        (work / 'native').mkdir()
        (work / 'native/program').touch()
        pool = Supervisor(SimpleNamespace(workers=1, start_timeout=1), work)
        old = Worker(work / 'old', SimpleNamespace(returncode=None))
        pool.active = [old]
        pool.workers.add(old)
        retiring = asyncio.Event()
        finished = asyncio.Event()
        stopped = []

        async def retire(worker):
            stopped.append(worker)
            if worker is old:
                retiring.set()
                await finished.wait()
            pool.workers.remove(worker)

        pool.retire = retire
        pool.compile = AsyncMock(return_value='new')
        with patch('asyncio.to_thread', new=AsyncMock()), \
                patch('asyncio.create_subprocess_exec', new=AsyncMock(return_value=SimpleNamespace(returncode=None))), \
                patch.object(Path, 'is_socket', return_value=True):
            request = asyncio.create_task(pool.acquire())
            await asyncio.wait_for(retiring.wait(), 1)
            new = await asyncio.wait_for(request, 1)
        assert new is not old and new.busy
        closing = asyncio.create_task(pool.close())
        await asyncio.sleep(0)
        assert not closing.done()
        finished.set()
        await asyncio.wait_for(closing, 1)
        assert stopped.count(old) == stopped.count(new) == 1
        assert not pool.workers and not pool.retiring


asyncio.run(prove())
asyncio.run(retirement())
