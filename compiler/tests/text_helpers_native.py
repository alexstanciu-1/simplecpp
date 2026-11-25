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
$p=string_parts_builder_create();
string_parts_builder_reserve($p,8);
string_parts_builder_append_bool($p,false);
if(string_parts_builder_capacity($p)<8 || string_parts_builder_count($p)!==1 || string_parts_builder_byte_len($p)!==0){return 11;}
string_parts_builder_append_string($p,"a\0b");
string_parts_builder_append_int($p,-9223372036854775807-1);
string_parts_builder_append_bool($p,true);
$copy=$p; $snapshot=string_parts_builder_to_string($p); string_parts_builder_clear($p);
if($snapshot!=="a\0b-92233720368547758081" || string_parts_builder_count($copy)!==4 ||
    string_parts_builder_count($p)!==0 || string_parts_builder_byte_len($copy)!==24){return 12;}
$b=text_builder_create(); text_builder_reserve_bytes($b,80);
if(text_builder_capacity_bytes($b)<80 || text_builder_byte_len(text_builder_create())!==0){return 13;}
text_builder_append_string($b,$snapshot); $alias=&$b;
append_one($alias);
if(observe($b)!==25 || observe($alias)!==25){return 14;}
$other=changed_copy($b); $saved=text_builder_to_string($b);
if(text_builder_take_string($b)!==$saved || observe($b)!==0 || $saved!==$snapshot."1"){return 15;}
text_builder_append_int($b,-42); text_builder_append_bool($b,false);
if(text_builder_to_string($b)!=="-42" || observe($other)!==26){return 16;}
text_builder_clear($b);
return observe($b);
'''

with tempfile.TemporaryDirectory(prefix='text-helpers-') as tmp:
    work = Path(tmp)
    app, out = work/'app', work/'out'
    app.mkdir()
    main = app/'main.php'
    main.write_text(source)
    declarations = """
function append_one(text_builder &$writer): void { text_builder_append_bool($writer,true); }
function observe(const text_builder &$view): int { return text_builder_byte_len($view); }
function changed_copy(text_builder $copy): text_builder { append_one($copy); return $copy; }
"""
    helpers = app/'builder_functions.php'
    helpers.write_text(declarations)
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
        for bad, expected in [
            ('text_builder_clear($view);', 'read-only'),
            ('text_builder_clear(text_builder_create());', 'reference'),
        ]:
            helpers.write_text(declarations.replace('return text_builder_byte_len($view);',
                                                    bad + 'return 0;'))
            failed = invoke(proc)
            assert expected in failed.get('error', '').lower(), failed
        helpers.write_text(declarations.replace('text_builder_append_bool($writer,true);',
                                                'string_parts_builder_clear($writer);'))
        failed = invoke(proc)
        assert 'exact' in failed.get('error', '').lower(), failed
        helpers.write_text(declarations)
        assert 'error' not in invoke(proc)
        manifest.write_text('{}')
        assert 'Unknown function:' in invoke(proc).get('error', '')
        manifest.write_text('{"general_api":true}')
        assert 'error' not in invoke(proc)
        assert artifacts(out) == original
    finally:
        proc.stdin.close()
        proc.wait()
