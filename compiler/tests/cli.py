"""Exercise the PHP entry point as a subprocess, including transactional failure."""
import json
import os
from pathlib import Path
import subprocess
import tempfile

php = os.environ.get('PHP_EXECUTABLE', 'php')
main = Path(__file__).resolve().parents[1] / 'main.php'


def run(*args):
    return subprocess.run([str(php), str(main), *map(str, args)], capture_output=True)


assert run('--help').returncode == 0
assert run('parse').returncode == 2
assert run('build').returncode == 2
assert b'php main.php build' in run('--help').stdout
with tempfile.TemporaryDirectory() as directory:
    source = Path(directory) / 'source.input'
    for flavor, text, first in [('cpp', b'int x=3;', 'identifier'),
                                ('php', b'$x=3;', 'variable'),
                                ('js', b'let x=3;', 'keyword')]:
        source.write_bytes(text)
        result = run('tokens', flavor, source)
        assert result.returncode == 0, result.stderr
        data = json.loads(result.stdout)
        assert data['flavor'] == flavor
        assert data['tokens'][0]['kind'] == first
        for token in data['tokens']:
            assert bytes.fromhex(token['text_hex']) == text[token['offset']:token['offset'] + token['length']]
    for flavor, text, intent in [('cpp', b'int x=3; x=y;', 'existing_binding'),
                                  ('php', b'$x int=3; $x=$y;', 'declare_or_assign'),
                                  ('js', b'let x: int=3; x=y;', 'existing_binding')]:
        source.write_bytes(text)
        result = run('parse', flavor, source)
        assert result.returncode == 0, result.stderr
        data = json.loads(result.stdout)
        local, assignment = data['statements']
        assert data['flavor'] == flavor and local['kind'] == 'local'
        assert local['name']['text'] == 'x' and local['type']['text'] == 'int'
        assert local['initializer']['spelling'] == '3'
        assert assignment['intent'] == intent and assignment['value']['name']['text'] == 'y'
    source.write_bytes(b'let x=1;\nlet y=')
    result = run('parse', 'js', source)
    assert result.returncode == 1 and not result.stdout
    assert b':2:7: Expected identifier' in result.stderr
    source.write_bytes('"é\x00"'.encode())
    assert json.loads(run('tokens', 'php', source).stdout)['tokens'][0]['text_hex'] == '22c3a90022'
    source.write_bytes(b'"\xff\x00"')
    result = run('tokens', 'php', source)
    assert result.returncode == 1 and not result.stdout
    assert b':1:2: Invalid UTF-8 source' in result.stderr
    source.write_bytes(b'good;\n$')
    result = run('tokens', 'php', source)
    assert result.returncode == 1 and not result.stdout
    assert b':2:1: Expected variable name' in result.stderr
    assert run('tokens', 'phs', source).returncode == 2
    assert run('tokens', 'php', Path(directory) / 'missing').returncode == 1
    project = Path(directory)
    facts = main.parents[1] / 'build/clang/native-layout/facts.json'
    (project / 'main.php').write_text('<?php\n$v vector<vector<int>>=[]; echo answer();')
    (project / 'helper.php').write_text('<?php function answer():int { return 42; }')
    for _ in range(2):
        result = run('program', project, 'main.php', facts, project / 'out',
                     'php', 'main.php', 'php', 'helper.php')
        assert result.returncode == 0, result.stderr
        assert result.stderr.count(b'opening tag is deprecated') == 1, result.stderr
    for name in ('include', 'require', 'include_once', 'require_once'):
        for prefix in ('', '$x = '):
            source.write_text(f'<?php\n{prefix}{name} "helper.php";')
            result = run('parse', 'php', source)
            assert result.returncode == 1 and f'{name} is unsupported'.encode() in result.stderr
            assert b'module.json' in result.stderr and b':2:' in result.stderr
    source.write_text('<?php\ndeclare ( strict_types = 1 );')
    result = run('parse', 'php', source)
    assert result.returncode == 1 and b':2:1: Remove declare(strict_types)' in result.stderr
print('CLI checks passed')
