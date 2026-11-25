"""Allocation/use/destruction witness; compare generated policies at equal Clang levels."""
import json, subprocess, time
import benchmark_support as b
B=b.BASE
S=B/'runtime-probe-source';S.mkdir()
(S/'types.phs').write_text('''class Counter { public static int $destroyed = 0; }
class Item { public int $number = 7; public function __destruct() { Counter::$destroyed += 1; } }
class Bag {
 public $first hash<Item> = []; public $second hash<Item> = [];
 public $third hash<Item> = []; public $fourth hash<Item> = [];
 public int $marker = 3;
 public function store(Item $item): void { $this->first["x"] = $item; }
 public function read(): int { return $this->first["x"]->number; }
}
''')
(S/'main.phs').write_text('''$sum int = 0; $i int = 0;
while ($i < 200000) {
 $a = new Bag(); $b = new Bag(); $alias = $a; $item = new Item();
 $alias->store($item); $sum += $a->read();
 if (count($b->first) !== 0 || $a->marker !== 3) { return 1; }
 $i += 1;
}
if ($sum !== 1400000 || Counter::$destroyed !== 200000) { return 2; }
echo "RUNTIME_POLICY_PASS\n";
return 0;''')

def main():
    root=B/'runtime-results';root.mkdir();apps={};builds=[]
    b.APP=S
    for opt,policy in [(0,2),(0,0),(1,2),(1,1),(2,2),(3,3)]:
        tag=f'O{opt}-policy{policy}'
        b.OPTIMIZATION=opt;b.POLICY_LEVEL=policy
        b.PUBLISHED=B/('runtime-published-'+tag);b.BUILD=B/('runtime-build-'+tag)
        b.RESULTS=root/tag;b.RESULTS.mkdir()
        builds.append(b.measure('build',False));apps[tag]=b.BUILD/'app'
        p=subprocess.run([str(apps[tag])],capture_output=True,text=True)
        assert p.returncode==0 and p.stdout=='RUNTIME_POLICY_PASS\n',(tag,p.returncode,p.stdout,p.stderr)
    rows=[]
    for trial in range(7):
        keys=list(apps)
        if trial%2:keys.reverse()
        for tag in keys:
            report=root/f'{tag}-{trial}.time'
            started=time.monotonic()
            p=subprocess.run(['/usr/bin/time','-f','%e %U %S %M','-o',str(report),str(apps[tag])],capture_output=True,text=True,check=True)
            assert p.stdout=='RUNTIME_POLICY_PASS\n'
            fields=report.read_text().split()
            rows.append(dict(configuration=tag,trial=trial,wall_s=time.monotonic()-started,user_s=float(fields[1]),system_s=float(fields[2]),peak_rss_kib=int(fields[3])))
    (root/'summary.json').write_text(json.dumps(dict(builds=builds,runs=rows),indent=2)+'\n')

if __name__=='__main__':b.run_tasks([b.Task('runtime-policy-probe',main)])
