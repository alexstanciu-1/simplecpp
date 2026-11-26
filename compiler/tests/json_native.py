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
$wire string=$encoded;
if($wire!=="{\"data\":[3,-4,8],\"version\":1}"){return 21;}
$box Box={label:"a\0b"}; $label string=encodeBox($box);
if($label!=="{\"label\":\"a\\u0000b\"}"){return 22;}
$badFloat Reading={value:1.0e308 * 2.0}; $failedRecord=json_encode($badFloat);
if(isset($failedRecord)){return 23;}
$absent ?Box=null; $emptyOwner string=json_encode($absent); if($emptyOwner!=="null"){return 24;}
$read Packet=readPacket("{\"data\":[8,-2,7]}");
if($read->version!==1 || $read->data[1]!==-2){return 25;}
$reversed Packet=json_decode_as("{\"version\":9,\"data\":[]}",Packet::class);
if($reversed->version!==9 || !empty($reversed->data)){return 26;}
$middle=readPacket("{\"data\":[10,false,30]}"); if(isset($middle)){return 27;}
$tail=readPacket("{\"data\":[1]} garbage"); if(isset($tail)){return 28;}
$env Envelope=json_decode_as("{\"packet\":{\"data\":[2,7]},\"box\":{\"label\":\"nested\"},\"tag\":5}",Envelope::class);
if($env->box->label!=="nested" || $env->tag!==5 || $env->packet->version!==1 || $env->packet->data[1]!==7){return 34;}
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
return 0;
'''
with tempfile.TemporaryDirectory(prefix='json-source-') as tmp:
    work = Path(tmp)
    app, out = work/'app', work/'out'
    app.mkdir()
    manifest, main = app/'module.json', app/'main.php'
    deps = {'dependencies': {'runtime': {'modules': ['json']}}}
    manifest.write_text(json.dumps(dict(deps, general_api=True)))
    records = app/'records.php'
    definitions = ('data struct Packet { public vector<int> $data; public int $version=1; }'
                   'data class Box { public string $label; }'
                   'data struct Reading { public float $value; }'
                   'data class Envelope { public Packet $packet; public Box $box; public int $tag; }'
                   'data class Tracked { public Packet $packet; public function __destruct(){echo "root destroyed";} }')
    records.write_text(definitions)
    reader = app/'reader.php'
    decoder = ('function readPacket(string $text):result<Packet> {return json_decode_as($text,Packet::class);}'
               'function snapshot(result<Packet> &$saved):Packet { $copy Packet=$saved; return $copy; }')
    reader.write_text(decoder)
    (app/'writer.php').write_text('function encodeBox(Box $box):string {return json_encode($box);}')
    main.write_text(source)
    proc = service(app, out)
    try:
        result = invoke(proc)
        assert 'error' not in result, result
        baseline = artifacts(out)
        units = b'\n'.join(v for k, v in baseline.items() if k.suffix == '.cpp')
        assert b'Packet::decode(::scpp::Decoder& d)' in units
        assert b'd.next_field()' in units and b'd.next_element()' in units
        assert all(b'template<class scpp_escape_Field' not in data for data in baseline.values())
        assert b'decode_record<' not in units and b'record_result<' not in units
        bundle = json.loads((out/'manifest.json').read_text())
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
                        '-Wl,--wrap=_ZN4scpp12vector_int64C1ERKS0_,--wrap=_ZN4scpp12vector_int64aSERKS0_',
                        *bundle['link_inputs'], '-o', work/'run'], check=True)
        run = subprocess.run([work/'run'], check=True, capture_output=True)
        # Only the valid root reaches construction. Both documents contain a valid nested packet.
        assert run.stdout == b'root destroyed', run.stdout
        for target, diagnostic in [('Reading::class', 'No JSON decoder for Reading::$value'),
                                   ('UnknownRecord::class', 'concrete data record'), ('"Packet"', 'type marker')]:
            main.write_text('json_decode_as("{}",' + target + '); return 0;')
            failure = invoke(proc)
            assert diagnostic in failure.get('error', ''), failure
        main.write_text(source)
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
        manifest.write_text(json.dumps(deps))
        assert 'Unknown function: json_decode_mixed' in invoke(proc).get('error','')
        main.write_text('json_encode(1); return 0;')
        assert 'error' not in invoke(proc)
        reader.unlink()
        manifest.write_text('{}')
        assert 'requires module runtime-json' in invoke(proc).get('error','')
        manifest.write_text(json.dumps(dict(deps, general_api=True)))
        reader.write_text(decoder)
        main.write_text(source)
        assert 'error' not in invoke(proc) and artifacts(out) == baseline
    finally:
        proc.stdin.close()
        proc.wait()
