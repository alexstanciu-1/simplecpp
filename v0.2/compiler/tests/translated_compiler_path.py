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
PHP, CXX, LINKER, FILESYSTEM, RUNTIME = sys.argv[1:]
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
        '-I', ROOT / 'runtime/generated/public', *units, *libraries, '-o', binary)


def build_bundle(bundle, libraries, binary):
    manifest = json.loads((bundle / 'manifest.json').read_text())
    assert [Path(path).name for path in manifest['link_inputs']] == [Path(path).name for path in libraries]
    native_binary([bundle / unit for unit in manifest['translation_units']], libraries, binary)


with tempfile.TemporaryDirectory(prefix='scpp-translated-path-') as temporary:
    work = Path(temporary)
    translator = work / 'translator'
    translator.mkdir()
    for file in EXAMPLE.glob('*.php.scpp'):
        target = 'main.php' if file.name == 'proof.php.scpp' else file.stem
        shutil.copyfile(file, translator / target)
    (translator / 'module.json').write_text('{"dependencies":{"runtime":{"modules":["filesystem"]}}}')
    host_bundle, executable = work / 'translator-cpp', work / 'translated-compiler'
    project(translator, host_bundle)
    build_bundle(host_bundle, [FILESYSTEM, RUNTIME], executable)

    definition = b'function answer_2() : int {\n  return 8;\n}\n'
    updated = b'function answer_2() : int {\n  return 9;\n}\n'
    overflow = b'function answer_2() : int {\n  return 9223372036854775808;\n}\n'
    initial = b'return answer_2();\n'
    bad = b'\n  return missing_2();\n'
    fixed = b'\n  return answer_2();\n'
    incomplete = b'\n  return answer_2()'
    samples = {'definition.php': definition, 'initial.source': initial, 'bad.source': bad,
               'fixed.source': fixed, 'updated.source': updated, 'incomplete.source': incomplete,
               'overflow.source': overflow}
    for name, data in samples.items():
        (work / name).write_bytes(data)

    translated = run(executable, cwd=work)
    diagnostics = translated.stdout.decode().splitlines()
    assert len(diagnostics) == 9, translated.stdout
    oracle = work / 'oracle'
    oracle.mkdir()
    (oracle / 'module.json').write_text('{}')

    errors = [
        ('incomplete', definition, incomplete, 'main.php', len(incomplete)),
        ('bad', updated, bad, 'main.php', bad.index(b'missing_2')),
        ('overflow', overflow, initial, 'definition.php', overflow.index(b'9223372036854775808')),
    ]
    for index, (name, current_definition, entry, expected_path, expected_offset) in enumerate(errors):
        path, offset, message = diagnostics[index * 3:index * 3 + 3]
        assert (path, int(offset)) == (expected_path, expected_offset), (path, offset, message)
        (oracle / 'definition.php').write_bytes(current_definition)
        (oracle / 'main.php').write_bytes(entry)
        diagnostic = project(oracle, work / f'oracle-{name}', expected=1)
        text = (diagnostic.stdout + diagnostic.stderr).decode()
        match = re.search(rf'{re.escape(path)}:(\d+):(\d+): ([^\n]+)', text)
        assert match is not None, text
        source = entry if path == 'main.php' else current_definition
        line = source.count(b'\n', 0, expected_offset) + 1
        column = expected_offset - source.rfind(b'\n', 0, expected_offset)
        assert (line, column, message) == (int(match[1]), int(match[2]), match[3]), text

    assert (work / 'initial.cpp').read_bytes() == (work / 'recovered.cpp').read_bytes()
    for name, current_definition, entry, status in [
        ('initial', definition, initial, 8),
        ('recovered', definition, fixed, 8),
        ('updated', updated, fixed, 9),
    ]:
        (oracle / 'definition.php').write_bytes(current_definition)
        (oracle / 'main.php').write_bytes(entry)
        reference_bundle, reference = work / f'oracle-{name}', work / f'reference-{name}'
        project(oracle, reference_bundle)
        build_bundle(reference_bundle, [RUNTIME], reference)

        generated = work / f'generated-{name}'
        native_binary([work / f'{name}.cpp'], [RUNTIME], generated)
        expected = run(reference, expected=status)
        actual = run(generated, expected=status)
        assert (actual.stdout, actual.stderr) == (expected.stdout, expected.stderr)

print('Translated parse, preparation, diagnostics, recovery, and executable behavior match the PHP oracle.')
