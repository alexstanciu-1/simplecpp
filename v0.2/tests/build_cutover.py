#!/usr/bin/env python3
"""Source-only Rust-generator cutover and rebuild proof. Edits only a temporary export."""
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import time

source = Path(__file__).resolve().parents[1]
report = {}
with tempfile.TemporaryDirectory(prefix='scpp-rust-cutover-') as temporary:
    root = Path(temporary) / 'v0.2'
    shutil.copytree(source, root, ignore=shutil.ignore_patterns('build', 'target', 'generated', '__pycache__'))
    assert not list((root / 'tools/api_generator').glob('*.cpp'))
    assert not (root / 'tools/support/json/pull_reader.hpp').exists()
    env = os.environ.copy()
    env.pop('CARGO_TARGET_DIR', None)

    def run(label, command):
        start = time.perf_counter()
        result = subprocess.run(command, cwd=root, env=env, capture_output=True, text=True)
        report[label + '_seconds'] = round(time.perf_counter() - start, 3)
        if result.returncode:
            raise RuntimeError(label + '\n' + result.stdout + result.stderr)
        return result.stdout

    run('configure', ['cmake', '--preset', 'default'])
    run('generator_build_and_probe', ['cmake', '--build', '--preset', 'default', '--target', 'scpp_api_generator'])
    generator = root / 'build/clang/scpp_api_generator'
    runtime_json = root / 'runtime/metadata/runtime.json'
    publication = root / 'runtime/metadata/publication.json'
    run('generation_only', [str(generator), str(runtime_json), str(publication), str(root / 'build/staging')])
    run('runtime_and_consumers_build', ['cmake', '--build', '--preset', 'default'])
    run('source_only_ctest', ['ctest', '--preset', 'default'])
    generated = root / 'build/clang/generated'
    def stamps():
        paths = [p for p in generated.rglob('*') if p.is_file()]
        paths += list((root / 'build/clang').rglob('*.o'))
        return {str(p): p.stat().st_mtime_ns for p in paths}
    before = stamps()
    log = run('unchanged_build', ['cmake', '--build', '--preset', 'default'])
    assert before == stamps(), 'unchanged build regenerated artifacts or compiled objects'
    assert 'Generating ' not in log and 'Building CXX' not in log
    report['unchanged_build'] = 'no generation or C++ compilation'

    output = generated / 'prepared.json'
    for label, path in [('metadata', runtime_json), ('publication', publication),
                        ('rust_source', root / 'tools/api_generator/src/emission.rs')]:
        before = output.stat().st_mtime_ns
        with path.open('a') as stream:
            stream.write('\n// rebuild probe\n' if path.suffix == '.rs' else '\n')
        run(label + '_invalidation', ['cmake', '--build', '--preset', 'default', '--target', 'scpp_runtime_publication'])
        assert output.stat().st_mtime_ns != before, label + ' did not invalidate generation'
    context = root / 'build/clang/native-layout/context.json'
    old_context = json.loads(context.read_text())['context_id']
    before = output.stat().st_mtime_ns
    run('changed_target_configure', ['cmake', '--preset', 'default',
        '-DCMAKE_CXX_FLAGS=-stdlib=libc++ -Wall -Wextra -Wpedantic -DSCPP_CUTOVER_TEST=1'])
    run('target_invalidation', ['cmake', '--build', '--preset', 'default', '--target', 'scpp_runtime_publication'])
    assert json.loads(context.read_text())['context_id'] != old_context
    assert output.stat().st_mtime_ns != before
    report['dependency_invalidation'] = ['metadata', 'publication', 'rust_source', 'target_flags']
    run('production_configure', ['cmake', '--preset', 'default', '-DBUILD_TESTING=OFF'])
    run('production_build', ['cmake', '--build', '--preset', 'default'])
    assert run('production_demo', [str(root / 'build/clang/scpp_runtime_demo')]).strip() == 'Ada: 21; ratio=7'
print(json.dumps(report, indent=2))
