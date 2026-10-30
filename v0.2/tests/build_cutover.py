#!/usr/bin/env python3
"""Source-only PHP-generator cutover and rebuild proof. Edits only a temporary export."""
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import time

source = Path(__file__).resolve().parents[1]
report = {}
with tempfile.TemporaryDirectory(prefix='scpp-php-cutover-') as temporary:
    root = Path(temporary) / 'v0.2'
    shutil.copytree(source, root, ignore=shutil.ignore_patterns('build', 'target', 'generated', '__pycache__'))
    assert not list(root.rglob('*.rs'))
    shutil.rmtree(root / 'compiler')
    for path in [root / 'Cargo.toml', root / 'Cargo.lock', root / 'rust-toolchain.toml']:
        assert not path.exists(), f'Retired Rust configuration remains: {path}'
    assert not (root / '.cargo').exists()
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
    run('source_only_ctest', ['ctest', '--preset', 'default', '-j', '8'])
    generated = root / 'runtime/generated'
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
                        ('php_source', root / 'tools/api_generator/php/render.php'),
                        ('cpp_template', root / 'tools/api_generator/templates/integer_storage.cpp.tpl')]:
        before = output.stat().st_mtime_ns
        with path.open('a') as stream:
            stream.write('\n// rebuild probe\n' if path.suffix in ('.php', '.tpl') else '\n')
        run(label + '_invalidation', ['cmake', '--build', '--preset', 'default', '--target', 'scpp_runtime_publication'])
        changed = output.stat().st_mtime_ns != before
        assert changed == (label == 'cpp_template'), label + ' did not respect output freshness'
    # A real publication change must replace the shared API, not merely check it.
    original = publication.read_text()
    request = json.loads(original)
    request['operators'].pop()
    original_plan = output.read_text()
    publication.write_text(json.dumps(request))
    before = output.stat().st_mtime_ns
    run('publication_change', ['cmake', '--build', '--preset', 'default', '--target', 'scpp_runtime_publication'])
    assert output.stat().st_mtime_ns != before
    assert output.read_text() != original_plan
    publication.write_text(original)
    run('publication_restore', ['cmake', '--build', '--preset', 'default', '--target', 'scpp_runtime_publication'])
    context = root / 'build/clang/native-layout/context.json'
    old_context = json.loads(context.read_text())['context_id']
    before = output.stat().st_mtime_ns
    run('changed_target_configure', ['cmake', '--preset', 'default',
        '-DCMAKE_CXX_FLAGS=-stdlib=libc++ -Wall -Wextra -Wpedantic -DSCPP_CUTOVER_TEST=1'])
    run('target_invalidation', ['cmake', '--build', '--preset', 'default', '--target', 'scpp_runtime_publication'])
    assert json.loads(context.read_text())['context_id'] != old_context
    assert output.stat().st_mtime_ns == before, 'A layout-neutral flag should preserve identical generated output'
    report['target_context'] = 'changed flags re-probed; identical output preserved'
    report['unchanged_output_inputs'] = ['metadata whitespace', 'publication whitespace', 'PHP comment']
    report['dependency_invalidation'] = ['publication operators', 'cpp_template']
    # Another build tree can replace the shared publication with different demand.
    alternate = root / 'build/alternate'
    run('alternate_configure', ['cmake', '--preset', 'default', '-B', str(alternate),
        '-DBUILD_TESTING=OFF', '-DSCPP_ENABLE_JSON=ON'])
    run('alternate_publication', ['cmake', '--build', str(alternate), '--target', 'scpp_runtime_publication'])
    assert (generated / 'public/scpp/json.hpp').exists()
    run('restore_primary_publication', ['cmake', '--build', '--preset', 'default', '--target', 'scpp_runtime_publication'])
    assert not (generated / 'public/scpp/json.hpp').exists()
    run('restored_freshness', [str(generator), str(runtime_json), str(root / 'build/clang/runtime-publication.json'), str(generated), '--check'])
    report['shared_publication'] = 'alternate profile replaced; original build restored its demand and target context'

    run('production_configure', ['cmake', '--preset', 'default', '-DBUILD_TESTING=OFF'])
    run('production_build', ['cmake', '--build', '--preset', 'default'])
    assert run('production_demo', [str(root / 'build/clang/scpp_runtime_demo')]).strip() == 'Ada: 21; ratio=7'
    run('datetime_configure', ['cmake', '--preset', 'default', '-DSCPP_ENABLE_DATETIME=ON'])
    run('datetime_build', ['cmake', '--build', '--preset', 'default'])
    assert run('datetime_demo', [str(root / 'build/clang/scpp_datetime_demo')]).strip() == '2024-02-29T12:34:56Z'
    core_symbols = run('core_symbols', ['nm', '-C', str(root / 'build/clang/libscpp_runtime.a')])
    assert 'scpp::dt::' not in core_symbols and 'scpp::detail::datetime::' not in core_symbols
    run('datetime_disable', ['cmake', '--preset', 'default', '-DSCPP_ENABLE_DATETIME=OFF'])
    run('core_only_build', ['cmake', '--build', '--preset', 'default'])
    targets = run('core_only_targets', ['ninja', '-C', 'build/clang', '-t', 'targets', 'all'])
    assert 'scpp_datetime:' not in targets
print(json.dumps(report, indent=2))
