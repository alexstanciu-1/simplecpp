"""Drive native PHP query-builder overloads and compare emitted bytes with stock PHP."""
import json
import random
import struct
from pathlib import Path
import subprocess
import sys
import tempfile

from app_namespace_native import ROOT, invoke, service

# Source-only type declarations select all concrete overloads. Stock PHP consumes the same values.
rows = [
    ('vector<int>', '[0,12,-43]'), ('vector<string>', '[""," +~","a\\0b"]'),
    ('vector<bool>', '[true,false]'),
    ('vector<float>', '[1.0,-0.0,1.2345678901234567,1e20,1e-5,1e-4,9.99999999999999e13]'),
    ('hash<byte,int>', '[5=>255,1=>0]'), ('hash<int,int>', '[5=>123,0=>1]'),
    ('hash<int,string>', '["z"=>12,"a"=>0]'), ('hash<string,int>', '[2=>"z",0=>"a"]'),
    ('hash<string,string>', '["01"=>"","k ~+"=>"a b+~/%\\0é"]'),
    ('mixed', '[4=>[2=>"x",0=>"y"],"flag"=>false,"skip"=>null,"empty"=>[],1=>true]'),
    ('mixed', '[null,true,false,"",0,[],[null],["x"=>false]]'),
]
simple, stock = [], []
for i, (shape, literal) in enumerate(rows):
    v = '$v' + str(i)
    simple.append(f'{v} {shape} = {literal};')
    stock.append(f'{v} = {literal};')
    for args in ['', ',"p +&"', ',"",""', ',"",null,PHP_QUERY_RFC1738',
                 ',"p ","&amp;",PHP_QUERY_RFC3986']:
        call = f'echo http_build_query({v}{args}),"\\n";'
        simple.append(call)
        stock.append(call)

common = '''
echo http_build_query(["page"=>1]),"\\n";
$q=[]; parse_str("z=old&a[2]=x&a[0]=y&z=new",$q);
echo http_build_query($q),"\\n";
$bytes=""; for ($i=0;$i<256;$i++) {$bytes=$bytes.chr($i);}
$map=[]; $map[$bytes]=$bytes;
echo http_build_query($map),"\\n",http_build_query($map,"",null,PHP_QUERY_RFC3986),"\\n";
'''
# A typed string hash preserves the spelling "1" as text. PHP arrays normalize it to integer 1.
simple.append('''$h hash<int,string> = ["1"=>9]; echo http_build_query($h,"p"),"\\n";
$m mixed=1; try {$unused=http_build_query($m);return 1;} catch (Exception $e) {echo "caught\\n";}''')
stock.append('echo "1=9\\ncaught\\n";')
with tempfile.TemporaryDirectory(prefix='scpp-query-build-') as directory:
    work = Path(directory)
    app, output = work/'app', work/'out'
    app.mkdir()
    (app/'module.json').write_text('{}')
    main = app/'main.php'
    main.write_text('\n'.join(simple) + common)
    proc = service(app, output)
    try:
        result = invoke(proc)
        assert 'error' not in result, result
        manifest = json.loads((output/'manifest.json').read_text())
        units = [output/u for u in manifest['translation_units']]
        assert any('::scpp::php::http_build_query(' in unit.read_text() for unit in units)
        subprocess.run([sys.argv[1], '-std=c++23', '-stdlib=libc++', '-I', ROOT/'runtime/generated/public',
                        *units, *manifest['link_inputs'], '-o', work/'run'], check=True)
        actual = subprocess.check_output([work/'run'])
        expected = subprocess.check_output(['php', '-n', '-d', 'precision=14', '-r', '\n'.join(stock) + common])
        assert actual == expected, [(i,a,e) for i,(a,e) in enumerate(zip(actual.splitlines(),expected.splitlines())) if a!=e]
        # Supply identical IEEE-754 bits to both runtimes, including nonfinite values
        # and subnormals that have no literal spelling in the source caller.
        probe = work/'float.cpp'
        probe.write_text('''#include "scpp/php/http_build_query.hpp"
#include "scpp/vectors.hpp"
#include <cstdio>
int main() {
    scpp::vector_float64 values;
    double n;
    while (std::fread(&n, sizeof(n), 1, stdin)) values.append(n);
    const auto query = scpp::php::http_build_query(values);
    std::fwrite(query.data(), 1, query.size(), stdout);
}
''')
        subprocess.run([sys.argv[1], '-std=c++23', '-stdlib=libc++', '-I', ROOT/'runtime/generated/public',
                        probe, *manifest['link_inputs'], '-o', work/'float'], check=True)
        rng = random.Random(29)
        bits = [0, 1, 1 << 63, 0x7fefffffffffffff, 0x7ff0000000000000,
                0xfff0000000000000, 0x7ff8000000000000] + [rng.getrandbits(64) for _ in range(1024)]
        data = b''.join(struct.pack('=Q', n) for n in bits)
        oracle = 'echo http_build_query(array_values(unpack("d*",stream_get_contents(STDIN))));'
        encoded = subprocess.run([work/'float'], input=data, capture_output=True, check=True)
        php = subprocess.run(['php', '-n', '-d', 'display_errors=stderr', '-d', 'precision=14', '-r', oracle],
                             input=data, capture_output=True, check=True)
        assert encoded.stderr.count(b'NAN value was coerced') == php.stderr.count(b'NAN value was coerced')
        native, reference = encoded.stdout.split(b'&'), php.stdout.split(b'&')
        assert native == reference, [(hex(n), a, e) for n, a, e in zip(bits, native, reference) if a != e][:8]
        for bad in ['$v dynamic=[]; http_build_query($v);', 'http_build_query(123);']:
            main.write_text(bad)
            assert 'error' in invoke(proc)
    finally:
        proc.stdin.close()
        proc.wait()
