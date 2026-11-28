"""Exercise the published JSON source functions through the project link manifest."""
import json
from concurrent.futures import ThreadPoolExecutor
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
if($n!==null || null!==json_decode("null") || json_decode("false")===null){return 59;}
if(json_decode("{")===null || null===json_decode_mixed("{") || json_decode("{")!==false){return 60;}
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
$checked=json_document_parse("[]",$diag);
$checkedKind string=json_node_kind(json_document_root($checked));
if($checkedKind!=="array"){return 49;}
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
if($diag->byte_offset!==7){return 52;}
$err json_parse_error=$diag;
if($err->byte_offset!==7 || $err->category!=="duplicate_key"){return 13;}
$failed=json_document_parse("[[]]",$diag,1); if(isset($failed)){return 14;}
$doc=json_document_parse("{}",$diag); if(isset($diag)){return 15;}
$reader=json_pull_reader_create(); $alias=$reader; $input="{\"a\":[1e9999,false,null,\"x\"],\"b\":42}";
$ok bool=$reader->start($input,"test.json",128); $input="changed";
$type string=$alias->kind(); $ok=$reader->begin_object();
$ok=$alias->next_property(); $key string=$reader->property_name();
$ok=$reader->begin_array(); $ok=$alias->next_element();
$raw string=$reader->read_number(); $ok=$reader->next_element(); $readerFlag bool=$alias->read_boolean();
$ok=$reader->next_element(); $ok=$reader->read_null(); $ok=$reader->next_element(); $token string=$reader->read_string();
$done bool=$reader->next_element(); $ok=$reader->next_property(); $integer int=$reader->read_integer(); $end bool=$reader->next_property();
if(!$ok || $type!=="object" || $key!=="a" || $raw!=="1e9999" || $readerFlag || $done || $end || $integer!==42 || $token!=="x"){return 16;}
$ok=$reader->finish_document(); if(!$ok || !isset($alias->finish_document())){return 17;}
$badReader=json_pull_reader_create(); $ok=$badReader->start("null","bad.json",1);
if(isset($badReader->read_string()) || isset($badReader->read_null()) || isset($badReader->start("true","",1))){return 18;}
$reject=json_pull_reader_create(); $ok=$reject->start("[]","",1);
if(isset($reject->reject("schema failure")) || isset($reject->kind())){return 19;}
$packet Packet={data:[3,-4,8]}; $encoded=json_encode($packet);
if(!isset($encoded)){return 20;}
$packed = (mixed)$packet; $numbers = $packed["data"]; if ((int)$numbers[1] !== -4) {return 48;}
$wire string=$encoded;
if($wire!=="{\"data\":[3,-4,8],\"version\":1}"){return 21;}
$box Box={label:"a\0b"}; $label string=encodeBox($box);
if($label!=="{\"label\":\"a\\u0000b\"}"){return 22;}
$badFloat Reading={value:1.0e308 * 2.0}; $failedRecord=json_encode($badFloat);
if(isset($failedRecord)){return 23;}
$absent ?Box=null; $emptyOwner string=json_encode($absent); if($emptyOwner!=="null"){return 24;}
$borrowed=readPacket("{\"version\":3,\"data\":[2]}");
$old=$borrowed->data[replaceIndex($borrowed)];
if($old!==2 || $borrowed->version!==70){return 57;}
$borrowed->version=8;
$borrowed->bump(2);
if($borrowed->version!==10 || $borrowed!==true){return 53;}
$selected=$borrowed->bump(replacePacket($borrowed));
if($selected!==14 || $borrowed->version!==70){return 54;}
$borrowed->version+=replacePacket($borrowed);
if($borrowed->version!==70){return 55;}
$borrowed->version=replacePacket($borrowed);
if($borrowed->version!==4){return 56;}
$read Packet=readPacket("{\"data\":[8,-2,7]}");
if($read->version!==1 || $read->data[1]!==-2){return 25;}
$reversed Packet=json_decode_as("{\"version\":9,\"data\":[]}",Packet::class);
if($reversed->version!==9 || !empty($reversed->data)){return 26;}
$middle=readPacket("{\"data\":[10,false,30]}"); if(isset($middle)){return 27;}
$tail=readPacket("{\"data\":[1]} garbage"); if(isset($tail)){return 28;}
$env Envelope=json_decode_as("{\"packet\":{\"data\":[2,7]},\"box\":{\"label\":\"nested\"},\"tag\":5}",Envelope::class);
if($env->box->label!=="nested" || $env->tag!==5 || $env->packet->version!==1 || $env->packet->data[1]!==7){return 34;}
$nested string=json_encode($env);
if($nested!=="{\"packet\":{\"data\":[2,7],\"version\":1},\"box\":{\"label\":\"nested\"},\"tag\":5}"){return 40;}
$failure=encodeLate(); if(isset($failure)){return 41;}
$pair Pair={a:$box,b:$box}; $pairText string=json_encode($pair);
if($pairText!=="{\"a\":{\"label\":\"a\\u0000b\"},\"b\":{\"label\":\"a\\u0000b\"}}"){return 47;}
$link Link={id:3}; $link->next=$link;
$cycle=json_encode($link); $link->next=null; if(isset($cycle)){return 42;}
$recovered string=json_encode($link); if($recovered!=="{\"id\":3,\"next\":null}"){return 43;}
$missing=json_decode_as("{\"packet\":{}}",Envelope::class); if(isset($missing)){return 39;}
$opened=json_pull_reader_create(); $started bool=$opened->start("{\"version\":4,\"data\":[9]}","",128);
$direct Packet=Packet::decode($opened);
if(!$started || $direct->version!==4 || $direct->data[0]!==9){return 35;}
$badRoot=json_decode_as("{\"packet\":{\"data\":[3]}} garbage",Tracked::class);
if(isset($badRoot)){return 36;}
$goodRoot=json_decode_as("{\"packet\":{\"data\":[3]}}",Tracked::class);
if(!isset($goodRoot)){return 37;}
$decodedBox Box=json_decode_as("{\"label\":\"hello\"}",Box::class);
if($decodedBox->label!=="hello"){return 29;}
$wire="{\"label\":\"a plain string that exceeds inline storage\",\"words\":[\"x\\u0000y\",\"\\uD83D\\uDE03\"]}";
$typedTextFixture Text=json_decode_as($wire,Text::class); $wire="replaced";
if($typedTextFixture->label!=="a plain string that exceeds inline storage" || $typedTextFixture->words[0]!=="x\0y" || $typedTextFixture->words[1]!=="😃"){return 52;}

return 0;
'''
with tempfile.TemporaryDirectory(prefix='json-source-') as tmp:
    work = Path(tmp)
    app, out = work/'app', work/'out'
    app.mkdir()
    manifest, main = app/'module.json', app/'main.phs'
    deps = {'dependencies': {'runtime': {'modules': ['json']}}}
    manifest.write_text(json.dumps(dict(deps, general_api=True)))
    records = app/'records.phs'
    definitions = ('data struct Packet { public vector<int> $data; public int $version=1; public function bump(int $n):int { $this->version+=$n; return $this->version; } }'
                   'data class Box { public string $label; }'
                   'data struct Text { public string $label; public vector<string> $words; }'
                   'data class Pair { public Box $a; public Box $b; }'
                   'data struct Late { public Packet $payload; public float $tail; }'
                   'data class Link { public int $id; public ?Link $next=null; }'
                   'data struct Reading { public float $value; }'
                   'data class Envelope { public Packet $packet; public Box $box; public int $tag; }'
                   'data class Tracked { public Packet $packet; public function __destruct(){echo "root destroyed";} }')
    definitions += 'data struct Wide {' + ''.join(f'public int $f{i}=0;' for i in range(65)) + '}'
    source = source.replace('return 0;', r"""
$wide Wide=json_decode_as("{\"f0\":1,\"f63\":2,\"f64\":3}",Wide::class);
if($wide->f0!==1 || $wide->f63!==2 || $wide->f64!==3){return 48;}
$dup=json_decode_as("{\"f64\":1,\"f64\":2}",Wide::class);if(isset($dup)){return 49;}
$wire="{\"words\":[\"x\\u0000y\"]}";
$cwords CppWords=readCppWords($wire); $jwords JsWords=readJsWords($wire); $pwords PyWords=readPyWords($wire);
if($cwords->words[0]!=="x\0y" || $jwords->words[0]!=="x\0y" || $pwords->words[0]!=="x\0y"){return 53;}
return 0;""")
    records.write_text(definitions)
    # Each frontend declares its own schema and calls its own generated decoder.
    flavor_sources = {
        'CppWords.cpp': ('data struct CppWords { vector<string> words; };'
         'CppWords readCppWords(string s) { return json_decode_as(s,CppWords::class); }'),
        'JsWords.js': ('data struct JsWords { words:vector<string>; }'
         'function readJsWords(s:string):JsWords { return json_decode_as(s,JsWords.class); }'),
        'PyWords.py': ('data class PyWords:\n    words: vector<string>\n'
         'def readPyWords(s: string) -> PyWords:\n    return json_decode_as(s,PyWords)\n'),
    }
    for name, body in flavor_sources.items():
        (app/name).write_text(body)
    reader = app/'reader.phs'
    decoder = ('function readPacket(string $text):result<Packet> {return json_decode_as($text,Packet::class);}'
               r'function replacePacket(result<Packet> &$r):int { $r=readPacket("{\"data\":[],\"version\":70}"); return 4; }'
               'function replaceIndex(result<Packet> &$r):int { replacePacket($r); return 0; }'
               'function snapshot(result<Packet> &$saved):Packet { $copy Packet=$saved; return $copy; }')
    # Packet requires a pinned payload in this program. Text uses inline record storage.
    for record in ('Packet', 'Text'):
        decoder += (f'function forward{record}(result<string> $r):result<{record}> {{'
                    'take($v,$e,$ok)=$r; return $e; }'
                    f'function forwarded{record}Line(result<string> $r):int {{'
                    f'take($v,$e,$ok)=forward{record}($r); return $e->get_line(); }}')
    families = {'Nullable': '?vector<string>', 'Result': 'result<vector<string>>',
                'Falseable': 'result_or_false<vector<string>>', 'Boolean': 'result_or_bool<vector<string>>'}
    for name, carrier in families.items():
        decoder += f'function grow{name}({carrier} &$slot):void {{ $slot->resize(2,"x"); }}'
    reader.write_text(decoder)
    (app/'writer.phs').write_text('function encodeBox(Box $box):string {return json_encode($box);}'
                                  'function encodeLate():result<string> {'
                                  '$late Late={payload:{data:[2,7]},tail:1.0e308*2.0};return json_encode($late);}')
    main.write_text(source)
    proc = service(app, out)
    try:
        result = invoke(proc)
        assert 'error' not in result, result
        baseline = artifacts(out)
        units = b'\n'.join(v for k, v in baseline.items() if k.suffix == '.cpp')
        assert b'((var_diag_' in units and b'.checked_ref()).byte_offset' in units
        assert b'.checked_ref()).version = 8LL;' in units
        assert b'.checked_ref()).version = replacePacket_' in units
        assert b'auto receiver_owner_guard = ' in units and b'auto& compound_target = (*receiver_owner_guard).version;' in units
        direct = next(line for line in units.splitlines() if b'_bump(' in line and b'2LL);' in line)
        assert b'[&]' not in direct and b'.value()' not in direct, direct
        for line in units.splitlines():
            if b'.resize(' in line or (b'.at(' in line and b'replaceIndex_' in line):
                assert b'[&]' not in line and b'.value()' not in line, line
        assert b'.pin()).data).at(' in units
        assert units.count(b'method_receiver.resize(') == 4
        assert b'::scpp::json::document_root((var_checked_' in units
        assert units.count(b'Packet::encode(::scpp::Encoder& out) const') == 1
        assert b'encode_record(' not in units and b'encode_owned_record(' not in units
        assert b'Packet::decode(::scpp::Decoder& d)' in units
        assert b'd.next_record_field(' in units and b'd.read_integers(' in units
        assert all(b'template<class scpp_escape_Field' not in data for data in baseline.values())
        assert b'decode_record<' not in units and b'record_result<' not in units
        bundle = json.loads((out/'manifest.json').read_text())
        for source_name in ['records.phs', 'CppWords.cpp', 'JsWords.js', 'PyWords.py']:
            unit = out/bundle['sources']['app/'+source_name]
            code = unit.read_text()
            assert 'd.read_strings(' in code and '::scpp::json::read_leaf_record(' in code, source_name
        records_unit = out/bundle['sources']['app/records.phs']
        envelope = records_unit.read_text().split('record_result_Envelope::from_text(', 1)[1].split('slot.present', 1)[0]
        assert '::scpp::json::read_record(' in envelope
        packet_header = next(out/h for h in bundle['headers'] if 'struct Packet {' in (out/h).read_text())
        sources = [out/u for u in bundle['translation_units'] if u != 'entry.cpp']
        sources.append(root/'compiler/tests/fixtures/json_record_copies.cpp')

        def compile_unit(source):
            obj = work/(source.stem + '.o')
            subprocess.run([sys.argv[1], '-std=c++23', '-stdlib=libc++', '-I', root/'runtime/generated/public',
                            '-DSCPP_PACKET_HEADER="' + str(packet_header) + '"', '-c', source, '-o', obj], check=True)
            return obj

        # Compile units independently so the cross-file header and linkage checks stay on the fast path.
        with ThreadPoolExecutor(max_workers=4) as pool:
            objects = list(pool.map(compile_unit, sources))
        subprocess.run([sys.argv[1], '-stdlib=libc++', '--ld-path='+sys.argv[2], *objects,
                        '-Wl,--wrap=_Znwm,--wrap=_ZN4scpp12vector_int64C1ERKS0_,--wrap=_ZN4scpp12vector_int64aSERKS0_',
                        *bundle['link_inputs'], '-o', work/'run'], check=True)
        run = subprocess.run([work/'run'], check=True, capture_output=True)
        # Only the valid root reaches construction. Both documents contain a valid nested packet.
        assert run.stdout == b'root destroyed', run.stdout
        # Removing the only pin demand changes Packet's generated result layout across units.
        main.write_text(r'function useInt(int $n):int {return $n;} $r=readPacket("{\"data\":[]}"); '
                        '$r->version+=1; return useInt($r->version);')
        reduced = invoke(proc)
        assert 'error' not in reduced, reduced
        cold = service(app, work/'reduced')
        try:
            assert 'error' not in invoke(cold)
            assert artifacts(work/'reduced') == artifacts(out)
            reduced_units = artifacts(out)
            header = next(data for data in reduced_units.values() if b'struct record_result_Packet {' in data)
            assert b'std::shared_ptr<Value> payload' not in header and b'#include <memory>' not in header
            entry = reduced_units[Path(bundle['sources']['app/main.phs'])]
            assert b'.pin()' not in entry and entry.count(b'.checked_ref()).version') == 2, entry
        finally:
            cold.stdin.close()
            cold.wait()
        for target, diagnostic in [('Reading::class', 'No JSON decoder for Reading::$value'),
                                   ('UnknownRecord::class', 'concrete data record'), ('"Packet"', 'type marker')]:
            main.write_text('json_decode_as("{}",' + target + '); return 0;')
            failure = invoke(proc)
            assert diagnostic in failure.get('error', ''), failure
        main.write_text(source)
        assert 'error' not in invoke(proc) and artifacts(out) == baseline
        reader.write_text(decoder.replace('forwardText(result<string> $r):result<Text>', 'forwardText(result<string> $r):int'))
        assert 'conversion from Exception to int64' in invoke(proc).get('error', '')
        reader.write_text(decoder)
        assert 'error' not in invoke(proc) and artifacts(out) == baseline
        records.write_text(definitions.replace('int $version=1', 'hash<int,int> $version'))
        failed = invoke(proc)
        assert 'Packet::$version' in failed.get('error', '') and artifacts(out) == baseline, failed
        records.write_text(definitions)
        assert 'error' not in invoke(proc) and artifacts(out) == baseline
        fresh = service(app, work/'fresh')
        try:
            assert 'error' not in invoke(fresh) and artifacts(work/'fresh') == baseline
        finally:
            fresh.stdin.close()
            fresh.wait()
        py = app/'PyWords.py'
        py.write_text(flavor_sources[py.name].replace('    words:', '    nested: Packet\n    words:'))
        assert 'error' not in invoke(proc)
        updated = (out/bundle['sources']['app/PyWords.py']).read_text()
        assert '::scpp::json::read_record(' in updated and '::scpp::json::read_leaf_record(' not in updated
        py.write_text(flavor_sources[py.name].replace('s: string)', 's: string, PyWords: int)'))
        assert 'type marker' in invoke(proc).get('error', '')
        py.write_text(flavor_sources[py.name])
        assert 'error' not in invoke(proc) and artifacts(out) == baseline
        for name in flavor_sources:
            (app/name).unlink()
        manifest.write_text(json.dumps(deps))
        assert 'Unknown function: json_decode_mixed' in invoke(proc).get('error','')
        main.write_text('json_encode(1); return 0;')
        assert 'error' not in invoke(proc)
        reader.unlink()
        manifest.write_text('{}')
        assert 'requires module runtime-json' in invoke(proc).get('error','')
        manifest.write_text(json.dumps(dict(deps, general_api=True)))
        for name, body in flavor_sources.items():
            (app/name).write_text(body)
        reader.write_text(decoder)
        main.write_text(source)
        assert 'error' not in invoke(proc) and artifacts(out) == baseline
    finally:
        proc.stdin.close()
        proc.wait()
