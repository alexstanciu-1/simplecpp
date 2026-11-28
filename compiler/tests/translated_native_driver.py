"""Build and run the vector bundle through a native Simple C++ process driver on Linux."""
from contextlib import closing
import json
from pathlib import Path
import select
import shlex
import shutil
import subprocess
import sys
import tempfile
import time

from app_namespace_native import service

from translated_vector_recipe import ROOT, build, fresh, vector_bundles


def wait_until(check, description):
    deadline = time.monotonic() + 10
    while not check():
        assert time.monotonic() < deadline, description
        time.sleep(.01)


def verify_publication(work, driver, cxx, flags):
    app, bundle = work/'revision-app', work/'revision-bundle'
    app.mkdir()
    (app/'module.json').write_text(json.dumps({'general_api': True, 'dependencies': {
        'runtime': {'modules': ['filesystem', 'datetime']}}}))
    main = app/'main.phs'
    release_app = work/'release-app'
    main.write_text(f'while (!fs_exists("{release_app}")) {{ dt_sleep_ms(1); }} echo 1; return 0;')
    fresh(app, bundle)
    exe = work/'revision-executable'
    ready, resume = work/'clang-ready', work/'resume-clang'
    wrapper = work/'pause-clang'
    wrapper.write_text(f'#!/bin/sh\ntouch "{ready}"\nwhile [ ! -f "{resume}" ]; do sleep .01; done\n'
                       f'exec {shlex.quote(cxx)} "$@"\n')
    wrapper.chmod(0o755)
    args = [driver, bundle, exe]
    child = subprocess.Popen([*args, wrapper, *flags], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    writer = service(app, bundle)
    try:
        wait_until(ready.exists, 'Clang never entered')
        (app/'second.phs').write_text('function second(): int { return 2; }')
        main.write_text('echo second(); return 0;')
        writer.stdin.write('"run"\n')
        writer.stdin.flush()
        # Linux reports queued flock requests with the waiting PID and target inode.
        inode = (bundle/'.scpp-bundle.lock').stat().st_ino
        def publisher_waits():
            for line in Path('/proc/locks').read_text().splitlines():
                fields = line.split()
                if fields[1:6] == ['->', 'FLOCK', 'ADVISORY', 'WRITE', str(writer.pid)]:
                    if fields[6].rsplit(':', 1)[-1] == str(inode):
                        return True
            return False

        wait_until(publisher_waits, 'publisher never queued behind native build')
        resume.touch()
        assert select.select([writer.stdout], [], [], 10)[0], 'application retained the build lock'
        result = json.loads(writer.stdout.readline())
        assert 'error' not in result, result
        release_app.touch()
        stdout, stderr = child.communicate(timeout=10)
        assert (child.returncode, stdout, stderr) == (0, b'1', b''), (stdout, stderr)
    finally:
        resume.touch()
        release_app.touch()
        child.communicate(timeout=10)
        writer.stdin.close()
        writer.wait(timeout=10)
    assert subprocess.check_output([*args, cxx, *flags], timeout=10) == b'2'


def build_driver(work, cxx, linker):
    tool = work/'driver'
    tool.mkdir()
    modules = ['filesystem', 'io', 'json', 'process', 'datetime']
    (tool/'module.json').write_text(json.dumps({'general_api': True, 'dependencies': {'runtime': {'modules': modules}}}))
    shutil.copyfile(ROOT/'compiler/examples/native_driver/main.phs', tool/'main.phs')
    shutil.copyfile(ROOT/'compiler/examples/native_driver/build_inputs.phs', tool/'build_inputs.phs')
    bundle = work/'driver-out'
    manifest = fresh(tool, bundle)
    driver = work/'driver-executable'
    build(bundle, driver, cxx, linker, manifest['link_inputs'])

    return driver


def verify(work, cxx, linker):
    driver = build_driver(work, cxx, linker)
    project = work/'vector with spaces'
    project.mkdir()
    with closing(vector_bundles(project, cxx, linker)) as snapshots:
        source, links, expected = next(snapshots)
    vector_manifest = json.loads((source/'manifest.json').read_text())
    include = str(ROOT/'runtime/generated/public')
    flags = ['-std=c++23', '-stdlib=libc++', f'--ld-path={linker}', '-I', include]
    exe = work/'compiled vector'
    direct = work/'control'
    units = [source/u for u in vector_manifest['translation_units']]
    control = [cxx, *flags, *units, *links, '-o', direct]
    command = [driver, source, exe, cxx, *flags]
    subprocess.run(control, check=True)
    reference = subprocess.run([direct], capture_output=True)
    assert (reference.returncode, reference.stdout, reference.stderr) == (8, expected, b''), reference
    success = subprocess.run(command, capture_output=True, timeout=30)
    assert (success.returncode, success.stdout, success.stderr) == (8, expected, b''), success
    verify_publication(work, driver, cxx, flags)
    saved = exe.read_bytes()

    # Introduce a deterministic native compile failure after a successful publication.
    with units[0].open('ab') as output:
        output.write(b'\n#error native_driver_failure\n')
    failure = subprocess.run(control, capture_output=True)
    assert failure.returncode != 0 and b'native_driver_failure' in failure.stderr, failure
    actual = subprocess.run(command, capture_output=True, timeout=30)
    assert (actual.returncode, actual.stdout, actual.stderr) == (
        failure.returncode, failure.stdout, failure.stderr), actual
    assert exe.read_bytes() == saved and not list(work.glob('.scpp-stage-*'))


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-native-driver-') as tmp:
        verify(Path(tmp), *sys.argv[1:])
