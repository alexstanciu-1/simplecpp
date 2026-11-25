"""Duplicate diagnostics preserve analyzer output and block artifact publication."""
import json
import os
from pathlib import Path
import subprocess
import tempfile

compiler = Path(__file__).resolve().parents[1]
php = os.environ.get('PHP_EXECUTABLE', 'php')
facts = compiler.parent / 'build/clang/native-layout/facts.json'


def run(*args):
    return subprocess.run([php, str(compiler / 'main.php'), *map(str, args)], capture_output=True, text=True, timeout=60)


with tempfile.TemporaryDirectory(prefix='scpp-duplicates-') as tmp:
    root = Path(tmp)
    source = root / 'entry.cpp'
    source.write_text('void f() { int x=1; int x=2; } void f() {} void later() {} return 8;')
    for command in ('parse', 'collect'):
        result = run(command, 'cpp', source)
        assert result.returncode == 1, result
        data = json.loads(result.stdout)
        assert len(data['functions']) == 3 and len(data['diagnostics']) == 2, data
        assert result.stderr.count('Duplicate ') == 2, result.stderr
    for existing in (False, True):
        output = root / 'output.cpp'
        if existing:
            output.write_text('preserved output')
        result = run('cpp', 'cpp', source, facts, output)
        assert result.returncode == 1 and result.stderr.count('Duplicate ') == 2, result
        assert result.stdout == '', result.stdout
        assert output.read_text() == 'preserved output' if existing else not output.exists()
    source.write_text('void f() {} void f() { return')
    result = run('parse', 'cpp', source)
    assert result.returncode == 1 and result.stdout == '', result
print('Duplicate CLI inspection, error status, syntax failure and output preservation passed')
