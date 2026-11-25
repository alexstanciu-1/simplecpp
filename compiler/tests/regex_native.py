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
    for prefix in (['regex', 'preg'] if flavor == 'php' else ['regex']):
        local(prefix + 'Jit', 'bool', prefix + '_jit_available()')
        for suffix, args, expected in [('Plain', '"a.b/"', r'a\\.b/'), ('Delimited', '"a.b/", "/"', r'a\\.b\\/')]:
            value = local(prefix + suffix, 'string', f'{prefix}_quote({args})')
            statements.append(f'if ({value} !== "{expected}") {{ return 8; }}')
    if flavor == 'php':
        statements.append(r'''
$named hash<string,string> = regex_match_named("/(?<tag>a)/", "a");
if ($named["0"] !== "a" || $named["tag"] !== "a") { return 31; }
$rows vector<vector<string>> = regex_match_all("/(a)?b/", "bab");
$second vector<string> = $rows[1];
if (count($rows) !== 2 || $second[1] !== "a") { return 32; }
$rows = regex_match_all_pattern_order("/(a)?b/", "bab");
$second = $rows[1];
if ($second[0] !== "" || $second[1] !== "a") { return 33; }
$namedRows vector<hash<string,string>> = regex_match_all_named("/(?<tag>a)/", "aa", -1);
$named = $namedRows[0];
if (count($namedRows) !== 1 || $named["tag"] !== "a") { return 34; }
$nestedFail result_or_false<vector<vector<string>>> = regex_match_all("/[/", "");
if (isset($nestedFail)) { return 35; }
$boxes mixed = ["p"=>"ax", "q"=>"xz", "r"=>"!"];
$kept result_or_false<mixed> = preg_grep("/x/", $boxes);
$values mixed = $kept;
if (count($values) !== 2 || (string)$values["q"] !== "xz") { return 21; }
$values["q"] = "updated";
$original mixed = $kept;
if ((string)$original["q"] !== "xz") { return 30; }
$values = preg_filter("/x/", "x", $boxes, 1);
if (count($values) !== 2 || (string)$values["p"] !== "ax") { return 22; }
$kept = preg_split("/,/", "", -1, PREG_SPLIT_NO_EMPTY);
if (!isset($kept)) { return 23; }
$values = $kept;
if (count($values) !== 0) { return 24; }
$kept = preg_split("/[/", "");
if (isset($kept)) { return 25; }
$n int = preg_match_all("/(?<letter>a)/", "aa", $values);
$column mixed = $values["letter"];
if ($n !== 2 || (string)$column[1] !== "a") { return 26; }
$n = preg_match_all("/(?<letter>a)/", "aa", $values, PREG_SET_ORDER, -1);
$column = $values[0];
if ($n !== 1 || (string)$column["letter"] !== "a") { return 27; }
$n = preg_match("/(?<letter>a)/", "a", $values);
if ($n !== 1 || (string)$values[0] !== "a" || (string)$values["letter"] !== "a") { return 28; }
$status result_or_false<int> = preg_match("/[/", "", $values);
if (isset($status) || count($values) !== 0) { return 29; }
$row result_or_false<vector<string>> = regex_match("/(a)?(b)(z)?/", "xxb", -1);
$v vector<string> = $row;
if (count($v) !== 3 || $v[0] !== "b" || $v[1] !== "" || $v[2] !== "b") { return 11; }
$v = regex_match("/x/", "x", 2);
if (count($v) !== 0) { return 12; }
$row = regex_match("/[/", "x");
if (isset($row)) { return 13; }
$input vector<string> = ["aa", "!", "ba"];
$v = regex_grep("/a/", $input);
if (count($v) !== 2 || $v[1] !== "ba") { return 14; }
$v = regex_filter("/a/", "*", $input, 1);
if (count($v) !== 2 || $v[0] !== "*a" || $v[1] !== "b*") { return 15; }
$v = regex_split("/(,)/", ",a,b,", -1, 3);
if (count($v) !== 5 || $v[0] !== "," || $v[3] !== "b") { return 16; }
$t string = regex_replace("/(?:|a)/", "-", "a");
if ($t !== "---") { return 17; }
$t = regex_replace("/()/u", "-", "é");
if ($t !== "-é-") { return 18; }
$t = regex_replace("/(a)(b)/", "\\2\\1", "abab", 1);
if ($t !== "baab") { return 19; }
$bad result_or_false<string> = regex_replace("/[/", "", "", 0);
if (isset($bad)) { return 20; }
''')
    end = local('endByte', 'int', '99')
    for i, (pattern, subject, offset, start, after) in enumerate([
        ('/x/', 'x', 0, 0, 1), ('/x/', 'é!x', 2, 3, 4),
        (r'/\\Gx/', 'é!x', 2, -1, 99), (r'/\\Gx/', 'é!x', 3, 3, 4),
        ('/(x)/', r'a\0x', 2, 2, 3), ('//', '', 0, 0, 0),
        ('//', 'é', 2, 2, 2), ('/x/', 'xx', 2, -1, 99),
        ('/é/u', 'éé', 2, 2, 4), ('/./u', 'é', 1, None, 99),
        ('/[/', 'x', 0, None, 99), ('//', 'x', -1, None, 99),
        ('//', 'x', 9223372036854775807, None, 99),
    ]):
        statements.append(f'{end} = 99;')
        owner = local(f'span{i}', 'result<int>', f'regex_find_span("{pattern}", "{subject}", {offset}, {end})')
        if start is None:
            statements.append(f'if (isset({owner})) {{ return 5; }}')
        else:
            value = local(f'start{i}', 'int', owner)
            statements.append(f'if ({value} !== {start}) {{ return 6; }}')
        statements.append(f'if ({end} !== {after}) {{ return 7; }}')
    return '\n'.join(statements) + '\nreturn 0;'


def prove(work, runtime, flavor):
    app = work / flavor
    app.mkdir()
    output = work / ('out-' + flavor)
    source = app / ('main.' + flavor)
    manifest = app / 'module.json'
    enabled = json.dumps({'general_api': True, 'dependencies': {'runtime': {'modules': ['regex']}}})
    def compiler(out):
        return subprocess.Popen([php, '-r', driver, root/'compiler/boot.php', app, out, runtime],
                                stdin=subprocess.PIPE, stdout=subprocess.PIPE, text=True)
    source_edit(manifest, '{"general_api":true}')
    interpolation = {'php': '$n int = 8; echo "é{$n}";', 'cpp': 'int n = 8; echo f"é{n}";',
                     'js': 'let n: int = 8; echo `é${n}`;'}[flavor]
    source_edit(source, interpolation + ' return 0;')
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
        if flavor != 'cpp':
            source_edit(manifest, '{"dependencies":{"runtime":{"modules":["regex"]}}}')
            source_edit(source, 'regex_test("/a/", "a"); return 0;')
            assert 'regex_test' in invoke(resident).get('error', '')
            if flavor == 'php':
                source_edit(source, 'preg_quote("."); preg_match("/a/", "a"); return 0;')
                ok()
            source_edit(source, fixture(flavor))
        source_edit(manifest, enabled)
        ok()
        source_edit(source, 'regex_find_span("/a/", "a", 0, 1); return 0;')
        bad = invoke(resident)
        assert 'reference' in bad.get('error', '').lower(), bad
        with compiler(work/('invalid-' + flavor)) as fresh:
            assert invoke(fresh).get('error') == bad['error']
            fresh.stdin.close()
        source_edit(source, fixture(flavor))
        ok()
        byte_local = {'php': '$b byte = 0;', 'js': 'let b: byte = 0;', 'cpp': 'byte b = 0;'}[flavor]
        var = '$b' if flavor == 'php' else 'b'
        source_edit(source, byte_local + f'regex_find_span("/a/", "a", 0, {var}); return 0;')
        bad = invoke(resident)
        assert 'Reference argument requires exact storage type' in bad.get('error', ''), bad
        source_edit(source, fixture(flavor))
        ok()
        if flavor != 'php':
            source_edit(source, 'preg_match("/a/", "a"); return 0;')
            error = invoke(resident).get('error', '')
            assert 'preg_match' in error, error
            source_edit(source, fixture(flavor))
            ok()
        source_edit(manifest, '{"general_api":true}')
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
