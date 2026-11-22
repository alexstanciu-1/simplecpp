"""Check the complete dependencies of the synchronous and suspending fixture units."""
from pathlib import Path
import shlex
import subprocess
import sys

compiler, public = sys.argv[1:]
fixture = Path(__file__).resolve().parent
for unit in ('caller', 'parent', 'child'):
    result = subprocess.check_output([compiler, '-std=c++23', '-stdlib=libc++', '-I', public,
                                      '-M', str(fixture / (unit + '.cpp'))], text=True)
    paths = [Path(word) for word in shlex.split(result.replace('\\\n', ' '))[1:]]
    names = {path.name for path in paths}
    forbidden = {'async_core.hpp', 'mutex', 'thread', 'queue', 'condition_variable', 'chrono'}
    if unit == 'caller':
        forbidden.update({'coroutine', 'exception', 'async_int64.hpp'})
    elif 'async_int64.hpp' not in names:
        raise AssertionError(f'{unit}: missing concrete task header')
    if names & forbidden:
        raise AssertionError(f'{unit}: unexpected dependencies {names & forbidden}')
