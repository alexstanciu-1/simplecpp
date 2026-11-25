"""Compile module callers, link exactly their published inputs and check resident recovery."""
import json
from pathlib import Path
import subprocess
import sys
import tempfile

from app_namespace_native import artifacts, invoke, service

root = Path(__file__).resolve().parents[2]
io = r'''
$h file_stream=fopen("stream.dat","w+b"); $alias=$h;
$n int=fputs($h,"a\0b\nx"); if($n!==5 || !fflush($alias) || !rewind($h)){return 1;}
$zero int=ftell($alias); $empty string=fgets($alias,1); $first string=fgets($h);
$last string=fgets($alias); if($zero!==0 || $empty!=="" || $first!=="a\0b\n" || $last!=="x"){return 2;}
if(isset(fgets($h)) || !feof($alias)){return 3;}
$bytes string=fread($alias,4); if($bytes!=="" || isset(fseek($h,-1,SEEK_SET))){return 4;}
$ok int=io_seek($h,0,origin()); if($ok!==0 || feof($alias)){return 5;}
if(!fclose($alias) || isset(fopen("missing","rb"))){return 6;}
return 0;
'''
def prove(module, source, disabled_call, helper=''):
    with tempfile.TemporaryDirectory(prefix=module+'-source-') as directory:
        work = Path(directory)
        app, out = work/'app', work/'out'
        app.mkdir()
        manifest = app/'module.json'
        deps = dict(general_api=True, dependencies={'runtime': {'modules': [module]}})
        manifest.write_text(json.dumps(deps))
        main = app/'main.php'
        main.write_text(source)
        if helper:
            (app/'helper.cpp').write_text(helper)
        proc = service(app, out)
        try:
            result = invoke(proc)
            assert 'error' not in result, result
            baseline = artifacts(out)
            bundle = json.loads((out/'manifest.json').read_text())
            subprocess.run([sys.argv[1], '-std=c++23', '-stdlib=libc++', '-I', root/'runtime/generated/public',
                            *(out/u for u in bundle['translation_units']), *bundle['link_inputs'], '-o', work/'run'], check=True)
            subprocess.run([work/'run'], cwd=work, check=True, timeout=30)
            manifest.write_text(json.dumps({'general_api': True}))
            main.write_text(disabled_call+'; return 0;')
            assert 'requires module runtime-'+module in invoke(proc).get('error','')
            manifest.write_text(json.dumps(deps))
            main.write_text(source)
            assert 'error' not in invoke(proc) and artifacts(out) == baseline
            fresh = service(app, work/'fresh')
            try:
                assert 'error' not in invoke(fresh) and artifacts(work/'fresh') == baseline
            finally:
                fresh.stdin.close()
                fresh.wait()
        finally:
            proc.stdin.close()
            proc.wait()

if sys.argv[2] == 'io':
    prove('io', io, 'fopen("a","r")', 'int origin(){return SEEK_SET;}')
