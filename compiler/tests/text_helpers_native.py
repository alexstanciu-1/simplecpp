"""Compile source callers against the selected text helper publication."""
import json
from pathlib import Path
import subprocess
import sys
import tempfile

from app_namespace_native import artifacts, invoke, service

root = Path(__file__).resolve().parents[2]
cxx, linker, archive = sys.argv[1:]
source = r'''
$s="é\0x";
if(string_byte_len($s)!==4 || string_byte_at($s,1)!==169 || string_byte_at($s,-1)!==-1){return 1;}
$zero=string_byte_find($s,"é"); $miss=string_byte_find($s,"z");
$end int=string_byte_find($s,"",4);
if(!isset($zero) || isset($miss) || $end!==4){return 2;}
$z int=$zero; if($z!==0){return 3;}
if(bin2hex(string_byte_slice($s,1,2))!=="a900" || string_byte_slice($s,3,9223372036854775807)!=="x"){return 4;}
if(!string_byte_slice_equals($s,4,0,"") || string_byte_slice_equals($s,3,2,"x")){return 5;}
$ints hash<string,int> = []; $ints[19]=""; $ints[2]="b";
$keys hash<string,string> = []; $keys["05"]="a"; $keys["5"]="b";
if(implode("|",$ints)!=="|b" || implode("|",$keys)!=="a|b" || implode("|",["","b"])!=="|b"){return 6;}
$text="é中😀\0";
if(strlen($text)!==11 || string_utf8_codepoint_count($text)!==5){return 7;}
if(string_utf8_codepoint_at($text,1)!==769 || string_utf8_codepoint_at($text,3)!==128512 ||
   string_utf8_codepoint_at($text,4)!==0 || string_utf8_codepoint_at($text,5)!==-1){return 8;}
if(string_utf8_slice_codepoints($text,2,9223372036854775807)!=="中😀\0"){return 9;}
$invalid="é".string_byte_from_int(255);
if(string_utf8_codepoint_count($invalid)!==3 || string_utf8_codepoint_at($invalid,0)!==195 ||
   bin2hex(string_utf8_slice_codepoints($invalid,1,2))!=="a9ff"){return 10;}
return 0;
'''

with tempfile.TemporaryDirectory(prefix='text-helpers-') as tmp:
    work = Path(tmp)
    app, out = work/'app', work/'out'
    app.mkdir()
    main = app/'main.php'
    main.write_text(source)
    manifest = app/'module.json'
    manifest.write_text('{"general_api":true}')
    proc = service(app, out)
    try:
        result = invoke(proc)
        assert 'error' not in result, result
        original = artifacts(out)
        bundle = json.loads((out/'manifest.json').read_text())
        subprocess.run([cxx, '-std=c++23', '-stdlib=libc++', f'--ld-path={linker}',
                        '-I', root/'runtime/generated/public', *(out/p for p in bundle['translation_units']),
                        archive, '-o', work/'run'], check=True)
        subprocess.run([work/'run'], check=True)
        manifest.write_text('{}')
        assert 'Unknown function: string_byte_len' in invoke(proc).get('error', '')
        manifest.write_text('{"general_api":true}')
        assert 'error' not in invoke(proc)
        assert artifacts(out) == original
    finally:
        proc.stdin.close()
        proc.wait()
