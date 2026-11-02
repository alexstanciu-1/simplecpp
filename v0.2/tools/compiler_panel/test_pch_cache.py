"""PCH reuse, dependency invalidation, missing artifacts and failed-build recovery in an isolated runtime tree."""
from pathlib import Path
import shutil
import tempfile

import native_build
from pch_cache import runtime_pch
from server import process

with tempfile.TemporaryDirectory(prefix='scpp-pch-proof-') as directory:
    root = Path(directory)
    shutil.copytree(native_build.ROOT/'runtime/generated/public',root/'runtime/generated/public')
    (root/'build/clang').mkdir(parents=True)
    (root/'build/clang/libscpp_runtime.a').symlink_to(native_build.ROOT/'build/clang/libscpp_runtime.a')
    native_build.ROOT = root
    cache = root/'cache'
    counter = 0
    def prepare(level='O2', validate=True):
        global counter
        counter += 1
        work = root/f'run-{counter}'
        work.mkdir()
        source = work/'main.cpp'
        source.write_text('#include <scpp/entry.hpp>\nint main(){return scpp::entry_exit_checked(8);}')
        with runtime_pch(cache, work, level) as (build_dir, result):
            command = native_build.write_build(build_dir, [source], level, True, validate_pch=validate)
            built = process(command, build_dir, 60)
            result['success'] = built['exit_code'] == 0
            if result['success']:
                assert process([build_dir/'program'], build_dir, 3)['exit_code'] == 8
                if not validate:
                    assert '-fno-validate-pch' in built['stdout']
            path = build_dir/'runtime.pch'
        assert not list(build_dir.glob('unit-*.o')) and not (build_dir/'program').exists()
        return (path if result['status'] != 'failed' else None), result
    path, first = prepare()
    assert first['status'] == 'built', first
    assert prepare(validate=False)[1]['status'] == 'reused'
    # Touch a transitive dependency, not the umbrella header. It must rebuild.
    header = root/'runtime/generated/public/scpp/entry.hpp'
    original = header.read_text()
    header.write_text(original+'\n// invalidation test\n')
    assert prepare(validate=False)[1]['status'] == 'rebuilt'
    assert prepare()[1]['status'] == 'reused'
    path.unlink()
    assert prepare()[1]['status'] == 'built'
    assert prepare('O1')[1]['status'] == 'built'
    assert prepare('O1')[1]['status'] == 'reused'
    header.write_text('#error deliberate cache failure\n')
    missing, failure = prepare(validate=False)
    assert missing is None and failure['status'] == 'failed', failure
    assert not path.exists(), 'Failed rebuild left a reusable artifact'
    header.write_text(original)
    assert prepare()[1]['status'] == 'built'
    assert prepare()[1]['status'] == 'reused'
print('Runtime PCH reuse, transitive dependency invalidation, optimization isolation and failure recovery passed')
