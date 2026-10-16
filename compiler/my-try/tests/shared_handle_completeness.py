#!/usr/bin/env python3
"""Stored shared handles avoid payload layout; actual payload operations retain it."""
import argparse,json,re,sys
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
sys.path.insert(0,str(ROOT/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_artifacts import decode_artifacts
from cpp_fixture import CppFixtureCompiler


def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results',type=Path,required=True)
    out=parser.parse_args().results.resolve();out.mkdir(parents=True,exist_ok=False)
    source=out/'source';source.mkdir()
    files={
        'types.phs': '''class Counter { public static int $destroyed = 0; }
class Payload { public int $number = 7; public function __destruct() { Counter::$destroyed += 1; } }
class Child extends Payload { public int $extra = 2; }
struct Item { int $number = 3; }''',
        'identity.phs': 'function identity(Payload $p): Payload { return $p; }',
        'transfer.phs': '''function transfer(Payload $p): Payload {
 $empty Payload; $copy Payload = $p; $inferred = $copy; $empty = identity($inferred); return identity($empty);
}''',
        'access.phs': 'function access(Payload $p): int { return $p->number; }',
        'construct.phs': 'function construct(): Payload { return new Payload(); }',
        'upcast.phs': 'function upcast(Child $p): Payload { return $p; }',
        'value.phs': 'function copy_value(Item $p): Item { $copy = $p; return $copy; }',
        'template.phs': 'function pass_vector(vector<Payload> $p): vector<Payload> { return $p; }',
        'main.phs': '''if (true) {
 $a = construct(); $b = transfer($a); $b->number = 42;
 if (access($a) !== 42) { return 1; }
 $child = new Child(); $base = upcast($child); $base->number = 9;
 if ($child->number !== 9) { return 2; }
 $rows vector<Payload> = [$a]; $kept = pass_vector($rows);
 if ($kept[0]->number !== 42) { return 3; }
 $v Item; $other = copy_value($v); $other->number = 8;
 if ($v->number !== 3) { return 4; }
}
if (Counter::$destroyed !== 2) { return 5; }
return 0;''',
    }
    for name,text in files.items():(source/name).write_text(text)
    def type_path(name):return 'types/n'+name.encode().hex()+'.hpp'
    with CommandRunner(out/'logs',ROOT.parents[1]) as runner:
        def proof():
            compiler=CppFixtureCompiler(runner,ROOT.parents[1],out,pch=True,runtime=True,cxx='/usr/bin/clang++-18')
            snapshots=[]
            for variant in ['baseline','layout-edit']:
                if variant=='layout-edit':
                    (source/'types.phs').write_text(files['types.phs'].replace('class Payload {','class Payload { public int $marker = 11;'))
                payload=runner.run(variant+'-emit',['php',ROOT/'main.php','--s2s',source,'--entry','main.phs'],cli_reason='Focused generated artifact boundary proof')
                (out/(variant+'.json')).write_bytes(payload)
                artifacts={a['path']:a['text'] for a in decode_artifacts(payload)}
                def unit(name):return next(text for path,text in artifacts.items() if path.endswith('/n'+name.encode().hex()+'.cpp'))
                for name in ['identity.phs','transfer.phs']:
                    assert type_path('Payload') not in unit(name),(variant,name,'unnecessary payload include')
                    seen=set()
                    def closure(text):
                        for header in re.findall(r'#include "([^"]+)"',text):
                            if header in artifacts and header not in seen:
                                seen.add(header);closure(artifacts[header])
                    closure(unit(name))
                    assert type_path('Payload') not in seen,(variant,name,'transitive payload include')
                    assert any('struct record_Payload;' in text for text in [unit(name),*[artifacts[h] for h in seen]]),(variant,name,'missing forward declaration')
                for name,required in [('access.phs','Payload'),('construct.phs','Payload'),('upcast.phs','Child'),('upcast.phs','Payload'),('value.phs','Item'),('template.phs','Payload')]:
                    assert type_path(required) in unit(name),(variant,name,'lost complete definition',required)
                snapshots.append(artifacts)
                compiler.compile_artifacts(variant,payload,out/variant)
                runner.run(variant+'-execute',[out/variant])
            changed=[p for p in snapshots[0] if snapshots[0][p]!=snapshots[1][p]]
            assert changed==[type_path('Payload')],changed
            (out/'summary.json').write_text(json.dumps(dict(passed=True,changed=changed,generated_program_runs=2,proofs=['handle aliasing','last-owner destruction','upcast identity','value copy','conservative template payload','layout edit stable units']),indent=2))
        run_tasks([Task('shared-handle-completeness',proof)])

if __name__=='__main__':main()
