"""Exercise PHP filesystem names and the project-wide general surface in a mixed application."""
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile

root = Path(__file__).resolve().parents[2]
php, cxx, linker, filesystem, runtime = sys.argv[1:]
facts = Path(runtime).parent / 'native-layout/facts.json'
include = root / 'runtime/generated/public'


def compile_app(app, out, error=None):
    result = subprocess.run([php, root / 'compiler/main.php', 'project', app, facts, out], capture_output=True, text=True)
    if error:
        assert result.returncode and error in result.stderr, (error, result.stderr)
        return
    assert result.returncode == 0, result.stderr
    bundle = json.loads((out / 'manifest.json').read_text())
    return [out / unit for unit in bundle['translation_units']]


with tempfile.TemporaryDirectory(prefix='scpp-filesystem-family-') as folder:
    work = Path(folder)
    app = work / 'app'
    app.mkdir()
    manifest = {'dependencies': {'runtime': {'modules': ['filesystem']}}}
    config = app / 'module.json'
    config.write_text(json.dumps(manifest))
    for header, expected in [('scpp/filesystem/fs_is_dir.hpp', 'scpp/string/type.hpp'),
                             ('scpp/php/file_get_contents.hpp', 'scpp/filesystem/fs_get.hpp')]:
        source = work / 'header.cpp'
        source.write_text(f'#include <{header}>\n')
        flags = [cxx, '-std=c++23', '-stdlib=libc++', '-I', include]
        subprocess.run([*flags, '-fsyntax-only', source], check=True)
        closure = subprocess.check_output([*flags, '-M', source], text=True)
        assert expected in closure and 'filesystem_provider.hpp' not in closure and 'scpp/filesystem.hpp' not in closure
        assert 'scpp/js/' not in closure and 'scpp/py/' not in closure and 'runtime/src/' not in closure
        if 'fs_is_dir' in header:
            assert 'scpp/php/' not in closure and 'scpp/carriers.hpp' not in closure
    main = app / 'main.php'
    main.write_text(r'''
use function file_get_contents as read_bytes;
if (!mkdir("dir") || mkdir("dir") || !is_dir("dir")) { return 1; }
if (file_put_contents("dir/z", "b\0b") !== 3 || file_put_contents("dir/a", "") !== 0) { return 2; }
if (read_bytes("dir/z") !== "b\0b" || file_get_contents("dir/a") !== "") { return 3; }
if (isset(file_get_contents("absent")) || isset(file_put_contents("absent/f", ""))) { return 4; }
if (!file_exists("dir/z") || !is_file("dir/z") || filesize("dir/a") !== 0 || isset(filesize("absent"))) { return 5; }
if (!touch("dir/z") || filesize("dir/z") !== 3 || !touch("new") || !unlink("new")) { return 6; }
if (filemtime("stamp") !== 1000000000 || isset(filemtime("absent"))) { return 7; }
if (!copy("dir/z", "dir/a") || !rename("dir/a", "dir/b") || read_bytes("dir/b") !== "b\0b") { return 8; }
if (dirname("dir/b") !== "dir" || basename("dir/b") !== "b" || isset(realpath("absent"))) { return 9; }
if (!isset(realpath("dir")) || !is_link("dangling") || file_exists("dangling") || !unlink("dangling")) { return 10; }
if (rmdir("dir/z") || unlink("dir") || rmdir("dir") || !is_file("dir/z")) { return 11; }
$listing result_or_false<vector<string>> = scandir("dir");
if (!isset($listing) || isset(scandir("absent"))) { return 12; }
$names vector<string> = $listing;
if (count($names) !== 2 || $names[0] !== "b" || $names[1] !== "z") { return 13; }
if (!unlink("dir/b") || !unlink("dir/z") || unlink("dir") || !is_dir("dir")) { return 14; }
$empty vector<string> = scandir("dir");
if (count($empty) !== 0 || !rmdir("dir")) { return 15; }
if (is_file("stamp\0suffix") || file_exists("stamp\0suffix") || isset(realpath("stamp\0suffix"))) { return 16; }
if (dirname("leaf") !== "" || basename("dir/") !== "" || basename("dir/x\0b") !== "x\0b") { return 17; }
if (copy("absent", "stamp") || copy("stamp", "dir-link") || rmdir("dir-link")) { return 18; }
return snapshot_check();
''')
    (app / 'snapshot.cpp').write_text('''
int snapshot_check() {
    if (fs_is_windows() || !fs_mkdir("empty-dir") || !fs_remove("empty-dir") || fs_exists("empty-dir")) { return 19; }
    result<string> read = fs_read_snapshot("stamp", 1000000000, 0);
    if (!isset(read) || read !== "" || isset(fs_read_snapshot("stamp", 0, 0))
        || isset(fs_read_snapshot("stamp", 1000000000, 1))
        || isset(fs_read_snapshot("stamp-link", 1000000000, 0))) { return 20; }
    return 0;
}
''' if sys.platform.startswith('linux') else '''
int snapshot_check() {
    return isset(fs_read_snapshot("stamp", 1000000000, 0)) ? 20 : 0;
}
''')
    (work / 'dangling').symlink_to('missing')
    (work / 'target-dir').mkdir()
    (work / 'dir-link').symlink_to('target-dir')
    (work / 'stamp').touch()
    os.utime(work / 'stamp', (1000000000, 1000000000))
    (work / 'stamp-link').symlink_to('stamp')
    out = work / 'out'
    units = compile_app(app, out)
    generated = '\n'.join(unit.read_text() for unit in units)
    assert '::scpp::php::scandir(' in generated and '::scpp::fs::mkdir(' in generated
    assert '::scpp::php::unlink(' in generated and '::scpp::fs::read_snapshot(' in generated
    binary = work / 'family'
    subprocess.run([cxx, '-std=c++23', '-stdlib=libc++', f'--ld-path={linker}', '-I', include,
                    *units, filesystem, runtime, '-o', binary], check=True)
    subprocess.run([binary], cwd=work, check=True)

    if sys.platform.startswith('linux'):
        for filename in ('snapshot', 'replacement'):
            (work / filename).write_bytes(b'data')
            os.utime(work / filename, (1000000000, 1000000000))
        probe = work / 'snapshot-probe'
        subprocess.run([cxx, '-std=c++23', '-stdlib=libc++', f'--ld-path={linker}', '-I', include,
                        Path(__file__).with_name('filesystem_snapshot.cpp'), filesystem, runtime,
                        '-Wl,--wrap=read', '-o', probe], check=True)
        subprocess.run([probe], cwd=work, check=True)

    # A source declaration shadows the enabled builtin too.
    main.write_text('function fs_get(string $path): int {return 7;} return fs_get("ignored");')
    config.write_text(json.dumps(dict(manifest, general_api=True)))
    assert '::scpp::fs::get(' not in ''.join(unit.read_text() for unit in compile_app(app, out))
    for flavor, code in [('php', r'\fs_get("x"); return 0;'), ('php', 'use function fs_get as fetch; return 0;'),
                         ('js', 'fs_get("x"); return 0;'), ('py', 'fs_get("x")\nreturn 0')]:
        main.unlink()
        main = app / ('main.' + flavor)
        main.write_text(code)
        config.write_text(json.dumps(manifest))
        compile_app(app, out, 'fs_get')
        config.write_text(json.dumps(dict(manifest, general_api=True)))
        compile_app(app, out)
    main.write_text('file_get_contents("x")\nreturn 0')
    compile_app(app, out, 'file_get_contents')
    config.write_text(json.dumps(dict(manifest, general_api="true")))
    compile_app(app, out, 'general_api must be a boolean')
