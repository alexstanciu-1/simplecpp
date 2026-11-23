"""Exercise Python async declarations, timer suspension, and cross-file waits in native code."""
import tempfile
from pathlib import Path

from app_namespace_native import artifacts, invoke, service
from async_native import build
from exceptions_native import fresh_build
from extends_native import source_edit
from parent_methods_native import write_app


CHILD = """async def child(n: int) -> int:
    await async_sleep_ms(1)
    return n
"""
PARENT = """def pair(first: int, second: int) -> int:
    return first * 10 + second

async def divide(n: int) -> int:
    return 8 // n

async def parent() -> int:
    await child(0)
    items: mixed = [await child(5)]
    table: dynamic = {"key": await child(5)}
    if await child(5) not in items or "key" not in table:
        return 90
    if 2 < 1 < await divide(0):
        return 92
    if not (0 < await child(3) < await child(5)):
        return 91
    return pair(second=await child(2), first=await child(1))
"""


def prove(work):
    app, output = work / 'app', work / 'output'
    write_app(app, 'py', {'main': 'return await parent()\n', 'parent': PARENT, 'child': CHILD})
    resident = service(app, output)
    try:
        for expected in (12, 23):
            if expected == 23:
                source_edit(app / 'child.py', CHILD.replace('async def', 'def').replace('    await async_sleep_ms(1)\n', ''))
                error = invoke(resident)['error']
                assert 'requires an async function' in error and error == fresh_build(app, work / 'invalid')['error'], error
                source_edit(app / 'child.py', CHILD.replace('return n', 'return n + 1'))
            result = invoke(resident)
            assert 'error' not in result, result
            fresh = work / f'fresh-{expected}'
            result = fresh_build(app, fresh)
            assert 'error' not in result, result
            files = artifacts(output)
            assert files == artifacts(fresh)
            units = [text for path, text in files.items() if path.suffix == '.cpp']
            assert any(b'co_await ::scpp::async::delay_ms(' in text for text in units)
            assert any(b'co_await child' in text for text in units)
            fixture = {'files': {p.as_posix(): t.decode() for p, t in files.items()},
                       'exit': expected, 'stdout_hex': '', 'stderr': '', 'compiler_ms': 0}
            stats = build(work / 'native', fixture)
            if expected == 23:
                assert stats['objects'] == 1, stats
    finally:
        resident.stdin.close()
        assert resident.wait() == 0


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-python-async-') as directory:
        prove(Path(directory))
