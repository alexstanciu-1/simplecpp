"""Exercise the PHP entry point as a subprocess, including transactional failure."""
import json
from pathlib import Path
import subprocess
import tempfile

main = Path(__file__).resolve().parents[1] / 'main.php'


def run(*args):
    return subprocess.run(['php', str(main), *map(str, args)], capture_output=True)


assert run('--help').returncode == 0
assert run('parse').returncode == 2
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
    assert b':2:7: Expected integer or name' in result.stderr
    source.write_bytes(b'"\xff\x00"')
    assert json.loads(run('tokens', 'php', source).stdout)['tokens'][0]['text_hex'] == '22ff0022'
    source.write_bytes(b'good;\n$')
    result = run('tokens', 'php', source)
    assert result.returncode == 1 and not result.stdout
    assert b':2:1: Expected variable name' in result.stderr
    assert run('tokens', 'phs', source).returncode == 2
    assert run('tokens', 'php', Path(directory) / 'missing').returncode == 1
print('CLI checks passed')
