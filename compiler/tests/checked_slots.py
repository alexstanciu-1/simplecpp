"""Repeat saved-key lookup after RHS effects with application and published outer collections."""
import json
from pathlib import Path
import subprocess
import sys
import tempfile

from app_namespace_native import invoke, service
from native_build import write_build


source = '''
function indexKey():int {echo "I"; return 0;}
function textKey():string {echo "K"; return "05";}
function leaf():int {echo "L"; return 1;}
function swapVector(vector<vector<string>> &$v, bool $drop):string {
    echo "R"; $row vector<string>=["sibling","new"]; if($drop){$row->remove(1);} $v=[$row]; return "!";
}
function swapHash(hash<vector<string>,string> &$h, bool $drop):string {
    echo "R"; $h->remove("05"); $row vector<string>=["sibling","new"]; if($drop){$row->remove(1);} $h["05"]=$row; return "!";
}
function dropHashLeaf(vector<hash<string,string>> &$v):string {
    echo "R"; $row=$v[0]; $row->remove("05"); $v[0]=$row; return "!";
}
function eraseParent(hash<vector<string>,string> &$h):string { echo "R"; $h->remove("05"); return "!"; }
$v vector<vector<string>>=[["before","old"]]; $h hash<vector<string>,string>=[];
$h["5"]=["other"]; $h["05"]=["before","old"]; $savedV=$v; $savedH=$h;
if(cli_argc()===2){$v[indexKey()][leaf()] .= swapVector($v,true);return 90;}
if(cli_argc()===3){$h[textKey()][leaf()] .= swapHash($h,true);return 91;}
if(cli_argc()===4){
    $row hash<string,string>=["5"=>"other","05"=>"old"]; $rows vector<hash<string,string>>=[$row];
    $rows[indexKey()][textKey()] .= dropHashLeaf($rows); return 92;
}
if(cli_argc()===5){$h[textKey()][leaf()] .= eraseParent($h);return 93;}
$v[indexKey()][leaf()] .= swapVector($v,false);
$h[textKey()][leaf()] .= swapHash($h,false);
if($v[0][1]!=="old!" || $v[0][0]!=="sibling" || $savedV[0][1]!=="old"){return 1;}
if($h["05"][1]!=="old!" || $h["05"][0]!=="sibling" || $savedH["05"][1]!=="old" || $h["5"][0]!=="other"){return 2;}
return 0;
'''
with tempfile.TemporaryDirectory(prefix='checked-slots-') as tmp:
    work = Path(tmp)
    app, out = work/'app', work/'out'
    app.mkdir()
    (app/'module.json').write_text('{}')
    (app/'main.php').write_text(source)
    proc = service(app, out)
    try:
        response = invoke(proc)
        assert 'error' not in response, response
    finally:
        proc.stdin.close()
        proc.wait()
    bundle = json.loads((out/'manifest.json').read_text())
    units = [out / u for u in bundle['translation_units']]
    cpp = '\n'.join(u.read_text() for u in units)
    assert '.checked_slot(' in cpp
    types = ('scpp::vector_vector_string', 'scpp::hash_vector_string_string') if sys.argv[1] == 'published' else ('scpp_vector_', 'scpp_hash_')
    for name in types:
        assert name in cpp, name
    subprocess.run(write_build(work, units), cwd=work, check=True)
    for args, trace, error in [([], b'ILRKLR', b''), (['v'], b'ILR', b'sequence_index_out_of_range\n'),
                               (['h','h'], b'KLR', b'sequence_index_out_of_range\n'),
                               (['x'] * 3, b'IKR', b'hash_missing_key\n'), (['x'] * 4, b'KLR', b'hash_missing_key\n')]:
        result = subprocess.run([work/'program', *args], capture_output=True)
        assert (result.returncode, result.stdout, result.stderr) == (int(bool(error)), trace, error), result
