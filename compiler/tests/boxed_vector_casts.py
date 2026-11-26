"""Source-level vector snapshots, admission and incremental recovery in a JSON-off-capable runtime."""
import json
from pathlib import Path
import subprocess
import tempfile

from app_namespace_native import artifacts, invoke, service, write_build
from extends_native import source_edit


with tempfile.TemporaryDirectory(prefix='boxed-vector-casts-') as folder:
    root = Path(folder)
    app, out = root/'app', root/'out'
    app.mkdir()
    (app/'module.json').write_text('{"entry":"main.php"}')
    main = app/'main.php'
    helpers = app/'helpers.php'
    library = 'function numbers():vector<int> { echo "N"; $v vector<int> = [2,7]; return $v; }'
    source_edit(helpers, library)
    for flavor, code in {
        'js': 'function jsList(v:vector<string>):dynamic { return (dynamic)v; }',
        'cpp': 'mixed cppList(vector<int> v) { return (mixed)v; }',
    }.items():
        source_edit(app/('export.'+flavor), code)
    program = '''
$v vector<int> = [2,7];
$s vector<string> = ["long string beyond inline capacity", "last"];
$empty vector<int> = [];
$m = (mixed)$v; $d = (dynamic)$v;
$ms = (mixed)$s; $ds = jsList($s);
$v[0] = 99; $s[0] = "changed";
echo (int)$m[0], (int)$d[0], (int)$d[1], (string)$ms[0], (string)$ds[0], (string)$ms[1];
$em = (mixed)$empty; $ed = (dynamic)$empty;
echo count($em), count($ed);
$outer mixed = ["items" => (mixed)numbers()];
$child = $outer["items"]; echo (int)$child[1];
$once = (dynamic)numbers(); echo (int)$once[0];
$cpp = cppList($v); echo (int)$cpp[0];
return 8;
'''
    source_edit(main, program)
    proc = service(app, out)
    try:
        got = invoke(proc)
        assert 'error' not in got, got
        baseline = artifacts(out)
        native = root/'native'
        native.mkdir()
        units = [out/u for u in json.loads((out/'manifest.json').read_text())['translation_units']]
        subprocess.run(write_build(native, units), cwd=native, check=True, stdout=subprocess.DEVNULL)
        run = subprocess.run([native/'program'], capture_output=True)
        assert (run.returncode, run.stdout, run.stderr) == (
            8, b'227long string beyond inline capacitylong string beyond inline capacitylast00N7N299', b''), run
        for invalid in ['$bad mixed = $v;', '$bad dynamic = $s;', '$bad mixed = ["items" => $v];']:
            source_edit(main, program.replace('return 8;', invalid+'return 8;'))
            assert 'Unsupported boxing' in invoke(proc).get('error', ''), invalid
        source_edit(main, program)
        source_edit(helpers, 'struct Item {public int $id;}' + library.replace('vector<int>', 'vector<Item>').replace('[2,7]', '[]'))
        err = invoke(proc)
        assert 'Cannot export element type' in err.get('error', ''), err
        source_edit(helpers, library)
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
