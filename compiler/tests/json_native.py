"""Exercise the published JSON source functions through the project link manifest."""
import json
from pathlib import Path
import subprocess
import sys
import tempfile

from app_namespace_native import artifacts, invoke, service

root = Path(__file__).resolve().parents[2]
source = r'''
$r=json_decode("false"); if(!isset($r)){return 1;}
$d dynamic=$r; $f string=json_encode($d); if($f!=="false"){return 2;}
$n=json_decode_mixed("null"); if(!isset($n)){return 3;}
$m mixed=$n; $s string=json_encode($m); if($s!=="null"){return 4;}
$bad=json_decode("{"); if(isset($bad)){return 5;}
$x ?int=null; $none string=json_encode($x); $x=0; $zero string=json_encode($x);
$b ?bool=false; $boolean string=json_encode($b);
$t ?string=""; $text string=json_encode($t);
$v ?float=1.25; $number string=json_encode($v);
if($none!=="null" || $zero!=="0" || $boolean!=="false" || $text!=="\u0022\u0022" || $number!=="1.25"){return 6;}
$i string=json_encode(3); $u string=json_encode(true); $q string=json_encode("a\0b"); $z string=json_encode(0.5);
if($i!=="3" || $u!=="true" || $z!=="0.5" || $q!=="\"a\\u0000b\""){return 7;}
return 0;
'''.replace(r'\u0022', r'\"')
with tempfile.TemporaryDirectory(prefix='json-source-') as tmp:
    work = Path(tmp)
    app, out = work/'app', work/'out'
    app.mkdir()
    manifest, main = app/'module.json', app/'main.php'
    deps = {'dependencies': {'runtime': {'modules': ['json']}}}
    manifest.write_text(json.dumps(dict(deps, general_api=True)))
    main.write_text(source)
    proc = service(app, out)
    try:
        result = invoke(proc)
        assert 'error' not in result, result
        baseline = artifacts(out)
        bundle = json.loads((out/'manifest.json').read_text())
        subprocess.run([sys.argv[1], '-std=c++23', '-stdlib=libc++', '--ld-path='+sys.argv[2],
                        '-I', root/'runtime/generated/public', *(out/u for u in bundle['translation_units']),
                        *bundle['link_inputs'], '-o', work/'run'], check=True)
        subprocess.run([work/'run'], check=True)
        manifest.write_text(json.dumps(deps))
        assert 'Unknown function: json_decode_mixed' in invoke(proc).get('error','')
        main.write_text('json_encode(1); return 0;')
        assert 'error' not in invoke(proc)
        manifest.write_text('{}')
        assert 'requires module runtime-json' in invoke(proc).get('error','')
        manifest.write_text(json.dumps(dict(deps, general_api=True)))
        main.write_text(source)
        assert 'error' not in invoke(proc) and artifacts(out) == baseline
    finally:
        proc.stdin.close()
        proc.wait()
