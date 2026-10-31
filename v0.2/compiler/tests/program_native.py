"""Explicit mixed-flavor inventory -> deterministic units -> one executable."""
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile

root = Path(__file__).resolve().parents[2]
php = os.environ.get('PHP_EXECUTABLE', 'php')
main = root/'compiler/main.php'
facts = root/'build/clang/native-layout/facts.json'
include = root/'runtime/generated/public'
library = root/'build/clang/libscpp_runtime.a'


def run(*args):
    return subprocess.run(list(map(str, args)), capture_output=True, text=True)


def success(result):
    assert result.returncode == 0, result.stdout + result.stderr
    return result


def compile_program(sources, output, entry, inventory):
    args = [item for pair in inventory for item in pair]
    return run(php, main, 'program', sources, entry, facts, output, *args)


def snapshot(output):
    return {str(p.relative_to(output)): p.read_bytes() for p in output.rglob('*') if p.is_file()}


def build(output, expected):
    manifest = json.loads((output/'manifest.json').read_text())
    units = [output/path for path in manifest['translation_units']]
    assert sum('int main()' in p.read_text() for p in units) == 1
    binary = output/'program'
    success(run('clang++', '-std=c++23', '-stdlib=libc++', '-Wall', '-Wextra', '-Werror',
                '-I', include, *units, library, '-o', binary))
    assert run(binary).returncode == expected
    return manifest


with tempfile.TemporaryDirectory(prefix='scpp-program-') as temporary:
    work = Path(temporary)
    sources = work/'sources'
    sources.mkdir()
    (sources/'a').mkdir()
    (sources/'b').mkdir()
    (sources/'a/notes.scpp').write_text('# PHP comment only\n')
    (sources/'b/notes.scpp').write_text('/* JS comment only */\n')
    inventory = [('cpp', 'main.scpp'), ('php', 'a/notes.scpp'), ('js', 'b/notes.scpp')]
    for flavor, text in [('cpp', 'int ret=8; return ret;'), ('php', '$ret int=8; return $ret;'),
                         ('js', 'let ret: int=8; return ret;')]:
        (sources/'main.scpp').write_text(text)
        inventory[0] = (flavor, 'main.scpp')
        output = work/flavor
        success(compile_program(sources, output, 'main.scpp', inventory))
        manifest = build(output, 8)
        assert len(manifest['translation_units']) == 4
        assert len(set(manifest['sources'].values())) == 3
        before = snapshot(output)
        success(compile_program(sources, output, 'main.scpp', list(reversed(inventory))))
        assert snapshot(output) == before, 'Reordered input changed output bytes'
        print('multifile executable passed:', flavor, flush=True)

    # Stable logical identities survive checkout relocation; absolute IO paths do not enter C++.
    relocated = work/'relocated'
    shutil.copytree(sources, relocated)
    relocated_output = work/'relocated-output'
    success(compile_program(relocated, relocated_output, 'main.scpp', inventory))
    for path in manifest['translation_units'] + ['manifest.json']:
        assert (output/path).read_bytes() == (relocated_output/path).read_bytes()

    # Every selected file matters, even if it contributes no executable body.
    original = (sources/'b/notes.scpp').read_text()
    for invalid in ['return 9;', 'let bad = ;']:
        (sources/'b/notes.scpp').write_text(invalid)
        before = snapshot(output)
        result = compile_program(sources, output, 'main.scpp', inventory)
        assert result.returncode == 1 and not result.stdout and 'notes.scpp:' in result.stderr
        assert snapshot(output) == before
        absent = work/'failed-output'
        assert compile_program(sources, absent, 'main.scpp', inventory).returncode == 1
        assert not absent.exists()
    (sources/'b/notes.scpp').write_text(original)
    for entry, requests in [('missing.scpp', inventory), ('main.scpp', inventory + [inventory[0]]),
                            ('../main.scpp', inventory), ('main.scpp', inventory + [('cpp', '../outside')])]:
        before = snapshot(output)
        assert compile_program(sources, output, entry, requests).returncode == 1
        assert snapshot(output) == before
    print('whole-program rejection and prior-output preservation passed', flush=True)

    # Switching entry requires the former entry to become declaration-only too.
    (sources/'main.scpp').write_text('// former entry\n')
    (sources/'b/notes.scpp').write_text('let ret: int=9; return ret;')
    success(compile_program(sources, output, 'b/notes.scpp', inventory))
    manifest = build(output, 9)
    assert manifest['entry'] == 'b/notes.scpp'

    # Build only manifest members, never every cpp file in the directory.
    unrelated = output/'unrelated.cpp'
    unrelated.write_text('#error not part of this program\n')
    retired = output/manifest['sources']['a/notes.scpp']
    reduced = [item for item in inventory if item[1] != 'a/notes.scpp']
    success(compile_program(sources, output, 'b/notes.scpp', reduced))
    assert not retired.exists()
    assert unrelated.read_text() == '#error not part of this program\n'
    manifest = build(output, 9)
    assert len(manifest['translation_units']) == 3
    success(compile_program(sources, output, 'b/notes.scpp', inventory))
    assert retired.exists()
    build(output, 9)

    collision = work/'collision'
    collision.mkdir()
    (collision/'entry.cpp').write_text('user file\n')
    assert compile_program(sources, collision, 'b/notes.scpp', inventory).returncode == 1
    assert (collision/'entry.cpp').read_text() == 'user file\n'
    print('source removal/re-addition, exact link inventory and collision protection passed', flush=True)
print('Multifile end-to-end checks passed')
