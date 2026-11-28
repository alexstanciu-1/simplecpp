"""Compare a translated compiler path with the PHP compiler, including recovery."""
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[2]
ADAPTER, PHP, CXX, LINKER, FILESYSTEM, RUNTIME = sys.argv[1:]
FACTS = Path(RUNTIME).parent / 'native-layout/facts.json'
EXAMPLE = ROOT / 'compiler/examples/translated_path'
ENV = {**os.environ, 'XDEBUG_MODE': 'off'}


def run(*args, cwd=None, expected=0):
    result = subprocess.run(list(map(str, args)), cwd=cwd, env=ENV,
                            capture_output=True, timeout=60)
    assert result.returncode == expected, (str(args[0]), result.returncode, result.stdout, result.stderr)
    return result


def project(app, output, expected=0):
    return run(PHP, ROOT / 'compiler/main.php', 'project', app, FACTS, output, expected=expected)


def native_binary(units, libraries, binary):
    run(CXX, '-std=c++23', '-stdlib=libc++', '-O1', f'--ld-path={LINKER}',
        '-I', ROOT / 'runtime/generated/public', '-I', EXAMPLE.parent/'retained_frontend/socket', *units, *libraries, '-o', binary)


def build_bundle(bundle, libraries, binary):
    manifest = json.loads((bundle / 'manifest.json').read_text())
    assert [Path(path).name for path in manifest['link_inputs']] == [Path(path).name for path in libraries]
    native_binary([bundle / unit for unit in manifest['translation_units']], libraries, binary)


with tempfile.TemporaryDirectory(prefix='scpp-translated-path-') as temporary:
    work = Path(temporary)
    translator = work / 'translator'
    translator.mkdir()
    for file in EXAMPLE.glob('*.phs'):
        target = 'main.phs' if file.name == 'proof.phs' else file.name
        shutil.copyfile(file, translator / target)
    with (translator/'punctuation.phs').open('wb') as generated:
        subprocess.run([PHP, EXAMPLE.parent/'tokenizer/emit_punctuation.php'], stdout=generated, check=True)
    shutil.copyfile(EXAMPLE.parent/'tokenizer/utf8.phs', translator/'utf8.phs')
    (translator / 'module.json').write_text(json.dumps({'general_api': True, 'dependencies': {
        'compiler-private-frontend': {'path': ADAPTER}, 'runtime': {'modules': ['filesystem']}}}))
    host_bundle, executable = work / 'translator-cpp', work / 'translated-compiler'
    project(translator, host_bundle)
    numeric_links = json.loads((Path(ADAPTER)/'module.json').read_text())['artifacts']
    build_bundle(host_bundle, [FILESYSTEM, RUNTIME, *numeric_links], executable)

    definition = b'function answer_2() : int {\n  return 8;\n}\n'
    updated = b'function answer_2() : int {\n  return 9;\n}\n'
    overflow = b'function answer_2() : int {\n  return 9223372036854775808;\n}\n'
    initial = b'return answer_2();\n'
    bad = '/* first line */\r\n/* é */ return missing_2();\n'.encode()
    fixed = b'\n  return answer_2();\n'
    incomplete = b'\n  return answer_2()'
    unclosed = updated[:updated.index(b'}')]
    # Invalid UTF-8 after an earlier illegal byte must win; repairing it exposes that byte.
    lexical_error = b'return answer_2();\r\n\x01'
    invalid_entry = lexical_error + b'abc\x80'
    invalid_definition = updated + b'\xf0\x90'
    samples = {'invalid-entry.source': invalid_entry, 'invalid-definition.source': invalid_definition,
               'lexical-error.source': lexical_error, 'definition.phs': definition, 'initial.source': initial, 'bad.source': bad,
               'fixed.source': fixed, 'updated.source': updated, 'incomplete.source': incomplete,
               'overflow.source': overflow, 'unclosed.source': unclosed}
    for name, data in samples.items():
        (work / name).write_bytes(data)

    left = b'// first source\n  ' + definition
    right = b'\n\n   ' + updated
    (work/'left.phs').write_bytes(left)
    (work/'right.phs').write_bytes(right)
    translated = run(executable, cwd=work)
    parsed, *diagnostics = translated.stdout.decode().splitlines()
    oracle = work / 'oracle'
    oracle.mkdir()
    (oracle / 'module.json').write_text('{}')

    (oracle/'main.phs').write_bytes(initial)
    (oracle/'right.phs').write_bytes(right)
    (oracle/'left.phs').write_bytes(left)
    duplicate = project(oracle, work/'duplicate-bundle', expected=1)
    message = (work/'duplicate.txt').read_text()
    assert message == 'right.phs:3:13: Duplicate function: answer_2; first declaration at left.phs:2:12', message
    assert message == duplicate.stderr.decode().replace(str(oracle) + '/', '').strip(), duplicate.stderr
    for survivor, equivalent in [('right', 'updated'), ('left', 'initial')]:
        removed = oracle/('left.phs' if survivor == 'right' else 'right.phs')
        removed.unlink()
        project(oracle, work/(survivor+'-bundle'))
        # These exact translation units are compiled and executed against PHP below.
        assert (work/(survivor+'-survivor.cpp')).read_bytes() == (work/(equivalent+'.cpp')).read_bytes()
        removed.write_bytes(left if survivor == 'right' else right)
    (oracle/'left.phs').unlink()
    (oracle/'right.phs').unlink()
    (oracle / 'definition.phs').write_bytes(definition)
    (oracle / 'main.phs').write_bytes(initial)
    production_ast = run(PHP, '-r', r"""
require $argv[1];
$session = (new \scpp\compiler\Project_Compiler())->frontend($argv[2]);
$function = $session->scope->resolve('func:answer_2');
$body = $function->body();
$return = $body->statements[0];
if ($body->parent !== $function || $return->parent !== $body || $return->value->parent !== $return) {
    throw new LogicException('Unexpected production ownership');
}
echo implode('|', [$function->symbol_key(), $function->start, $function->header_end, $function->end,
    $body->start, $body->end, $return->start, $return->end, $return->value->start]);
""", ROOT / 'compiler/boot.php', oracle)
    assert parsed == production_ast.stdout.decode(), (parsed, production_ast.stdout)

    errors = [
        ('invalid-entry', definition, invalid_entry, 'main.phs', invalid_entry.index(b'\x80')),
        ('invalid-definition', invalid_definition, initial, 'definition.phs', len(updated)),
        ('lexical-error', definition, lexical_error, 'main.phs', lexical_error.index(b'\x01')),
        ('incomplete', definition, incomplete, 'main.phs', len(incomplete)),
        ('bad', updated, bad, 'main.phs', bad.index(b'missing_2')),
        ('overflow', overflow, initial, 'definition.phs', overflow.index(b'9223372036854775808')),
        ('unclosed', unclosed, fixed, 'definition.phs', len(unclosed)),
        ('removed', None, fixed, 'main.phs', fixed.index(b'answer_2')),
    ]
    assert len(diagnostics) == 4 * len(errors), translated.stdout
    for index, (name, current_definition, entry, expected_path, expected_offset) in enumerate(errors):
        path, offset, location, message = diagnostics[index * 4:index * 4 + 4]
        assert (path, int(offset)) == (expected_path, expected_offset), (path, offset, message)
        definition_path = oracle / 'definition.phs'
        if current_definition is None:
            definition_path.unlink()
        else:
            definition_path.write_bytes(current_definition)
        (oracle / 'main.phs').write_bytes(entry)
        diagnostic = project(oracle, work / f'oracle-{name}', expected=1)
        text = (diagnostic.stdout + diagnostic.stderr).decode()
        match = re.search(rf'{re.escape(path)}:(\d+):(\d+): ([^\n]+)', text)
        assert match is not None, text
        assert (location, message) == (f'{match[1]}:{match[2]}', match[3]), (name, location, text)

    assert (work / 'initial.cpp').read_bytes() == (work / 'recovered.cpp').read_bytes()
    # Building the translator and linking both native implementations of each distinct output
    # can take over three seconds on dev002 even after reusing byte-identical builds.
    binaries = {}
    for name, current_definition, entry, status in [
        ('initial', definition, initial, 8),
        ('recovered', definition, fixed, 8),
        ('updated', updated, fixed, 9),
        ('restored', definition, fixed, 8),
    ]:
        (oracle / 'definition.phs').write_bytes(current_definition)
        (oracle / 'main.phs').write_bytes(entry)
        reference_bundle, reference = work / f'oracle-{name}', work / f'reference-{name}'
        project(oracle, reference_bundle)
        # Repairs producing identical native inputs can execute the already-linked pair.
        source = work/f'{name}.cpp'
        emitted = tuple((p.relative_to(reference_bundle), p.read_bytes())
                        for p in sorted(reference_bundle.rglob('*')) if p.suffix in ('.cpp', '.hpp', '.h'))
        layout = json.loads((reference_bundle/'manifest.json').read_text())
        inputs = (source.read_bytes(), emitted, tuple(layout['translation_units']), tuple(layout['link_inputs']))
        if inputs in binaries:
            generated, reference = binaries[inputs]
        else:
            build_bundle(reference_bundle, [RUNTIME], reference)
            generated = work/f'generated-{name}'
            native_binary([source], [RUNTIME], generated)
            binaries[inputs] = generated, reference
        expected = run(reference, expected=status)
        actual = run(generated, expected=status)
        assert (actual.stdout, actual.stderr) == (expected.stdout, expected.stderr)

print('Translated function ranges, stable symbol lifecycle, diagnostics, and executable behavior match the PHP oracle.')
