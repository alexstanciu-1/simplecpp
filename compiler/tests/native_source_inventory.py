"""Compare native snapshot and streaming digests with the production PHP inventory."""
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[2]
REFERENCE = r'''
require $argv[1] . '/compiler/boot.php';
$session = (new scpp\compiler\Module_Loader())->load($argv[2], $argv[3] ?? null);
$rows = [];
foreach ($session->entry->sources as $source) {
    $key = $session->source_key($source);
    $path = $argv[2] . '/' . $source->path;
    $digest = scpp\compiler\Project_Changes::digest($path);
    if ($digest !== hash('sha256', file_get_contents($path))) throw new RuntimeException($path);
    $rows[$key] = ['flavor' => $source->flavor->value, 'sha256' => $digest];
}
$bundle = scpp\compiler\Cpp_Program::assemble($session->entry_key(), array_fill_keys(array_keys($rows), ''));
foreach ($bundle->source_units as $key => $unit) $rows[$key]['unit'] = $unit;
echo json_encode($rows, JSON_THROW_ON_ERROR);
'''


def verify(app, exe):
    def native(*args):
        return subprocess.run([exe, 'inventory', app, *args], capture_output=True, timeout=5)

    def compare(output=None):
        # Run native first with a poisoned legacy inventory to catch any PHP handoff.
        (app/'module.sources.json').write_text('invalid stale inventory')
        actual = []
        for args in [(), ('--from-bytes',)]:
            result = native(*args, *(['--output-directory', output] if output else []))
            assert result.returncode == 0, result.stderr
            actual.append(json.loads(result.stdout))
        expected = json.loads(subprocess.check_output(
            ['php', '-r', REFERENCE, ROOT, app, *([output] if output else [])]))
        for rows in actual:
            assert list(rows.items()) == list(expected.items()), (rows, expected)
        return expected

    def reject():
        result = native()
        assert result.returncode and result.stderr and not result.stdout
        oracle = subprocess.run(['php', '-r', REFERENCE, ROOT, app], capture_output=True)
        assert oracle.returncode and oracle.stderr

    (app/'module.json').write_text('{"name":"sample"}')
    (app/'main.php').write_text('return 0;')
    (app/'empty.js').touch()
    path = app/'binary.py'
    # Two whole 64 KiB reads plus a short final read, including NUL and high bytes.
    original = bytes(range(256)) * 512 + b'\0end'
    path.write_bytes(original)
    first = compare()
    key = 'sample/binary.py'
    assert set(first) == {key, 'sample/empty.js', 'sample/main.php'}
    assert first['sample/empty.js']['sha256'] == 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'
    stat = path.stat()
    path.write_bytes(original[:-1] + b'!')
    os.utime(path, ns=(stat.st_atime_ns, stat.st_mtime_ns))
    edited = compare()
    assert edited[key]['sha256'] != first[key]['sha256']
    assert edited[key]['unit'] == first[key]['unit']

    path.unlink()
    removed = compare()
    assert removed == {k: v for k, v in first.items() if k != key}
    path.write_bytes(original)
    assert compare() == first
    for directory in ['src', '.cache', 'BUILD', 'Generated', 'vendor', 'NODE_MODULES',
                      'nested', 'dep', 'inner', 'plugin', 'out']:
        (app/directory).mkdir()
        (app/directory/'unit.cpp').touch()
    (app/'nested/module.json').write_text('{}')
    (app/'dep/custom.json').write_text(json.dumps({
        'name': 'dep', 'dependencies': {'inner': {'path': '../inner/custom.json'}},
        'modules': {'plugin': '../plugin/custom.json'}}))
    (app/'inner/custom.json').write_text('{"name":"inner"}')
    (app/'plugin/custom.json').write_text('{"name":"plugin"}')
    (app/'src.php').touch()
    (app/'src/unit.CPP').touch()
    (app/'.hidden.php').touch()
    (app/'link.js').symlink_to(path)
    (app/'cycle').symlink_to(app, target_is_directory=True)
    manifest = app/'module.json'
    manifest.write_text(json.dumps({'dependencies': {'dep': {'path': 'dep/custom.json', 'modules': ['plugin']}}}))
    rows = compare(app/'out')
    assert [key.removeprefix(app.name + '/') for key in rows] == [
        'binary.py', 'empty.js', 'main.php', 'src.php', 'src/unit.cpp']
    manifest.write_text(json.dumps({'sources': ['src/unit.cpp', 'main.php', 'empty.js']}))
    assert len(compare()) == 3
    (app/'input.custom').touch()
    manifest.write_text(json.dumps({'name': 'mapped', 'entry': 'input.custom', 'sources': {
        'src/unit.cpp': 'py', 'input.custom': 'js', '.hidden.php': 'cpp'}}))
    assert {key: row['flavor'] for key, row in compare().items()} == {
        'mapped/.hidden.php': 'cpp', 'mapped/input.custom': 'js', 'mapped/src/unit.cpp': 'py'}
    (app/'main').touch()
    manifest.write_text(json.dumps({'sources': {'main': 'php'}}))
    assert len(compare()) == 1
    manifest.write_text(json.dumps({'sources': {'main': 'php', 'main.php': 'php'}}))
    reject()
    for sources in [['main.php', 'main.php'], ['main.php', 'missing.php'],
                    ['main.php', '../escape.php'], ['main.php', 'binary.py', 'link.js'],
                    ['main.php', 'unsupported.extension'], {'main.php': 'unknown'}]:
        manifest.write_text(json.dumps({'sources': sources}))
        reject()
    print('Discovery, lists, flavor overrides, exclusions, ordering and digest edits matched PHP.')


if __name__ == '__main__':
    with tempfile.TemporaryDirectory() as tmp:
        verify(Path(tmp), Path(sys.argv[1]).resolve(strict=True))
