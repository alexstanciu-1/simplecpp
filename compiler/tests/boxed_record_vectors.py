"""Boxed snapshots of application vectors; an authored destructor observes native element copies."""
import json
from pathlib import Path
import subprocess
import tempfile

from app_namespace_native import artifacts, invoke, service, write_build
from extends_native import source_edit


with tempfile.TemporaryDirectory(prefix='record-vector-export-') as folder:
    root = Path(folder)
    app, out = root/'app', root/'out'
    app.mkdir()
    (app/'module.json').write_text('{"entry":"main.php"}')
    point = app/'point.php'
    schema = '''data struct Point { public int $x; public vector<int> $samples;
        public function __destruct(){echo "D";} }'''
    source_edit(point, schema)
    source_edit(app/'packet.php', 'data struct Packet {public vector<Point> $points; public int $version=7;}')
    main = app/'main.php'
    code = '''
function once():vector<Point> {echo "!"; $v vector<Point> = [];return $v;}
function copyPoint(vector<Point> &$v):void {$copy=$v[0];echo $copy->x;}
$a Point = {x:2,samples:[3,4]}; $b Point = {x:5,samples:[6]};
$v vector<Point> = []; $v[]=$a; $v[]=$b;
$p Packet = {points:$v};
echo "<"; $m=(mixed)$v; $d=(dynamic)$v; $pm=(mixed)$p; $pd=(dynamic)$p; echo ">";
echo "["; copyPoint($v); echo "]";
$v[0]=$b; $p->points=[]; $em=(mixed)once(); $ed=(dynamic)once();
$empty=(mixed)$p; $emptyPoints=$empty["points"]; $de=(dynamic)$p; $dep=$de["points"];
echo "|",count($em),count($ed),count($emptyPoints),count($dep),":";
$ml=$pm["points"]; $dl=$pd["points"];
'''
    for i, name in enumerate(['m', 'd', 'ml', 'dl']):
        code += f'$a{i}=${name}[0]; $b{i}=${name}[1]; $s{i}=$a{i}["samples"];'
        code += f'echo (int)$a{i}["x"],(int)$s{i}[1],(int)$b{i}["x"];'
    code += 'echo (int)$pm["version"],(int)$pd["version"],"|"; return 8;'
    source_edit(main, code)
    proc = service(app, out)
    try:
        result = invoke(proc)
        assert 'error' not in result, result
        baseline = artifacts(out)
        manifest = json.loads((out/'manifest.json').read_text())
        assert not any('scpp_json' in lib for lib in manifest['link_inputs'])
        build = root/'native'
        build.mkdir()
        units = [out/u for u in manifest['translation_units']]
        subprocess.run(write_build(build, units), cwd=build, check=True)
        run = subprocess.run([build/'program'], capture_output=True)
        assert run.returncode == 8 and not run.stderr, run
        assert b'<>[2D]' in run.stdout, run.stdout
        assert run.stdout.count(b'!') == 2 and b'|0000:24524524524577|' in run.stdout, run.stdout

        invalid = schema.replace('public int $x;', 'public int $x; public hash<int,int> $bad;')
        source_edit(point, invalid)
        result = invoke(proc)
        assert 'Point::$bad' in result.get('error', ''), result
        source_edit(point, schema)
        assert 'error' not in invoke(proc) and artifacts(out) == baseline

        # With only the field cast present, its own dependency must invalidate on a leaf edit.
        source_edit(main, 'function snapshot(Packet $p):dynamic { return (dynamic)$p; } return 0;')
        assert 'error' not in invoke(proc)
        field_baseline = artifacts(out)
        source_edit(point, invalid)
        error = invoke(proc).get('error', '')
        assert 'Packet::$points -> Point::$bad' in error, error
        source_edit(point, schema)
        assert 'error' not in invoke(proc) and artifacts(out) == field_baseline
        source_edit(main, code)
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
