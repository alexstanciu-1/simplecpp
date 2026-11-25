"""Bounded scalar dbg acceptance under default prelowering; run on dev002."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks

def cases():
    rows={}
    original=ROOT/'tests/php/debug/strict_level_01/strict_debug_001_dbg_scalar.phs'
    rows['original']=dict(source=original.read_text(),expected='[dbg] catalog_dbg\n  value: 42\ndone\n')
    for typ,value,printed in [('int','42','42'),('bool','false','false'),('float','1.5','1.5'),('string','"bytes"','"bytes"')]:
        rows[typ]=dict(source=f'dbg({value});\ndbg("label",{value});\ndbg("flags",{value},DBG_VALUE|DBG_DEPTH_0);\n',
            expected=f'[dbg] main.phs:1 in <runtime>\n  type: {typ}_t\n  value: {printed}\n[dbg] main.phs:2 in <runtime> label\n  type: {typ}_t\n  value: {printed}\n[dbg] flags\n  value: {printed}\n')
    rows['snapshots']=dict(source='''function label(string &$value,int &$trace):string {$trace=$trace*10+1;return $value;}
function value(string &$label,int &$trace):int {$label="changed";$trace=$trace*10+2;return 42;}
function flags(int &$trace):int {$trace=$trace*10+3;return DBG_VALUE|DBG_DEPTH_0;}
$text="original";$trace=0;
dbg(label($text,$trace),value($text,$trace),flags($trace));
echo $text,":",$trace,"\\n";
''',expected='[dbg] original\n  value: 42\nchanged:123\n')
    rows['invalid_depth']=dict(source='dbg("bad",42,DBG_DEPTH_0|DBG_DEPTH_1);echo "continued\\n";',expected='[dbg] <debug inspection failed: dbg config error: multiple DBG_DEPTH_* flags provided>\ncontinued\n')
    rows['line_endings']=dict(source='// first\r\n\r\nfunction show():void {\r\n dbg("where",42,DBG_SOURCE|DBG_VALUE|DBG_DEPTH_0);\r\n}\r\nshow();',expected='[dbg] main.phs:4 where\n  value: 42\n')
    rows['source_shadow']=dict(source='function dbg(int $n):void {echo \"source:\",$n,\"\\n\";}dbg(7);',expected='source:7\n')
    return rows

def main():
    parser=argparse.ArgumentParser(description=__doc__);parser.add_argument('--results',type=Path,required=True)
    out=parser.parse_args().results.resolve();out.mkdir(parents=True,exist_ok=False)
    selected=cases();(out/'cases.json').write_text(json.dumps(selected,indent=2)+'\n')
    with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
        def verify(name,case):
            folder=out/name;(folder/'source').mkdir(parents=True);(folder/'source/main.phs').write_bytes(case['source'].encode())
            runner.run(name+'-publish',['php',APP/'tests/scalar_debug_publish.php',folder],cli_reason='Default scalar debug publication')
            builds={}
            for phase in ['cold','warm']:
                build=json.loads(runner.run(name+'-'+phase,['php',APP/'tests/fixtures/native_cache_driver.php',folder/'published',folder/'cache',ROOT/'runtime/include','/usr/bin/clang++','--no-pch'],timeout=300,cli_reason='Default scalar debug native execution'))
                if phase=='warm':assert build['compiled']==build['linked']==0,build
                actual=runner.run(name+'-'+phase+'-run',[build['executable']]);assert actual==case['expected'].encode(),(name,actual,case['expected'])
                builds[phase]=build
            (folder/'result.json').write_text(json.dumps(dict(passed=True,builds=builds),indent=2)+'\n')
        def recovery():
            runner.run('recovery',['php',APP/'tests/scalar_debug_recovery.php',out/'recovery'],timeout=120)
        def metadata():
            runner.run('metadata',['php',APP/'tests/scalar_debug_metadata.php',out/'metadata'],timeout=120)
        run_tasks([Task(n,lambda n=n,c=c:verify(n,c)) for n,c in selected.items()]+[Task('recovery',recovery),Task('metadata',metadata)])
    (out/'summary.json').write_text(json.dumps(dict(passed=True,cases=list(selected),recovery=True),indent=2)+'\n')
if __name__=='__main__':main()
