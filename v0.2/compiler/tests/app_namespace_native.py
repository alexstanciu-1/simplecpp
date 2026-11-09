"""Cross-flavor provider bindings and application namespace changes through the resident project."""
import argparse
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import time

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'tools/compiler_panel'))
from native_build import write_build


def invoke(process):
    process.stdin.write('"run"\n')
    process.stdin.flush()
    return json.loads(process.stdout.readline())


def service(app, output):
    return subprocess.Popen(['php', ROOT / 'compiler/tests/incremental_executable.php', app, output],
                            stdin=subprocess.PIPE, stdout=subprocess.PIPE, text=True,
                            env={**os.environ, 'XDEBUG_MODE': 'off'})


def artifacts(folder):
    return {p.relative_to(folder): p.read_bytes() for p in folder.rglob('*') if p.suffix in ('.cpp', '.hpp')}


def prove(work):
    app, output, native = (work / n for n in ('app', 'output', 'native'))
    app.mkdir(parents=True)
    native.mkdir()
    manifest = app / 'module.json'
    manifest.write_text('{}')
    (app / 'main.cpp').write_text('echo (int)p("a\\0b","a\\0"), (int)j("",""), (int)c("a","ab"),'
                                  '(int)c(string_byte_from_int(255),string_byte_from_int(255)); return 8;')
    (app / 'predicate.php').write_text('function p(string $s, string $n): bool { return str_starts_with($s,$n); }')
    (app / 'predicate.js').write_text('function j(s: string,n: string): bool { return s.startsWith(n); }')
    cpp = app / 'predicate.cpp'
    original = 'bool c(string s,string n) { return s.starts_with(n); }'
    cpp.write_text(original)
    predicates = {flavor: app / ('predicate.' + flavor) for flavor in ('cpp', 'php', 'js')}
    originals = {flavor: path.read_text() for flavor, path in predicates.items()}
    calls = {
        'php': ('str_starts_with($s,$n)', 'str_ends_with($s,$n)', 'str_contains($s,$n)'),
        'js': ('s.startsWith(n)', 's.endsWith(n)', 's.includes(n)'),
        'cpp': ('s.starts_with(n)', 's.ends_with(n)', 's.contains(n)'),
    }
    edits = {}
    for flavor, path in predicates.items():
        prefix, suffix, contains = calls[flavor]
        for role, candidate in [('prefix', prefix), ('suffix', suffix), ('contains', contains)]:
            text = originals[flavor].replace(prefix, candidate)
            invalid = text.replace(candidate, candidate.replace('$n)', '200)').replace('(n)', '(200)'))
            edits['bad-' + role + '-' + flavor] = (path, invalid)
            edits['recovery-' + role + '-' + flavor] = (path, text.replace('return ', 'return !!') if role == 'prefix' else text)
    for flavor, call in [('php', 'str_starts_with'), ('js', 'startsWith'), ('cpp', 'starts_with')]:
        probe = (f'#include <scpp/{flavor}/string.hpp>\nnamespace Consumer {{\n'
                 f'using namespace ::scpp::types; using namespace ::scpp::{flavor};\n'
                 'static_assert(__is_same(byte, ::scpp::uint8)); static_assert(sizeof(int64) == 8);\n'
                 f'bool_t test(const string& s) {{ return {call}(s,s); }} }}')
        subprocess.run(['clang++', '-std=c++23', '-fsyntax-only', '-x', 'c++', '-I',
                        ROOT / 'runtime/generated/public', '-'], input=probe, text=True, check=True)
    process = service(app, output)
    revision = int(time.time())
    rows = []
    try:
        for label, config in [('default', {}), ('custom', {'cpp_namespace': 'acme::ledger'}),
                              ('no_edit', {'cpp_namespace': 'acme::ledger'}), ('global', {'cpp_namespace': None}),
                              ('invalid', {'cpp_namespace': 'bad::class'}), ('retry', {'cpp_namespace': 'bad::class'}),
                              ('shadow', {'cpp_namespace': 'starts_with'}), ('restore', {}),
                              *((label, {}) for label in edits)]:
            manifest.write_text(json.dumps(config))
            if label in edits:
                path, text = edits[label]
                path.write_text(text)
                revision += 1
                os.utime(path, (revision, revision))
            before = artifacts(output)
            result = invoke(process)
            fresh_folder = work / ('fresh-' + label)
            with service(app, fresh_folder) as fresh:
                other = invoke(fresh)
                fresh.stdin.close()
            if label in ('invalid', 'retry') or label.startswith('bad-'):
                assert 'error' in result and result['error'] == other.get('error') and before == artifacts(output), (label, result)
                continue
            assert 'error' not in result and 'error' not in other, (result, other)
            expected_emits = 1 if label.startswith('recovery-') else (0 if label == 'no_edit' else 4)
            assert len(result['emitted_sources']) == expected_emits, result
            assert artifacts(output) == artifacts(fresh_folder), label
            data = json.loads((output / 'manifest.json').read_text())
            box = config.get('cpp_namespace', 'App')
            for key, unit in data['sources'].items():
                text = (output / unit).read_text()
                if key.endswith('/main.cpp'):
                    assert all(f'<scpp/{f}/string.hpp>' not in text for f in ('cpp','js','php'))
                    continue
                flavor = key.split('.')[-1]
                assert f'#include <scpp/{flavor}/string.hpp>' in text and f'using namespace ::scpp::{flavor};' in text
                assert 'using namespace ::scpp::types;' in text
                if box:
                    assert 'namespace ' + box + ' {' in text and 'bool_t scpp_function_' in text
                if label == 'shadow' and flavor == 'cpp':
                    assert '::scpp::cpp::starts_with(' in text
            for header in data['headers']:
                text = (output / header).read_text()
                assert 'using namespace' not in text and '::scpp::string' in text
            command = write_build(native, [output / name for name in data['translation_units']])
            command[2] = '8'
            graph = native / 'build.ninja'
            graph.write_text(graph.read_text().replace(' -MD ', ' -ftime-trace -MD '))
            start = time.perf_counter()
            build = subprocess.run(command, cwd=native, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True)
            assert build.returncode == 0, build.stdout
            wall = 1000 * (time.perf_counter() - start)
            run = subprocess.run([native / 'program'], capture_output=True)
            expected = b'0101' if label == 'recovery-suffix-php' else b'1101'
            assert (run.returncode, run.stdout) == (8, expected), (label, run)
            rows.append((label, round(wall, 2)))
        print(rows)
    finally:
        process.stdin.close()
        process.wait()


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    if args.output:
        prove(args.output.resolve())
    else:
        with tempfile.TemporaryDirectory() as folder:
            prove(Path(folder))
