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
if($none!=="null" || $zero!=="0" || $boolean!=="false" || $text!=="\"\"" || $number!=="1.25"){return 6;}
$i string=json_encode(3); $u string=json_encode(true); $q string=json_encode("a\0b"); $z string=json_encode(0.5);
if($i!=="3" || $u!=="true" || $z!=="0.5" || $q!=="\"a\\u0000b\""){return 7;}
$diag ?json_parse_error=null;
$doc json_document=json_document_parse("{\"items\":[1e0,true,\"a\\u0000b\"],\"0\":null}",$diag);
if(isset($diag)){return 8;}
$root json_node=json_document_root($doc);
$doc=json_document_parse("[]",$diag,1);
$items json_node=json_node_member($root,"items");
$numNode json_node=json_node_at($items,0); $spelling string=json_node_number($numNode);
if($spelling!=="1e0" || isset(json_node_int($numNode))){return 9;}
$word json_node=json_node_at($items,2); $decodedText string=json_node_string($word);
$size int=json_node_size($items); $first string=json_node_key($root,0);
$present bool=json_node_has($root,"0"); $flag json_node=json_node_at($items,1); $truth bool=json_node_boolean($flag);
if($size!==3 || $first!=="items" || !$present || !$truth || $decodedText!=="a\0b"){return 10;}
$leaf json_node=json_node_member($root,"0"); $kind string=json_node_kind($leaf);
if($kind!=="null" || isset(json_node_member($root,"absent"))){return 11;}
$failed=json_document_parse("{\"a\":0,\"\\u0061\":1}",$diag);
if(isset($failed) || !isset($diag)){return 12;}
$err json_parse_error=$diag;
if($err->byte_offset!==7 || $err->category!=="duplicate_key"){return 13;}
$failed=json_document_parse("[[]]",$diag,1); if(isset($failed)){return 14;}
$doc=json_document_parse("{}",$diag); if(isset($diag)){return 15;}
return 0;
'''
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
