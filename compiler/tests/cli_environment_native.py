"""Exercise argument transport through generated main and source-visible environment lookup."""
from source_suffix import source_suffix
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile

from app_namespace_native import service, invoke

root = Path(__file__).resolve().parents[2]
clang = sys.argv[1]
public = root / 'runtime/generated/public'


def project(work, source, *, general=False, helper=None, flavor="php"):
    app, out = work/'app', work/'out'
    app.mkdir(parents=True)
    (app/'module.json').write_text(json.dumps({'general_api': general}))
    (app/f'main.{source_suffix(flavor)}').write_text(source)
    if helper:
        (app/'helper.phs').write_text(helper)
    proc = service(app, out)
    try:
        response = invoke(proc)
    finally:
        proc.stdin.close()
        proc.wait()
    return response, out


def build(work, source, **kwargs):
    response, out = project(work, source, **kwargs)
    assert 'error' not in response, response
    bundle = json.loads((out/'manifest.json').read_text())
    exe = work/'program'
    assert all('libscpp_process' not in path for path in bundle['link_inputs'])
    subprocess.run([clang, '-std=c++23', '-stdlib=libc++', '-I', public,
                    *(out/u for u in bundle['translation_units']), *bundle['link_inputs'], '-o', exe], check=True)
    return exe


with tempfile.TemporaryDirectory(prefix='scpp-cli-environment-') as tmp:
    work = Path(tmp)
    exe = build(work/'full', r'''
$n=cli_argument_count();
if($n!==cli_argc()){return 1;}
$values=read_arguments(); $boxed=cli_args(); $other=cli_argv();
if(count($values)!==$n || count($boxed)!==$n){return 2;}
foreach($values as $i=>$s){$b string=$boxed[$i]; if($s!==$b){return 3;} echo strlen($s),"|",$s,"\n";}
$boxed[0]="changed"; $values[0]="changed"; $fresh=read_arguments();
$again=cli_argv(); $original string=$again[0];
$first string=$other[0]; if($original!==$first){return 7;} if($first!==$fresh[0] || $first==="changed"){return 4;}
$present=getenv("SCPP_CLI_PROBE");
$bytes=""; if(isset($present)){ $bytes=$present; echo "present|",$bytes; } else {echo "absent";}
$general=env_get("SCPP_CLI_PROBE"); if(isset($general)!==isset($present)){return 5;}
if(isset($general)){$owned string=$general; if($owned!==$bytes){return 6;}}
return 0;
''', general=True, helper='function read_arguments(): vector<string> { return cli_arguments(); }')
    for args in ([b'custom-name'], [b'custom-name', b'', b'two words', b'\xff-x']):
        expected = b''.join(str(len(arg)).encode()+b'|'+arg+b'\n' for arg in args)
        for value in (None, '', 'a b'):
            env = dict(os.environ)
            env.pop('SCPP_CLI_PROBE', None)
            if value is not None:
                env['SCPP_CLI_PROBE'] = value
            result = subprocess.run(args, executable=exe, env=env, capture_output=True, check=True)
            tail = b'absent' if value is None else b'present|'+value.encode()
            assert result.stdout == expected+tail, result.stdout
    cpp = build(work/'cpp', 'vector<string> args=cli_arguments(); return count(args) - cli_argument_count();', flavor='cpp')
    subprocess.run([cpp,'argument'], check=True)
    nul = build(work/'nul', r'$s=getenv("SCPP_CLI_PROBE\0suffix"); return 0;')
    result = subprocess.run([nul], env={**os.environ, 'SCPP_CLI_PROBE':'exists'}, capture_output=True)
    assert result.returncode and b'must not contain NUL' in result.stderr, result
    for i, expr in enumerate(['cli_arguments()', 'env_get("x")', '$argc', '$argv']):
        response, _ = project(work/str(i), 'echo '+expr+'; return 0;', general=i>=2)
        assert 'Unknown' in response.get('error',''), response
    probe = build(work/'count', 'return cli_argc();')
    assert subprocess.run([probe,'a']).returncode == 2
    unit, = (work/'count/out/units').glob('*.cpp')
    deps = subprocess.check_output([clang,'-std=c++23','-stdlib=libc++','-I',public,'-M',unit], text=True)
    assert '/cli/argument_count.hpp' in deps
    assert all(h not in deps for h in ['/vectors.hpp','/mixed.hpp','/carriers.hpp','/process/']), deps
