"""Native framework assembly ownership and no-op proofs; no native compilation needed."""
import json
from pathlib import Path
import shutil
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[2]
def run(command, ok=True):
    p = subprocess.run(command, text=True, capture_output=True)
    assert (p.returncode == 0) == ok, (command, p.stdout, p.stderr)
    return p

with tempfile.TemporaryDirectory(prefix='scpp-native-runtime-') as temp:
    base = Path(temp)
    tools = base / 'tools'
    shutil.copytree(ROOT / 'tools/php_portability', tools)
    # The policy hierarchy must match PHP's actual SPL class parents.
    run(['php', '-r', 'require $argv[1]; foreach (scpp\\portability\\Exception_Policy::TYPES as $php => [$native,$parent]) { '
        'if ($parent !== null && scpp\\portability\\Exception_Policy::name(chr(92).get_parent_class($php)) '
        '!== chr(92)."scpp_portability_".$parent) exit(1); }', str(tools / 'src/exception_policy.php')])
    source, output = base / 'php', base / 'phpp'
    source.mkdir()
    (source / 'main.php').write_text('<?php echo "ok";\n')
    run(['php', str(tools / 'sync_imports.php'), str(source)])
    convert = ['php', str(tools / 'convert.php'), str(source), str(output)]
    run(convert)
    manifest = (output / '.scpp-portability.json').read_bytes()
    install = ['php', str(tools / 'install_native_runtime.php'), str(output)]
    assert json.loads(run(install).stdout) == {'updated': 1}
    target = output / 'scpp_framework/exceptions.phs'
    runtime_manifest = output / '.scpp-native-runtime.json'
    stamp = target.stat().st_mtime_ns
    assert json.loads(run(install).stdout) == {'updated': 0}
    assert target.stat().st_mtime_ns == stamp
    assert (output / '.scpp-portability.json').read_bytes() == manifest
    assert json.loads(run(convert).stdout) == {'converted': 0, 'reused': 1, 'removed': 0}
    owned = target.read_bytes()
    recorded = runtime_manifest.read_bytes()
    # A second family must be preflighted before either family is published.
    strings = output / 'scpp_framework/strings.phs'
    string_bytes = strings.read_bytes()
    strings.write_text('user text-runtime edit')
    assert 'edited' in run(install, False).stderr
    assert target.read_bytes() == owned and runtime_manifest.read_bytes() == recorded
    strings.unlink()
    strings.symlink_to(target)
    assert 'Symlink' in run(install, False).stderr
    strings.unlink()
    strings.write_bytes(string_bytes)
    # Upgrade a previously installed exception-only framework without modifying it.
    old_hash = json.loads(recorded)['files']['exceptions.phs']
    runtime_manifest.write_text(json.dumps({'version': 1, 'exceptions_sha256': old_hash}))
    strings.unlink()
    (output / 'scpp_framework/collections.phs').unlink()
    assert json.loads(run(install).stdout) == {'updated': 1}
    assert strings.read_bytes() == string_bytes and target.read_bytes() == owned
    assert runtime_manifest.read_bytes() == recorded
    target.write_text('user edit')
    assert 'edited' in run(install, False).stderr
    assert target.read_text() == 'user edit' and runtime_manifest.read_bytes() == recorded
    target.write_bytes(owned)
    outside = base / 'outside.phs'
    outside.write_bytes(owned)
    target.unlink()
    target.symlink_to(outside)
    assert 'Symlink' in run(install, False).stderr
    assert outside.read_bytes() == owned
    target.unlink()
    target.write_bytes(owned)
    runtime_manifest.write_text('{}')
    assert 'Invalid native runtime manifest' in run(install, False).stderr
    assert target.read_bytes() == owned
    runtime_manifest.write_bytes(recorded)
    policy = tools / 'src/exception_policy.php'
    policy.write_text(policy.read_text().replace('// Owned by', '// Updated fixture. Owned by'))
    assert json.loads(run(install).stdout) == {'updated': 1}
    assert target.read_bytes() != owned
    assert (output / '.scpp-portability.json').read_bytes() == manifest
    # Optional OS adapters have independent ownership and require explicit selection.
    os_install = install + ['--os', '--filesystem']
    assert json.loads(run(os_install).stdout) == {'updated': 1}
    assert json.loads(run(os_install).stdout) == {'updated': 0}
    os_files = json.loads(runtime_manifest.read_text())['files']
    assert 'file_locks.phs' in os_files and 'processes.phs' in os_files and 'filesystem.phs' in os_files
    assert 'Invalid native runtime manifest' in run(install, False).stderr
    process_file = output / 'scpp_framework/processes.phs'
    process_bytes = process_file.read_bytes()
    process_file.write_text('user process edit')
    assert 'edited' in run(os_install, False).stderr
    process_file.write_bytes(process_bytes)
    install = os_install
    # Converter-owned source under the reserved directory must never be replaced.
    state = json.loads(manifest)
    state['files']['scpp_framework/other.php'] = state['files']['main.php']
    (output / '.scpp-portability.json').write_text(json.dumps(state))
    assert 'conflicts' in run(install, False).stderr
    (output / '.scpp-portability.json').write_bytes(manifest)
    runtime_manifest.unlink()
    current = target.read_bytes()
    assert 'unowned' in run(install, False).stderr
    assert target.read_bytes() == current
print('Native runtime assembly, independent ownership, safe rejection and no-op proofs passed.')
