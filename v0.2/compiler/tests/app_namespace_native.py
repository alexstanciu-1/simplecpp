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
        'php': ('str_starts_with($s,$n)', 'str_ends_with($s,$n)', 'str_contains($s,$n)',
                'isset(strpos($s,$n))', 'isset(strrpos($s,$n))'),
        'js': ('s.startsWith(n)', 's.endsWith(n)', 's.includes(n)', 's.indexOf(n) >= 0', 's.lastIndexOf(n) >= 0'),
        'cpp': ('s.starts_with(n)', 's.ends_with(n)', 's.contains(n)', 's.find(n) >= 0', 's.rfind(n) >= 0'),
    }
    edits = {}
    for flavor, path in predicates.items():
        prefix = calls[flavor][0]
        for role, candidate in zip(('prefix', 'suffix', 'contains', 'first', 'last'), calls[flavor], strict=True):
            text = originals[flavor].replace(prefix, candidate)
            invalid = text.replace(candidate, candidate.replace('$n)', '200)').replace('(n)', '(200)'))
            edits['bad-' + role + '-' + flavor] = (path, invalid)
            edits['recovery-' + role + '-' + flavor] = (path, text.replace('return ', 'return !!') if role == 'prefix' else text)
        # Both position overloads must recover after an invalid typed offset.
        for role, candidate in zip(('first', 'last'), calls[flavor][3:], strict=True):
            needle = '$n' if flavor == 'php' else 'n'
            bounded = candidate.replace(needle + ')', needle + ', 0)')
            valid = originals[flavor].replace(prefix, bounded)
            edits[f'bad-offset-{role}-{flavor}'] = (path, valid.replace(', 0)', ', "bad")'))
            edits[f'recovery-offset-{role}-{flavor}'] = (path, valid)
    count_calls = {'php': 'substr_count($s,$n)', 'js': 's.countMatches(n)', 'cpp': 's.count_matches(n)'}
    for flavor, call in count_calls.items():
        needle = '$n' if flavor == 'php' else 'n'
        condition = f'strlen({needle}) === 0 || {call} > 0'
        text = originals[flavor].replace(calls[flavor][0], condition)
        edits[f'bad-count-{flavor}'] = (predicates[flavor], text.replace(call, call.replace(needle + ')', '7)')))
        edits[f'recovery-count-{flavor}'] = (predicates[flavor], text)
        bounded = call[:-1] + ', 0, strlen(' + ('$s' if flavor == 'php' else 's') + '))'
        valid = text.replace(call, bounded)
        edits[f'bad-count-window-{flavor}'] = (predicates[flavor], valid.replace(', 0, strlen(', ', "bad", strlen('))
        edits[f'bad-count-length-{flavor}'] = (predicates[flavor], text.replace(call, call[:-1] + ', 0, "bad")'))
        edits[f'recovery-count-window-{flavor}'] = (predicates[flavor], valid)
    round_trips = {'php': 'implode("!", explode("!", $n))', 'cpp': 'join(n.split("!"), "!")',
                   'js': 'n.split("!").join("!")'}
    for flavor, call in round_trips.items():
        # Keep the caller unchanged while the edited function switches providers.
        expression = calls[flavor][0].replace('$n)', call + ')') if flavor == 'php' else calls[flavor][0].replace('(n)', f'({call})')
        valid = originals[flavor].replace(calls[flavor][0], expression)
        edits['bad-split-join-' + flavor] = (predicates[flavor], valid.replace('"!"', '7'))
        edits['recovery-split-join-' + flavor] = (predicates[flavor], valid)
        bounded = valid.replace('explode("!", $n)', 'explode("!", $n, 2)').replace('split("!")', 'split("!", 2)')
        edits['bad-split-limit-' + flavor] = (predicates[flavor], bounded.replace(', 2)', ', "bad")'))
        edits['recovery-split-limit-' + flavor] = (predicates[flavor], bounded)
    # Repair the count in a nested string-producing call. The cross-unit caller stays unchanged.
    transforms = {
        'php': 'str_contains(strtoupper(strtolower($s)), strtoupper(str_repeat($n, 1)))',
        'js': 's.toLowerCase().toUpperCase().includes(n.repeat(1).toUpperCase())',
        'cpp': 's.to_lower().to_upper().contains(n.repeat(1).to_upper())',
    }
    for flavor, expression in transforms.items():
        path = predicates[flavor]
        text = originals[flavor].replace(calls[flavor][0], expression)
        edits['bad-transforms-' + flavor] = (path, text.replace('1)', '"bad")'))
        edits['recovery-transforms-' + flavor] = (path, text)
    for flavor, name in [('php', 'substr'), ('cpp', 'substr'), ('js', 'slice')]:
        for args in ('0', '0, 99'):
            call = f'substr($n, {args})' if flavor == 'php' else f'n.{name}({args})'
            expression = calls[flavor][0].replace('$n)', call + ')') if flavor == 'php' else calls[flavor][0].replace('(n)', f'({call})')
            text = originals[flavor].replace(calls[flavor][0], expression)
            edits[f'bad-slice-{args}-{flavor}'] = (predicates[flavor], text.replace(args + ')', '"bad")'))
            edits[f'recovery-slice-{args}-{flavor}'] = (predicates[flavor], text)
    replace_names = {'php': ('str_replace_first', 'str_replace'), 'cpp': ('replace_first', 'replace_all'),
                     'js': ('replace', 'replaceAll')}
    for flavor, names in replace_names.items():
        for name in names:
            call = f'{name}("x", "y", $n)' if flavor == 'php' else f'n.{name}("x", "y")'
            operand = '$n)' if flavor == 'php' else '(n)'
            argument = call + ')' if flavor == 'php' else f'({call})'
            text = originals[flavor].replace(calls[flavor][0], calls[flavor][0].replace(operand, argument))
            edits[f'bad-{name}-{flavor}'] = (predicates[flavor], text.replace('"y"', '5'))
            edits[f'recovery-{name}-{flavor}'] = (predicates[flavor], text)
    counted = originals['php'].replace('return ', '$out = 99; str_replace("x", "y", $s, $out); return $out === 0 && ')
    edits['bad-replace-count-php'] = (predicates['php'], counted.replace(', $out)', ', 0)'))
    edits['recovery-replace-count-php'] = (predicates['php'], counted)
    for form, args, expected in [('insert', '1', 'aXbc'), ('negative', '1, -2', 'abc')]:
        call = f'substr_replace("abc","X",{args})'
        text = originals['php'].replace('return ', f'return {call} === "{expected}" && ')
        edits[f'bad-substr-replace-{form}'] = (predicates['php'], text.replace(call, call[:-1] + ', "bad")'))
        edits[f'recovery-substr-replace-{form}'] = (predicates['php'], text)
    pads = {'php': 'str_pad($n, 0, "x", STR_PAD_LEFT)', 'js': 'n.padStart(0, "x")', 'cpp': 'n.pad_end(0, "x")'}
    for flavor, call in pads.items():
        operand = '$n)' if flavor == 'php' else '(n)'
        argument = call + ')' if flavor == 'php' else f'({call})'
        text = originals[flavor].replace(calls[flavor][0], calls[flavor][0].replace(operand, argument))
        if flavor == 'php':
            text = text.replace(';', ' && STR_PAD_LEFT === 0;')
        edits[f'bad-pad-{flavor}'] = (predicates[flavor], text.replace('"x"', '7'))
        edits[f'recovery-pad-{flavor}'] = (predicates[flavor], text)
    # A foreign declaration shadows the PHP provider constant in an unchanged caller.
    cpp_pad = edits['recovery-pad-cpp'][1]
    edits['recovery-pad-shadow'] = (cpp, cpp_pad + ' const int STR_PAD_LEFT = 1;')
    edits['recovery-pad-unshadow'] = (cpp, cpp_pad)
    trim_names = {'php': ('trim', 'ltrim', 'rtrim'), 'js': ('trim', 'trimStart', 'trimEnd'),
                  'cpp': ('trim', 'trim_start', 'trim_end')}
    for flavor, names in trim_names.items():
        for name in names:
            call = f'{name}($n)' if flavor == 'php' else f'n.{name}()'
            arg = calls[flavor][0].replace('$n)', call + ')') if flavor == 'php' else calls[flavor][0].replace('(n)', f'({call})')
            text = originals[flavor].replace(calls[flavor][0], arg)
            bad_call = f'{name}($n,1)' if flavor == 'php' else f'n.{name}("x")'
            edits[f'bad-{name}-{flavor}'] = (predicates[flavor], text.replace(call, bad_call))
            repaired = text.replace(call, f'{name}($n,"a")') if flavor == 'php' else text
            edits[f'recovery-{name}-{flavor}'] = (predicates[flavor], repaired)
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
            if label in ('recovery-pad-shadow', 'recovery-pad-unshadow'):
                expected_emits = 2
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
                    assert 'namespace ' + box + ' {' in text and f'bool_t {flavor[0]}_(' in text
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
            expected = b'0101' if label in ('recovery-suffix-php', 'recovery-pad-shadow', 'recovery-trim-php', 'recovery-ltrim-php') else b'1101'
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
