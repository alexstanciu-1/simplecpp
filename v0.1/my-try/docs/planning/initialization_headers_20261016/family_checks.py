"""Native compiler and runtime witnesses for narrow typed/mixed/dynamic adapters."""
import json,subprocess
import benchmark_support as b
B=b.BASE
sources={
 'typed':'''$v vector<int> = [10, 20, 30];
$h hash<int> = ["10" => 1, "08" => 2];
$nested vector<vector<int>> = [[1], [2, 3]];
$empty vector<int> = [];
if ($v[2] !== 30 || $h["08"] !== 2 || $nested[1][1] !== 3 || count($empty) !== 0) { return 1; }
echo "typed_PASS\\n"; return 0;''',
 'mixed':'''$m mixed = ["10" => null, "08" => false, "nested" => {x: 7}];
$n int = $m["nested"]["x"]; $f bool = $m["08"]; $nil mixed = $m[10];
$empty mixed = [];
if ($n !== 7 || $f !== false || $nil !== null || count($empty) !== 0) { return 1; }
echo "mixed_PASS\\n"; return 0;''',
 'dynamic':'''$child dynamic = {x: 7};
$d dynamic = {child: $child, no: false, nil: null};
$alias = $d; $alias["child"]["x"] = 9;
$n int = $child["x"]; $f bool = $d["no"]; $nil dynamic = $d["nil"];
$empty dynamic = {};
if ($n !== 9 || $f !== false || $nil !== null || count($empty) !== 0) { return 1; }
echo "dynamic_PASS\\n"; return 0;''',
 'typed-mixed':'''$v vector<mixed> = [{x: 7}, false];
$n int = $v[0]["x"]; $f bool = $v[1];
if ($n !== 7 || $f !== false) { return 1; }
echo "typed-mixed_PASS\\n"; return 0;''',
 'typed-dynamic':'''$child dynamic = {x: 7}; $v vector<dynamic> = [$child, {x: 8}];
$v[0]["x"] = 9; $n int = $child["x"]; $other int = $v[1]["x"];
if ($n !== 9 || $other !== 8) { return 1; }
echo "typed-dynamic_PASS\\n"; return 0;''',
 'mixed-duplicate':'$m mixed = ["10" => null, 10 => 1]; return 0;',
 'dynamic-duplicate':'$d dynamic = ["10" => false, 10 => 1]; return 0;'
}
def main():
 root=B/'family-results';root.mkdir();rows=[]
 for opt in [0,1,2]:
  for name,source in sources.items():
   tag=f'{name}-O{opt}';src=B/'family-source'/name;src.mkdir(parents=True,exist_ok=True);(src/'main.phs').write_text(source)
   b.APP=src;b.OPTIMIZATION=opt;b.POLICY_LEVEL=opt;b.VARIANT='outlined';b.EXE=B/'phpp/.prism/build/main'
   b.PUBLISHED=B/('family-published-'+tag);b.BUILD=B/('family-build-'+tag);b.RESULTS=root/tag;b.RESULTS.mkdir()
   row=b.measure('build',False)
   p=subprocess.run([str(b.BUILD/'app')],capture_output=True,text=True)
   (b.RESULTS/'application.txt').write_text(p.stdout+p.stderr)
   if name.endswith('-duplicate'):assert p.returncode!=0 and 'duplicate key' in p.stderr,(tag,p.returncode,p.stdout,p.stderr)
   else:assert p.returncode==0 and p.stdout==name+'_PASS\n',(tag,p.returncode,p.stdout,p.stderr)
   text=''.join(f.read_text() for f in b.PUBLISHED.rglob('*.cpp'))
   families=name.split('-') if name.startswith('typed-') else [name.split('-')[0]]
   for family in families:assert f'scpp/data_initializer_{family}.hpp' in text
   assert '"scpp/data_initializer.hpp"' not in text
   for other in ['typed','mixed','dynamic']:
    if other not in families:assert f'"scpp/data_initializer_{other}.hpp"' not in text
   row['case']=tag;row['application_exit']=p.returncode;rows.append(row)
   (root/'summary.json').write_text(json.dumps(rows,indent=2))
if __name__=='__main__':b.run_tasks([b.Task('native-initializer-families',main)])
