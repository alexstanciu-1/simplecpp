"""Field-derived record exports through the resident compiler and native application runtime."""
import json
from pathlib import Path
import subprocess
import tempfile

from app_namespace_native import artifacts, invoke, service, write_build
from extends_native import source_edit


with tempfile.TemporaryDirectory(prefix='boxed-record-casts-') as folder:
    root = Path(folder)
    app, out = root/'app', root/'out'
    app.mkdir()
    (app/'module.json').write_text('{"entry":"main.php"}')
    record = app/'packet.php'
    schema = '''data struct Packet {
        public int $version = 1;
        public vector<int> $data;
        public string $label;
        public vector<string> $tags;
    }'''
    source_edit(record, schema)
    source_edit(app/'export.cpp', 'mixed asMixed(Packet p) {return (mixed)p;}')
    source_edit(app/'export.js', 'function asDynamic(p:Packet):dynamic {return (dynamic)p;}')
    main = app/'main.php'
    program = '''
function once(Packet $p):Packet {echo "!";return $p;}
$p Packet = {tags:["long text beyond inline string capacity","z"],data:[2,7]};
$p->version = 3; $p->label = "old";
$m = (mixed)once($p); $d = (dynamic)once($p);
$p->version = 4; $p->data = [99]; $p->label = "new"; $p->tags = [];
'''
    body = '''
foreach (BOX as $key => $value) {echo (string)$key, "|";}
$data = BOX["data"]; $tags = BOX["tags"];
echo (int)BOX["version"], (string)BOX["label"], (int)$data[0], (int)$data[1], (string)$tags[0], (string)$tags[1];
'''
    program += body.replace('BOX', '$m') + body.replace('$', '$second_').replace('BOX', '$d')
    program += '''$a = asMixed($p); $b = asDynamic($p);
$last_tags = $a["tags"]; echo count($last_tags), (int)$b["version"]; return 8;'''
    source_edit(main, program)
    proc = service(app, out)
    try:
        result = invoke(proc)
        assert 'error' not in result, result
        baseline = artifacts(out)
        code = b'\n'.join(baseline.values())
        assert b'scpp_escape_json_encode' not in code and b'scpp/json/' not in code
        assert code.count(b'Packet::encode(::scpp::Encoder& out) const') == 1
        build = root/'native'
        build.mkdir()
        manifest = json.loads((out/'manifest.json').read_text())
        assert all('scpp_json' not in lib for lib in manifest['link_inputs'])
        units = [out/u for u in manifest['translation_units']]
        subprocess.run(write_build(build, units), cwd=build, check=True)
        run = subprocess.run([build/'program'], capture_output=True)
        one = b'version|data|label|tags|3old27long text beyond inline string capacityz'
        assert (run.returncode, run.stdout, run.stderr) == (8, b'!!'+one*2+b'04', b''), run

        source_edit(record, schema.replace('public string $label;', 'public string $label; public hash<int,int> $lookup;'))
        error = invoke(proc).get('error', '')
        assert 'Cannot export Packet::$lookup' in error, error
        source_edit(record, schema)
        assert 'error' not in invoke(proc) and artifacts(out) == baseline
        for bad in ('$bad mixed = $p;', '$bad dynamic = ["value" => $p];'):
            source_edit(main, program.replace('return 8;', bad+'return 8;'))
            result = invoke(proc)
            assert 'Unsupported boxing' in result.get('error', ''), result
        source_edit(main, program)
        assert 'error' not in invoke(proc) and artifacts(out) == baseline
    finally:
        proc.stdin.close()
        proc.wait()
    fresh = root/'fresh'
    proc = service(app, fresh)
    try:
        assert 'error' not in invoke(proc) and artifacts(fresh) == baseline
    finally:
        proc.stdin.close()
        proc.wait()
