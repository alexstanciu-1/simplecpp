"""Compare PHP query tables recursively, including key kind, order and byte values."""
import json
from pathlib import Path
import subprocess
import sys
import tempfile

from app_namespace_native import ROOT, invoke, service


walk = """
function dump_query(mixed $table): void {
    echo "[";
    foreach ($table as $key => $value) {
        if (is_int($key)) {echo "i",(int)$key;} else {echo "s",bin2hex((string)$key);}
        echo ":";
        if (is_array($value)) {dump_query($value);} else {echo bin2hex((string)$value);}
        echo ";";
    }
    echo "]";
}
"""
queries = [
    '', 'a=1&b=2&a=3', 'a[]=1&a[]=2', 'a=one&a[]=two', 'a[]=one&a=two',
    'a[x]=1&a[x][]=2&a[y]=3', 'a[4]=v&a[]=w&a[k]=s&a[0]=z',
    '+a.b=1&a+b=2&a_b=3&a[b.c]=2&a[d+e]=3', 'a%5Bx%5D=v&a[b]tail=w&bad[k=x',
    'n%00foo=hi&v=a%00b&=gone&bare&bad=%2g%&v2=a+b%2B', 'a=1\0&b=2',
    '0=a&01=b&%2B1=c&-0=d&9223372036854775808=e&2147483647=f',
    'a[ ]=v&a[  ]=w&a[0][bad=y&[]ignored=1',
    '..Host-x=no&__Host-x=yes&a[__Secure-x]=no',
]
queries += ['keep=ok&a[old]=1&a' + '[x]' * depth + '=v&tail=yes' for depth in (63, 64, 65)]
queries += ['a=old&a' + '[]' * 65 + '=v&a[new]=ok']
queries += ['&&'.join(['a=1'] * n + ['b=last']) for n in (999, 1000)]
queries += ['&'.join(['=ignored'] * 1000 + ['later=omitted'])]
body = '$out=["before"=>"original"];\n'
for q in queries:
    body += 'parse_str(' + json.dumps(q).replace(r'\u0000', r'\0') + ',$out); dump_query($out); echo "\\n";\n'

with tempfile.TemporaryDirectory(prefix='scpp-query-') as directory:
    work = Path(directory)
    app, output = work/'app', work/'out'
    app.mkdir()
    (app/'module.json').write_text('{}')
    main = app/'main.phs'
    failures = ''
    for q in ['ok=new&a[-1]=x', 'ok=new&2147483648=x', 'a[2147483647]=x&a[]=y']:
        failures += '$out=["original"=>"ok"]; try {parse_str("' + q + '",$out);return 1;} catch (Exception $e) {dump_query($out);}'
    main.write_text(walk + body + failures)
    proc = service(app, output)
    try:
        result = invoke(proc)
        assert 'error' not in result, result
        manifest = json.loads((output/'manifest.json').read_text())
        units = [output/u for u in manifest['translation_units']]
        assert any('::scpp::php::parse_str(' in unit.read_text() for unit in units)
        subprocess.run([sys.argv[1], '-std=c++23', '-stdlib=libc++', '-I', ROOT/'runtime/generated/public',
                        *units, *manifest['link_inputs'], '-o', work/'run'], check=True)
        expected = subprocess.run(['php', '-n', '-d', 'display_errors=stderr', '-d', 'max_input_vars=1000',
                                   '-d', 'max_input_nesting_level=64', '-r', walk + body], capture_output=True, check=True)
        actual = subprocess.run([work/'run'], capture_output=True)
        assert actual.returncode == 0, (actual.stderr, actual.stdout[-300:])
        assert actual.stdout == expected.stdout + b'[s6f726967696e616c:6f6b;]' * 3, (actual.stdout, expected.stdout)
        assert actual.stderr.count(b'Input variables exceeded 1000') == expected.stderr.count(b'Input variables exceeded 1000') == 2
        for bad in ['$out dynamic=[]; parse_str("a=1",$out);', 'parse_str("a=1",[]);']:
            main.write_text(bad)
            result = invoke(proc)
            assert 'error' in result, result
    finally:
        proc.stdin.close()
        proc.wait()
