"""No-argument void functions: single-source and mixed-flavor cross-source native proofs."""
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile

root = Path(__file__).resolve().parents[2]
php = os.environ.get('PHP_EXECUTABLE', 'php')
main = root / 'compiler/main.php'
facts = root / 'build/clang/native-layout/facts.json'
include = root / 'runtime/generated/public'
library = root / 'build/clang/libscpp_runtime.a'


def run(*args):
    return subprocess.run(list(map(str, args)), capture_output=True, text=True, timeout=60)


def success(result):
    assert result.returncode == 0, result.stdout + result.stderr
    return result


def build(units, binary):
    success(run('clang++', '-std=c++23', '-stdlib=libc++', '-Wall', '-Wextra', '-Werror',
                '-I', include, *units, library, '-o', binary))
    assert run(binary).returncode == 8


def snapshot(directory):
    return {str(p.relative_to(directory)): p.read_bytes() for p in directory.rglob('*') if p.is_file()}


forms = {
    'php': ('function ping(): void { $x int=9; pong(); return; } function pong(): void {}',
            '$ret int=8; ping(); return $ret;'),
    'cpp': ('void ping() { int x=9; pong(); return; } void pong() {}',
            'int ret=8; ping(); return ret;'),
    'js': ('function ping(): void { let x: int=9; pong(); return; } function pong(): void {}',
           'let ret: int=8; ping(); return ret;'),
}

with tempfile.TemporaryDirectory(prefix='scpp-functions-') as temporary:
    work = Path(temporary)
    for flavor, (definitions, entry) in forms.items():
        source = work / f'single-{flavor}.scpp'
        source.write_text(entry + '\n' + definitions)
        cpp = work / f'{flavor}.cpp'
        success(run(php, main, 'cpp', flavor, source, facts, cpp))
        build([cpp], work / f'program-{flavor}')
        parsed = json.loads(success(run(php, main, 'parse', flavor, source)).stdout)
        collected = json.loads(success(run(php, main, 'collect', flavor, source)).stdout)
        prepared = json.loads(success(run(php, main, 'prepare', flavor, source, facts)).stdout)
        assert len(parsed['functions']) == len(collected['functions']) == len(prepared['functions']) == 2
        assert prepared['statements'][1]['target'] == 'ping'
        assert prepared['functions'][0]['body']['statements'][1]['target'] == 'pong'
        before = cpp.read_bytes()
        source.write_text(definitions.replace('return;', 'return 9;') + entry)
        rejected = run(php, main, 'cpp', flavor, source, facts, cpp)
        assert rejected.returncode == 1 and 'cannot return a value' in rejected.stderr
        assert cpp.read_bytes() == before
        print(f'single-file functions and failure preservation passed: {flavor}', flush=True)

    sources = work / 'sources'
    sources.mkdir()
    (sources / 'support.cpp').write_text('void ping() { pong(); }')
    (sources / 'support.js').write_text('function pong(): void { return; }')
    inventory = [('php', 'main'), ('cpp', 'support.cpp'), ('js', 'support.js')]

    def compile_program(output, items=inventory, source_root=sources):
        return run(php, main, 'program', source_root, 'main', facts, output,
                   *[item for pair in items for item in pair])

    for flavor, (_, entry) in forms.items():
        (sources / 'main').write_text(entry)
        items = [(flavor, 'main'), *inventory[1:]]
        output = work / f'multi-{flavor}'
        success(compile_program(output, items))
        manifest = json.loads((output / 'manifest.json').read_text())
        units = [output / path for path in manifest['translation_units']]
        build(units, work / f'multi-program-{flavor}')
        assert 'void scpp_function_70696e67();' in (output / manifest['sources']['main']).read_text()
        assert 'void scpp_function_70696e67()\n{' not in (output / manifest['sources']['main']).read_text()
        assert 'void scpp_function_70696e67()\n{' in (output / manifest['sources']['support.cpp']).read_text()
        reordered = work / f'reordered-{flavor}'
        success(compile_program(reordered, list(reversed(items))))
        assert snapshot(reordered) == snapshot(output)
        print(f'cross-file functions and native exit 8 passed: {flavor}', flush=True)

    (sources / 'main').write_text('ping(); return 8;')
    output = work / 'stable'
    success(compile_program(output))
    before = snapshot(output)
    manifest = json.loads((output / 'manifest.json').read_text())
    # Stable source-owned output boundaries, not incremental compiler reuse.
    (sources / 'support.js').write_text('function pong(): void {}')
    success(compile_program(output))
    after = snapshot(output)
    assert [name for name in before if before[name] != after[name]] == [manifest['sources']['support.js']]
    relocated = work / 'relocated'
    shutil.copytree(sources, relocated)
    relocated_output = work / 'relocated-output'
    success(compile_program(relocated_output, source_root=relocated))
    assert snapshot(relocated_output) == after

    for text, diagnostic in [
        ('function pong(): void {', 'Expected }'),
        ('function pong(): void { return 7; }', 'cannot return a value'),
        ('function other(): void {}', 'Unknown function: pong'),
        ('function ping(): void {}', 'Duplicate function: ping'),
        ('function pong(): void {} ping();', 'selected entry'),
    ]:
        (sources / 'support.js').write_text(text)
        result = compile_program(output)
        assert result.returncode == 1 and diagnostic in result.stderr, result.stdout + result.stderr
        assert snapshot(output) == after
        fresh = work / 'must-not-exist'
        assert compile_program(fresh).returncode == 1
        assert not fresh.exists()
    print('function body output scope, relocation, and multifile failure preservation passed', flush=True)
print('Function end-to-end checks passed')
