"""Check the complete dependencies of the synchronous and suspending fixture units."""
from pathlib import Path
import shlex
import subprocess
import sys

compiler, public, tasks = sys.argv[1:]
fixture = Path(__file__).resolve().parent
for unit in ('caller', 'parent', 'child'):
    result = subprocess.check_output([compiler, '-std=c++23', '-stdlib=libc++', '-I', public, '-I', tasks,
                                      '-M', str(fixture / (unit + '.cpp'))], text=True)
    paths = [Path(word) for word in shlex.split(result.replace('\\\n', ' '))[1:]]
    names = {path.name for path in paths}
    forbidden = {'async_core.hpp', 'mutex', 'thread', 'queue', 'condition_variable', 'chrono'}
    if unit == 'caller':
        forbidden.update({'coroutine', 'frame.hpp', 'exception', 'int64.hpp'})
    elif 'int64.hpp' not in names:
        raise AssertionError(f'{unit}: missing concrete task header')
    if names & forbidden:
        raise AssertionError(f'{unit}: unexpected dependencies {names & forbidden}')

# The runtime owns the frame and timer; concrete tasks belong only to consumers.
assert (Path(public) / 'scpp/async/frame.hpp').is_file()
assert (Path(public) / 'scpp/async_timer.hpp').is_file()
assert not list((Path(public).parent / 'src').glob('async_*.cpp'))
assert sorted(p.name for p in (Path(public) / 'scpp').glob('async_*.hpp')) == ['async_timer.hpp']
