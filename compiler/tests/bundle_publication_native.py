"""Production bundle rollback, held native readers, and unchanged object reuse."""
import fcntl
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import time

ROOT = Path(__file__).resolve().parents[2]
php, cxx, linker = sys.argv[1:]
ENV = {**os.environ, 'XDEBUG_MODE': 'off'}

PUBLISH = r'''
namespace scpp\compiler {
    function rename(string $from, string $to): bool {
        if ($GLOBALS['mode'] === 'fail' && $to === $GLOBALS['bundle'] . '/manifest.json') return false;
        return \rename($from, $to);
    }
}
namespace {
    require $argv[1];
    use scpp\compiler\{Cpp_Bundle, cpp_program_output};
    [$bundle, $mode, $started] = array_slice($argv, 2);
    $quiet = 'units/' . hash('sha256', 'quiet') . '.cpp';
    $old = 'units/' . hash('sha256', 'old') . '.cpp';
    $new = 'units/' . hash('sha256', 'new') . '.cpp';
    $files = ['entry.cpp' => $mode === 'initial'
        ? 'int quiet(); int old(); int main() { return quiet() + old(); }'
        : 'int quiet(); int next(); int main() { return quiet() + next(); }'];
    $files[$quiet] = 'int quiet() { return 3; }';
    if ($mode === 'initial') $files[$old] = 'int old() { return 5; }';
    else $files[$new] = 'int next() { return 6; }';
    $output = new cpp_program_output('app/main.cpp', ['app/main.cpp' => 'entry.cpp'], $files);
    if ($started !== '') file_put_contents($started, 'started');
    try {
        Cpp_Bundle::write($bundle, $output, []);
        if ($mode === 'fail') throw new \LogicException('Manifest rename was not obstructed');
    }
    catch (\RuntimeException $error) {
        if ($mode !== 'fail' || !str_contains($error->getMessage(), 'Cannot publish C++ output: ' . $bundle . '/manifest.json')) throw $error;
    }
}
'''
BUILD = r'''
require $argv[1];
require dirname($argv[1]) . '/host/native_build.php';
[$bundle, $build, $config] = array_slice($argv, 2);
$result = \scpp\compiler\Native_Build::run($bundle, $build, json_decode($config, true, flags: JSON_THROW_ON_ERROR));
if ($result['exit_code'] !== 0) throw new \RuntimeException(json_encode($result));
'''


def command(code, *args):
    return [php, '-r', code, str(ROOT / 'compiler/boot.php'), *map(str, args)]


def run(args, expected=0):
    result = subprocess.run(args, capture_output=True, timeout=50, env=ENV)
    assert result.returncode == expected, (result.returncode, result.stdout, result.stderr)


def snapshot(directory):
    return {p.relative_to(directory): (p.read_bytes(), p.stat().st_ino, p.stat().st_mtime_ns)
            for p in directory.rglob('*') if p.is_file()}


def wait_for(marker, process):
    limit = time.monotonic() + 30
    while not marker.exists():
        assert process.poll() is None, process.communicate()
        assert time.monotonic() < limit, marker
        time.sleep(0.01)


def finish(process):
    stdout, stderr = process.communicate(timeout=50)
    assert process.returncode == 0, (stdout, stderr)


with tempfile.TemporaryDirectory(prefix='scpp-production-bundle-') as temporary:
    work = Path(temporary)
    bundle, build = work / 'bundle', work / 'native'
    run(command(PUBLISH, bundle, 'initial', ''))
    initial = snapshot(bundle)

    run(command(PUBLISH, bundle, 'fail', ''))
    assert snapshot(bundle) == initial, 'Manifest failure failed to restore bytes or artifact identity'

    ready, release, writer_started = (work / name for name in ('ready', 'release', 'writer-started'))
    ninja = shutil.which('ninja')
    assert ninja is not None
    wrapper = work / 'ninja-wrapper'
    wrapper.write_text(f'''#!{sys.executable}
import os
from pathlib import Path
import sys
import time
Path({str(ready)!r}).touch()
limit = time.monotonic() + 30
while not Path({str(release)!r}).exists():
    if time.monotonic() >= limit:
        raise RuntimeError("Native reader was not released")
    time.sleep(0.01)
os.execv({ninja!r}, [{ninja!r}, *sys.argv[1:]])
''')
    wrapper.chmod(0o700)
    config = dict(cxx=cxx, flags=['-std=c++23', '-stdlib=libc++'], link_flags=[f'--ld-path={linker}'], ninja=str(wrapper))

    reader = subprocess.Popen(command(BUILD, bundle, build, json.dumps(config)), stdout=subprocess.PIPE,
                              stderr=subprocess.PIPE, env=ENV)
    writer = None
    try:
        wait_for(ready, reader)
        with (bundle / '.scpp-bundle.lock').open('r') as probe:
            try:
                fcntl.flock(probe, fcntl.LOCK_EX | fcntl.LOCK_NB)
            except BlockingIOError:
                pass
            else:
                raise AssertionError('Native host released the bundle before Ninja finished')
        writer = subprocess.Popen(command(PUBLISH, bundle, 'next', writer_started), stdout=subprocess.PIPE,
                                  stderr=subprocess.PIPE, env=ENV)
        wait_for(writer_started, writer)
        assert snapshot(bundle) == initial
        release.touch()
        finish(reader)
        finish(writer)
    finally:
        release.touch()
        for process in (reader, writer):
            if process is not None and process.poll() is None:
                process.kill()
                process.communicate(timeout=10)

    run([str(build / 'program')], expected=8)
    after = snapshot(bundle)
    quiet = Path('units') / (hashlib.sha256(b'quiet').hexdigest() + '.cpp')
    stale = Path('units') / (hashlib.sha256(b'old').hexdigest() + '.cpp')
    assert after[quiet] == initial[quiet]
    assert stale not in after
    assert all(not p.name.startswith('.scpp-entry-') for p in after)
    objects = {path.name: path.stat().st_mtime_ns for path in build.glob('*.o')}
    quiet_obj = 'unit-' + hashlib.sha256(os.fsencode(bundle / quiet)).hexdigest() + '.o'
    entry_obj = 'unit-' + hashlib.sha256(os.fsencode(bundle / 'entry.cpp')).hexdigest() + '.o'
    run(command(BUILD, bundle, build, json.dumps({**config, 'ninja': ninja})))
    run([str(build / 'program')], expected=9)
    assert (build / quiet_obj).stat().st_mtime_ns == objects[quiet_obj]
    assert (build / entry_obj).stat().st_mtime_ns != objects[entry_obj]

print('Production bundle rollback, reader lifetime, retirement, and native object reuse passed.')
