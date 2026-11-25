"""Build and run the vector bundle through a native Simple C++ process driver on Linux."""
from contextlib import closing
import json
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile

from translated_vector_recipe import ROOT, build, fresh, vector_bundles


def verify(work, cxx, linker):
    tool = work/'driver'
    tool.mkdir()
    modules = ['filesystem', 'io', 'json', 'process', 'datetime']
    (tool/'module.json').write_text(json.dumps({'general_api': True, 'dependencies': {'runtime': {'modules': modules}}}))
    shutil.copyfile(ROOT/'compiler/examples/native_driver/main.php.scpp', tool/'main.php')
    bundle = work/'driver-out'
    manifest = fresh(tool, bundle)
    driver = work/'driver-executable'
    build(bundle, driver, cxx, linker, manifest['link_inputs'])

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
