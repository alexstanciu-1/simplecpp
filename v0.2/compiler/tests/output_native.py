"""Bounded echo: exact bytes, observable cross-file calls and failure preservation."""
import json
import os
from pathlib import Path
import subprocess
import tempfile

root = Path(__file__).resolve().parents[2]
php = os.environ.get('PHP_EXECUTABLE', 'php')
main = root / 'compiler/main.php'
facts = root / 'build/clang/native-layout/facts.json'


def run(*args):
    return subprocess.run(list(map(str, args)), capture_output=True, timeout=60)


def success(result):
    assert result.returncode == 0, result.stdout + result.stderr
    return result


def build(units, binary, expected):
    success(run('clang++', '-std=c++23', '-stdlib=libc++', '-Wall', '-Wextra', '-Werror',
                '-I', root / 'runtime/generated/public', *units,
                root / 'build/clang/libscpp_runtime.a', '-o', binary))
    result = run(binary)
    assert result.returncode == 8 and result.stdout == expected and result.stderr == b'', result


with tempfile.TemporaryDirectory(prefix='scpp-output-') as temporary:
    work = Path(temporary)
    forms = {'php': '$n int=8; echo $n;', 'cpp': 'int n=8; echo n;', 'js': 'let n: int=8; echo n;'}
    literals = [(b'""', b''), (b'"\\n\\r\\t\\0"', b'\n\r\t\0'),
                (b'"\\\\\\\"\\$"', b'\\"$'), (b"'a\\nb'", b'a\\nb'),
                (b"'a\\'b\\\\c'", b"a'b\\c"), (b"'$literal'", b'$literal'),
                ('"é"'.encode(), 'é'.encode()), (b'"raw\0\xff"', b'raw\0\xff')]
    for flavor, prefix in forms.items():
        source = work / 'single.scpp'
        source.write_bytes(prefix.encode() + b' echo 0; echo 9223372036854775807; '
                           + b''.join(b'echo ' + spelling + b';' for spelling, _ in literals) + b'return 8;')
        cpp = work / 'single.cpp'
        success(run(php, main, 'cpp', flavor, source, facts, cpp))
        build([cpp], work / 'single', b'809223372036854775807' + b''.join(value for _, value in literals))
        for stage in ('parse', 'collect', 'prepare'):
            args = [php, main, stage, flavor, source] + ([facts] if stage == 'prepare' else [])
            json.loads(success(run(*args)).stdout)
        before = cpp.read_bytes()
        for bad in [b'echo 1, 2;', b'echo;', b'echo unknown;', b'echo 1.5;', b'echo true;',
                    b'echo "\\x41";', b'echo "\\012";', b'echo "$name";', b'echo "\\q";',
                    b'echo 9223372036854775808;']:
            source.write_bytes(bad + b' return 8;')
            assert run(php, main, 'cpp', flavor, source, facts, cpp).returncode == 1, bad
            assert cpp.read_bytes() == before
        print(f'Exact bytes, inspection and rejection passed: {flavor}', flush=True)

    sources = work / 'sources'
    sources.mkdir()
    (sources / 'main').write_text('echo "start|"; ping(); echo "end"; return 8;')
    (sources / 'support').write_text('void ping() { echo "before|"; pong(); return; }')
    (sources / 'other').write_text('function pong(): void { echo 42; }')
    output = work / 'bundle'

    def compile_program():
        return run(php, main, 'program', sources, 'main', facts, output,
                   'php', 'main', 'cpp', 'support', 'js', 'other')

    success(compile_program())
    manifest = json.loads((output / 'manifest.json').read_text())
    units = [output / name for name in manifest['translation_units']]
    build(units, work / 'multi', b'start|before|42end')
    before = {str(p.relative_to(output)): p.read_bytes() for p in output.rglob('*') if p.is_file()}
    (sources / 'support').write_text('void ping() { echo "after|"; pong(); return; }')
    success(compile_program())
    build(units, work / 'multi', b'start|after|42end')
    after = {str(p.relative_to(output)): p.read_bytes() for p in output.rglob('*') if p.is_file()}
    assert [name for name in before if before[name] != after[name]] == [manifest['sources']['support']]
    (sources / 'support').write_text('void ping() {} void unused() { echo "$invalid"; }')
    assert compile_program().returncode == 1
    assert after == {str(p.relative_to(output)): p.read_bytes() for p in output.rglob('*') if p.is_file()}
print('Cross-file output order, body edit and failure preservation passed')
