"""Compare typed CSV field lists, stream byte counts and file bytes with stock PHP."""
import json
from pathlib import Path
import subprocess
import sys
import tempfile

from app_namespace_native import service, invoke

root = Path(__file__).resolve().parents[2]


def literal(value):
    return json.dumps(value, ensure_ascii=False).replace(r'\u0000', r'\0')


records = [
    [''], ['\r\n'], ['""'], [',a,,'], ['a,"b,b","a""b"\r\n'],
    ['a\0b,"c\0d",'], ['  "quoted" tail, plain  '], ['"a\r\nb",z'], ['"unfinished\n'],
    ['A;"B;B"', ';'], ["a;'b''c'", ';', "'"], ['"a\\"b",x', ',', '"', '\\'],
    ['"a\\"b",x', ',', '"', ''], ['a\0"b\0c"', '\0'], ['"é",ä'], ['a\n\n'],
]
fields = ['', 'a,b', 'a"b', 'a b', 'a\tb', 'a\r\nb', 'ab\0cd', 'a\\"b', "a;b'c"]
write_options = [[], [';'], [';', "'"], [';', "'", ''], [';', "'", '!', '\r\n'], [',', '"', '', '']]
php = scpp = ''
for args in records:
    call = 'str_getcsv(' + ','.join(map(literal, args)) + ')'
    scpp += '$v=' + call + '; foreach ($v as $field) { echo bin2hex($field),"/"; } echo "\\n";\n'
    php += '$v=' + call + '; foreach ($v as $field) { echo bin2hex($field ?? ""),"/"; } echo "\\n";\n'

writes = '$h=fopen("data.csv","wb");\n'
writes += '$row=[' + ','.join(map(literal, fields)) + '];\n'
for options in write_options:
    writes += 'echo (int)fputcsv(' + ','.join(['$h', '$row', *map(literal, options)]) + '),"\\n";\n'
writes += 'echo (int)fputcsv($h,[]),"\\n"; fclose($h);'
with tempfile.TemporaryDirectory(prefix='scpp-csv-') as directory:
    work = Path(directory)
    app, out = work/'app', work/'out'
    app.mkdir()
    (app/'module.json').write_text(json.dumps({'dependencies': {'runtime': {'modules': ['io']}}}))
    (app/'main.php').write_text(scpp + writes.replace('$row=[', '$row vector<string> = ['))
    proc = service(app, out)
    try:
        result = invoke(proc)
        assert 'error' not in result, result
        bundle = json.loads((out/'manifest.json').read_text())
        units = [out/u for u in bundle['translation_units']]
        subprocess.run([sys.argv[1], '-std=c++23', '-stdlib=libc++', '-I', root/'runtime/generated/public',
                        *units, *bundle['link_inputs'], '-o', work/'run'], check=True)
        expected = subprocess.check_output(['php', '-n', '-d', 'error_reporting=24575', '-r', php + writes], cwd=work)
        file = work/'data.csv'
        expected_bytes = file.read_bytes()
        file.unlink()
        actual = subprocess.check_output([work/'run'], cwd=work)
        assert actual == expected, (actual, expected)
        assert file.read_bytes() == expected_bytes
    finally:
        proc.stdin.close()
        proc.wait()
