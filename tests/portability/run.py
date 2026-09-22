#!/usr/bin/env python3
"""Focused portability proof. --native adds real scpp build/run parity."""
import json
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[2]
TOOLS = ROOT / 'tools/php_portability'
EXPECTED = 'yes\n0\nno\n0\nno\nyes\n12\nyes\ntrue\n12\nno\nyes\nfalse\n7\n'


def run(args, cwd=None, ok=True):
    result = subprocess.run(args, cwd=cwd, text=True, capture_output=True)
    assert (result.returncode == 0) == ok, (args, result.stdout, result.stderr)
    return result


with tempfile.TemporaryDirectory(prefix='scpp-portability-') as tmp:
    base = Path(tmp)
    tools = base / 'tools'
    shutil.copytree(TOOLS, tools)
    source, output = base / 'source', base / 'output'
    source.mkdir()
    fixture = ROOT / 'tests/portability/fixtures/take.php'
    shutil.copy(fixture, source / 'take.php')
    prologue = fixture.read_text().split('// </scpp-imports>\n', 1)[0] + '// </scpp-imports>\n'
    def authored(body):
        return prologue + body + '\n'
    sync = ['php', str(tools / 'sync_imports.php'), str(source)]
    run(sync + ['--check'])

    (source / 'other.php').write_text(authored('$x /** uint32 */ = 10;\necho $x, "\\n";'))
    command = ['php', str(tools / 'convert.php'), str(source), str(output)]
    assert json.loads(run(command).stdout) == dict(converted=2, reused=0, removed=0)
    generated = output / 'take.phs'
    assert '$out int = 9;' in generated.read_text()
    assert 'scpp' not in generated.read_text()
    stamp = generated.stat().st_mtime_ns
    assert json.loads(run(command).stdout) == dict(converted=0, reused=2, removed=0)
    assert generated.stat().st_mtime_ns == stamp
    (source / 'other.php').write_text(authored('$x /** uint32 */ = 11;\necho $x, "\\n";'))
    assert json.loads(run(command).stdout) == dict(converted=1, reused=1, removed=0)
    assert generated.stat().st_mtime_ns == stamp
    (output / 'unrelated.txt').write_text('keep')
    (source / 'other.php').rename(source / 'renamed.php')
    assert json.loads(run(command).stdout) == dict(converted=1, reused=1, removed=1)
    assert not (output / 'other.phs').exists()
    assert (output / 'unrelated.txt').read_text() == 'keep'
    with (tools / 'src/converter.php').open('a') as f:
        f.write('\n// Rule-version invalidation witness.\n')
    assert json.loads(run(command).stdout)['converted'] == 2
    assert generated.stat().st_mtime_ns == stamp
    manifest = (output / '.scpp-portability.json').read_bytes()
    for bad in [
        '$e = new Exception("unqualified");',
        '$e = new \\Throwable();',
        '$__scpp_portability_exception_1 = 1;',
        'class scpp_portability_exception {}',
        'try {} catch (\\Error $e) {}',
        '$f();',
        '\\take_false($x, false);',
        'use function scpp\\take_false as custom_take;',
        'unknown();',
        '\\scpp\\unknown();',
        '\\scpp\\take_false($x);',
        'class Bad { public readonly array $items; }',
        'class Bad { public readonly ?string $text; }',
        'class Bad { public readonly Item $item; }',
        '$x /** mixed */ = 1;',
        '$x /** self */ = 1;',
        '$x /** Foo|Bar */ = 1;',
        '$x /** nullable<Foo> */ = null;',
        '$x /** Foo<int> */ = 1;',
        '$x /** result_or_false<bool> */ = false;',
        '$x /** result_or_bool<bool> */ = true;',
        'function f(): int { return 1; }',
    ]:
        (source / 'bad.php').write_text(authored(bad))
        error = run(command, ok=False).stderr
        assert 'bad.php:' in error, error
        assert (output / '.scpp-portability.json').read_bytes() == manifest
        assert generated.stat().st_mtime_ns == stamp
    (source / 'bad.php').unlink()
    # Literal named local types pass through without declaration/type resolution.
    for typename in ['External', r'catalog\Reference', r'\catalog\Reference']:
        (source / 'named.php').write_text(authored('$x /** ' + typename + ' */ = $external;'))
        run(command)
        assert '$x ' + typename + ' = $external;' in (output / 'named.phs').read_text()
    (source / 'named.php').unlink()
    run(command)
    # Explicit names and case-insensitive default names use the same local rules.
    qualified = (source / 'take.php').read_text()
    qualified = qualified.replace('take_nullable(', '\\scpp\\take_nullable(')
    qualified = qualified.replace('take_false(', '\\scpp\\take_false(')
    qualified = qualified.replace('take_bool(', 'TAKE_BOOL(')
    (source / 'qualified.php').write_text(qualified)
    run(command)
    assert (output / 'qualified.phs').read_text() == generated.read_text()
    alternate = run(['php', '-d', 'auto_prepend_file=' + str(tools / 'runtime/bootstrap.php'), str(source / 'qualified.php')])
    assert alternate.stdout == EXPECTED

    missing = source / 'missing.php'
    missing.write_text('<?php\n$x /** int */ = 1;\n')
    assert 'managed imports' in run(command, ok=False).stderr
    run(sync + ['--check'], ok=False)
    run(sync)
    saved = missing.stat().st_mtime_ns
    run(sync)
    assert missing.stat().st_mtime_ns == saved
    run(sync + ['--check'])
    run(command)
    missing.unlink()
    run(command)
    # Managed block repair must never erase authored executable code.
    malformed = source / 'unsafe.php'
    malformed.write_text(authored('echo "body";').replace('// </scpp-imports>', 'echo "do not erase";\n// </scpp-imports>'))
    original = malformed.read_bytes()
    assert 'unexpected content' in run(sync, ok=False).stderr
    assert malformed.read_bytes() == original
    malformed.unlink()
    # Reject broken state before touching any generated output.
    state = output / '.scpp-portability.json'
    accepted = state.read_bytes()
    for invalid in [None, {'version': 2, 'files': {}}, {'version': 1, 'files': [] , 'ignored': 1}]:
        if isinstance(invalid, dict) and invalid.get('version') == 1:
            invalid['files'] = {'take.php': {'input': 'bad', 'output': 'bad'}}
        state.write_text(json.dumps(invalid))
        before = generated.read_bytes()
        assert 'manifest' in run(command, ok=False).stderr
        assert generated.read_bytes() == before
    state.write_bytes(accepted)
    # Even a hash-matching output cannot be reused through a symlink.
    backup = generated.read_bytes()
    external = base / 'external.phs'
    external.write_bytes(backup)
    generated.unlink()
    generated.symlink_to(external)
    assert 'Symlink output' in run(command, ok=False).stderr
    assert external.read_bytes() == backup
    generated.unlink()
    generated.write_bytes(backup)
    generated.write_text('corrupted')
    assert json.loads(run(command).stdout)['converted'] == 1
    php = run(['php', '-d', 'auto_prepend_file=' + str(tools / 'runtime/bootstrap.php'), str(fixture)])
    assert php.stdout == EXPECTED, php.stdout
    # Literal validation must fail locally, before the native target can corrupt bytes.
    for body in [r'echo "\xA9";', r'echo "\251";', r'echo "a\0b";',
                 r'class Bytes { public string $value = "\xA9"; }']:
        (source / 'bad.php').write_text(authored(body))
        assert 'binary string literal' in run(command, ok=False).stderr
        (source / 'bad.php').unlink()
    for body in [r'echo "\xC3\xA9";', "echo '\\xA9';"]:
        (source / 'literal.php').write_text(authored(body))
        run(command)
        (source / 'literal.php').unlink()
    run(command)
    # False is a valid nullable payload; zero is a valid result_or_false payload.
    library = str(tools / 'runtime/bootstrap.php')
    witness = run(['php', '-r', 'require $argv[1]; $out=5; '
        'if (!\\scpp\\take_nullable($out,false) || $out !== false) exit(1); '
        'if (!\\scpp\\take_false($out,0) || $out !== 0) exit(2);', library])
    run(['php', '-r', 'require $argv[1]; '
        'foreach ([["abc",-1,1,""],["abc",0,-1,""],["abc",4,1,""],["abc",3,2,""],["abc",1,99,"bc"]] '
        'as [$value,$offset,$length,$expected]) { '
        'if (scpp\\string_byte_slice($value,$offset,$length) !== $expected) exit(1); }', library])
    # A private policy fixture proves builtin shadowing and bypass rejection.
    # This is not a shipped count adapter or a native semantic equivalence claim.
    policy = tools / 'function_map.php'
    policy.write_text(policy.read_text().replace("\t'count' => ['php' => null, 'target' => 'count', 'arity' => 1],", "\t'count' => ['php' => 'scpp\\\\compat\\\\count', 'target' => 'count', 'arity' => 1],", 1))
    assert 'managed imports' in run(command, ok=False).stderr
    run(sync)
    run(command)
    owned = source / 'owned.php'
    owned.write_text('<?php\necho count(9);\n')
    run(sync)
    run(command)
    support = base / 'private_support.php'
    support.write_text('<?php namespace scpp\\compat; function count(mixed $v): int { return 17; }')
    observed = run(['php', '-d', 'auto_prepend_file=' + str(support), str(owned)])
    assert observed.stdout == '17'
    owned.write_text(owned.read_text().replace('count(9)', '\\count(9)'))
    assert 'global bypass' in run(command, ok=False).stderr
    owned.unlink()
    run(command)
    if '--native' in sys.argv:
        project = base / 'native'
        project.mkdir()
        cli = str(ROOT / 'bin/scpp.php')
        run(['php', cli, 'init', '--php-profile=strict'], cwd=project)
        shutil.copy(generated, project / 'main.phs')
        result = run(['php', cli, 'run'], cwd=project)
        assert result.stdout.endswith(EXPECTED), (result.stdout, result.stderr)
        print('Native PHP++ result matches expected output and PHP execution.')
print('Portability conversion, runtime states, rejection and incremental checks passed.')
