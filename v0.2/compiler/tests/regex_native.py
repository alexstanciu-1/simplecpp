"""Compile both regex result shapes and check module removal/recovery through the native bundle."""
import json
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile

from app_namespace_native import artifacts, invoke
from extends_native import source_edit

root = Path(__file__).resolve().parents[2]
php, cxx, mold, build = sys.argv[1:]

driver = r'''
require $argv[1];
$p = new scpp\compiler\Incremental_Project($argv[2], $argv[3], new scpp\compiler\entry_target(31), $argv[4]);
while (fgets(STDIN) !== false) {
    try { $r = $p->run(); } catch (Throwable $e) { $r = ['error'=>$e->getMessage()]; }
    echo json_encode($r, JSON_THROW_ON_ERROR), "\n";
}
'''


def fixture(flavor):
    statements = []
    def local(name, type_name, expr):
        declarations = {'php': f'${name} {type_name}', 'cpp': f'{type_name} {name}', 'js': f'let {name}: {type_name}'}
        statements.append(declarations[flavor] + ' = ' + expr + ';')
        return ('$' if flavor == 'php' else '') + name

    for name, pattern, subject, expected in [
        ('hit', '/(a)(b)/i', 'xABy', 1), ('miss', '/c/', 'ab', 0),
        ('nul', '/a\\0b/', 'xa\\0by', 1), ('pastnul', '/b$/', 'a\\0b', 1),
        ('emptyMatch', '//', '', 1), ('paired', '{a{2}}', 'aa', 1),
        ('escaped', '/a\\\\/b/', 'a/b', 1), ('invalid', '/[/', 'a', None),
        ('modifier', '/a/q', 'a', None), ('badUtf', '/./u', None, None),
    ]:
        text = f'"{subject}"' if subject is not None else 'string_byte_from_int(255)'
        args = f'("{pattern}", {text})'
        for function, scalar, result in [('regex_test', 'bool', 'result<bool>')] + (
                [('preg_match', 'int', 'result_or_false<int>')] if flavor == 'php' else []):
            key = name + function
            owner = local(key, result, function + args)
            if expected is None:
                statements.append(f'if (isset({owner})) {{ return 2; }}')
            else:
                statements.append(f'if (!isset({owner})) {{ return 3; }}')
                value = local(key + 'Value', scalar, owner)
                want = str(expected) if scalar == 'int' else ('true' if expected else 'false')
                statements.append(f'if ({value} !== {want}) {{ return 4; }}')
    return '\n'.join(statements) + '\nreturn 0;'


def prove(work, runtime, flavor):
    app = work / flavor
    app.mkdir()
    output = work / ('out-' + flavor)
    source = app / ('main.' + flavor)
    manifest = app / 'module.json'
    enabled = json.dumps({'dependencies': {'runtime': {'modules': ['regex']}}})
    def compiler(out):
        return subprocess.Popen([php, '-r', driver, root/'compiler/boot.php', app, out, runtime],
                                stdin=subprocess.PIPE, stdout=subprocess.PIPE, text=True)
    source_edit(manifest, '{}')
    source_edit(source, 'return 0;')
    with compiler(output) as resident:
        def ok():
            result = invoke(resident)
            assert 'error' not in result, result
        def links():
            return json.loads((output/'manifest.json').read_text())['link_inputs']
        ok()
        assert [Path(path).name for path in links()] == ['libscpp_runtime.a'], links()
        if flavor == 'php':
            source_edit(source, 'preg_match("/a/", "a"); return 0;')
            assert 'requires module runtime-regex' in invoke(resident).get('error', '')
        source_edit(source, fixture(flavor))
        assert 'requires module runtime-regex' in invoke(resident).get('error', '')
        source_edit(manifest, enabled)
        ok()
        if flavor != 'php':
            source_edit(source, 'preg_match("/a/", "a"); return 0;')
            error = invoke(resident).get('error', '')
            assert 'preg_match' in error, error
            source_edit(source, fixture(flavor))
            ok()
        source_edit(manifest, '{}')
        assert 'requires module runtime-regex' in invoke(resident).get('error', '')
        source_edit(manifest, enabled)
        ok()
        with compiler(work/('fresh-' + flavor)) as fresh:
            result = invoke(fresh)
            assert 'error' not in result, result
            fresh.stdin.close()
        assert artifacts(output) == artifacts(work/('fresh-' + flavor))
        resident.stdin.close()
    assert [Path(path).name for path in links()] == ['libscpp_regex.a', 'libpcre2-8.so', 'libscpp_runtime.a'], links()
    native = work / ('native-' + flavor)
    request = dict(action='build', cwd=str(work), bundle=str(output), native=str(native),
                   config=dict(cxx=cxx, flags=['-std=c++23', '-stdlib=libc++', '-O2', '-g0', '-ftime-trace',
                                               '-I', str(root/'runtime/generated/public')],
                               link_flags=['--ld-path=' + mold], jobs=8, cache=str(work/'qualification')))
    def build():
        host = subprocess.run([php, root/'compiler/tests/native_build_host.php'], input=json.dumps(request)+'\n',
                              text=True, capture_output=True, check=True)
        report = json.loads(host.stdout)['result']
        assert report['exit_code'] == 0, report
        subprocess.run([report['executable']], check=True)
        return report

    build()
    # Recompile the complete fixture with the toolchain already qualified.
    for obj in native.glob('unit-*.o'):
        obj.unlink()
    report = build()
    total = {}
    for path in native.glob('unit-*.json'):
        for event in json.loads(path.read_text())['traceEvents']:
            if event['name'] in ('Total Frontend', 'Total Backend'):
                total[event['name']] = total.get(event['name'], 0) + event['dur']/1000
    link = next(row.split('\t') for row in reversed((native/'.ninja_log').read_text().splitlines()) if '\tprogram\t' in row)
    print(flavor, report['qualification'], 'native ms', round(report['native_ms'], 1),
          total, 'link ms', int(link[1])-int(link[0]), flush=True)


with tempfile.TemporaryDirectory(prefix='scpp-regex-') as directory:
    work = Path(directory)
    runtime = work/'runtime'
    runtime.mkdir()
    for name in ('module.json', 'regex.module.json'):
        shutil.copy(root/'runtime'/name, runtime/name)
    (work/'build').mkdir()
    (work/'build/clang').symlink_to(build, target_is_directory=True)
    for flavor in ('php', 'cpp', 'js'):
        prove(work, runtime, flavor)
