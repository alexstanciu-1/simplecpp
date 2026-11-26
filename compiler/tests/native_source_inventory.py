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
$session = (new scpp\compiler\Module_Loader())->load($argv[2]);
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

    def compare():
        expected = json.loads(subprocess.check_output(['php', '-r', REFERENCE, ROOT, app]))
        for args in [(), ('--from-bytes',)]:
            result = native(*args)
            assert result.returncode == 0, result.stderr
            assert json.loads(result.stdout) == expected
        return expected

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
    # Before rediscovery, a stale selected path must fail without a partial report.
    for args in [(), ('--from-bytes',)]:
        result = native(*args)
        assert result.returncode != 0 and not result.stdout and result.stderr
    removed = compare()
    assert removed == {k: v for k, v in first.items() if k != key}
    path.write_bytes(original)
    assert compare() == first
    print('PHP and native inventories match for binary input, same-size edit, removal and re-addition.')


if __name__ == '__main__':
    with tempfile.TemporaryDirectory() as tmp:
        verify(Path(tmp), Path(sys.argv[1]).resolve(strict=True))
