#!/usr/bin/env python3
"""Replay full Git source states, preserving only the existing main probe harness.

Reject file-set/config changes requiring a new build graph. Use exact historical
smoke fixtures and a declaration-kind catalog from each source snapshot. This is
current-toolchain historical-source reconstruction, not a historical toolchain.
"""
import argparse,hashlib,io,json,os,re,subprocess,tarfile,time
from dataclasses import dataclass
from pathlib import Path
from expanded_layout import prepare
from experiment import require_scratch,measure,write_changed
from run_corpus import warm,verify

@dataclass
class Snapshot:
    revision: str
    sources: dict
    fixtures: dict
    other: dict

    @classmethod
    def read(cls, repository, revision):
        rev=subprocess.check_output(['git','-C',str(repository),'rev-parse',revision],text=True).strip()
        paths=['compiler/src']
        if subprocess.run(['git','-C',str(repository),'cat-file','-e',rev+':compiler/fixtures/return_42'],capture_output=True).returncode==0:paths.append('compiler/fixtures/return_42')
        data=subprocess.check_output(['git','-C',str(repository),'archive',rev,*paths])
        sources,fixtures,other={},{},{}
        with tarfile.open(fileobj=io.BytesIO(data)) as archive:
            for member in archive.getmembers():
                if not member.isfile():continue
                text=archive.extractfile(member).read()
                if member.name.startswith('compiler/src/'):
                    key=member.name.removeprefix('compiler/src/')
                    (sources if key.endswith('.phs') else other)[key]=text
                elif member.name.startswith('compiler/fixtures/return_42/'):
                    fixtures[member.name.removeprefix('compiler/fixtures/return_42/')]=text
        return cls(rev,sources,fixtures,other)

    def kinds(self):
        result={}
        for text in self.sources.values():
            for kind,name in re.findall(r'^\s*(?:final\s+|abstract\s+)?(class|struct)\s+(\w+)',text.decode(),re.M):
                if name in result:raise ValueError('Ambiguous historical type declaration: '+name)
                result[name]=kind
        return result

def digest(data):return hashlib.sha256(data).hexdigest()
p=argparse.ArgumentParser();p.add_argument('--app',type=Path,required=True);p.add_argument('--repository',type=Path,required=True);p.add_argument('--commit',action='append',required=True);p.add_argument('--out',type=Path,required=True);p.add_argument('--minimal-types',action='store_true');a=p.parse_args()
app=a.app.resolve();require_scratch(app);out=a.out.resolve();out.mkdir(parents=True,exist_ok=False);repo=Path(__file__).resolve().parents[2]
manifest=json.loads((app.parent/'manifest.json').read_text());pin=Snapshot.read(a.repository,manifest['application_revision']);original={k:(app/k).read_bytes() for k in pin.sources}
assert {str(p.relative_to(app)) for p in app.rglob('*.phs') if '.prism' not in p.parts}==set(pin.sources),'Scratch source file-set differs from pinned Git tree'
assert all(v==pin.sources[k] for k,v in original.items() if k!='main.phs'),'Scratch source must start at the pinned baseline'
catalog_path=app/'.prism/cache/declared_type_kind_catalog.json';original_catalog=catalog_path.read_bytes();fixture_root=app.parent/'fixtures/return_42';fixture_original={p.name:p.read_bytes() for p in fixture_root.iterdir() if p.is_file()};corpus=json.loads((app.parent/'corpus.json').read_text());config='latency-expanded.ninja';mirror=app/'.prism/expanded/generated'
if a.minimal_types:config='latency-minimal.ninja';mirror=app/'.prism/minimal/generated'
executable='minimal-main' if a.minimal_types else 'main'
summary={'minimal_types':a.minimal_types,'scope':'full historical compiler/src states except unchanged instrumented main; current generator/runtime/output policy, not historical toolchain','pinned_revision':pin.revision,'instrumented_main_sha256':digest(original['main.phs']),'cases':[],'restored':False}
def save():(out/'summary.json').write_text(json.dumps(summary,indent=2)+'\n')
def generate(metadata_path=None):
    prepare(app,partition_consumers=True,isolate_tables=True,isolate_counters=True,isolate_adapters=True,isolate_composition_type=True,isolate_shared_carriers=True,isolate_value_traits=True,isolate_provider_traits=True,type_metadata_path=metadata_path)
    if a.minimal_types:
        from minimal_type_layout import prepare as minimal
        minimal(app)
def regenerate(keys):
    if keys:subprocess.run(['php',str(repo/'tools/compile_latency/regenerate.php'),str(repo),str(app),*keys],check=True,stdout=subprocess.DEVNULL)
def install(snapshot,where):
    assert snapshot.sources.keys()==original.keys(),'Historical source ownership needs a new build graph'
    assert snapshot.other==pin.other,'Historical non-PHS config changes need separate setup'
    assert snapshot.fixtures.keys()==fixture_original.keys(),'Historical smoke fixture file-set changed'
    kinds=snapshot.kinds();catalog_path.write_text(json.dumps({'schema_version':1,'declared_type_kinds':kinds,'historical_revision':snapshot.revision})+'\n')
    changed=[]
    for key,value in snapshot.sources.items():
        expected=original['main.phs'] if key=='main.phs' else value
        if (app/key).read_bytes()!=expected:(app/key).write_bytes(expected);changed.append(key)
    for key,value in snapshot.fixtures.items():(fixture_root/key).write_bytes(value)
    assert all((app/k).read_bytes()==v for k,v in snapshot.sources.items() if k!='main.phs')
    (where/'source-state.json').write_text(json.dumps({'revision':snapshot.revision,'changed_sources_for_setup':changed,'historical_source_sha256':{k:digest(v) for k,v in snapshot.sources.items()},'instrumented_main_sha256':digest(original['main.phs']),'declared_type_kinds':kinds,'fixture_sha256':{k:digest(v) for k,v in snapshot.fixtures.items()}},indent=2)+'\n')
    regenerate(changed)
    from historical_type_metadata import project
    metadata_path=project(app/'.prism/generated',where/'publication-metadata.json')
    generate(metadata_path)
    (where/'build.ninja').write_bytes((app/'.prism/build'/config).read_bytes())
    if a.minimal_types:(where/'dependency-manifest.json').write_bytes((app/'.prism/build/latency-minimal-manifest.json').read_bytes())
def check(where,label):
    verify(app,corpus,where,label,executable)
    fixture=where/'literal';fixture.mkdir(exist_ok=True);(fixture/'main.phs').write_text('function run(): int32 { return 42; }\n');(fixture/'project.manifest').write_text('entry=run\nexpected_result=42\nsource=main.phs\n')
    dest=where/(label+'-literal');env={k:v for k,v in os.environ.items() if not k.startswith('SCPP_V2_')};env.update(SCPP_V2_PROJECT_ROOT=str(where),SCPP_V2_RUN_LABELS='literal',SCPP_V2_BUILD_ROOT=str(dest))
    r=subprocess.run([str(app/'.prism/build'/executable)],cwd=app,env=env,text=True,capture_output=True,timeout=30);(where/(label+'.pipeline.log')).write_text(r.stdout+r.stderr);assert r.returncode==0 and 'compiler_project_runner_completed=1' in r.stdout.splitlines(),r.stdout
    llvm=dest/'phs_literal_native/compiler.ll';assert 'ret i32 42' in llvm.read_text()
    r=subprocess.run(['clang','-Wno-override-module',str(llvm),'-o',str(dest/'fixture')],capture_output=True,text=True);r.check_returncode();assert subprocess.run([str(dest/'fixture')],capture_output=True).returncode==42
save()
try:
    for commit in a.commit:
        case={'commit':commit,'phase':'snapshot','result':'running'};summary['cases'].append(case);where=out/commit;where.mkdir();save()
        try:
            before=Snapshot.read(a.repository,commit+'^');after=Snapshot.read(a.repository,commit)
            case['before_revision']=before.revision;case['after_revision']=after.revision;case['changed_sources']=[k for k in before.sources if before.sources[k]!=after.sources.get(k)]
            (where/'historical.patch').write_bytes(subprocess.check_output(['git','-C',str(a.repository),'diff',before.revision,after.revision,'--','compiler/src']))
            before_dir=where/'before';before_dir.mkdir();case['phase']='before_setup';save();install(before,before_dir);start=time.perf_counter();warm(app,config);case['native_before_setup_seconds']=time.perf_counter()-start;case['phase']='before_validation';save();check(where,'before')
            hashes={p:digest(p.read_bytes()) for p in mirror.rglob('*') if p.is_file()};graph=(app/'.prism/build'/config).read_text();old_objects=set(next(l for l in graph.splitlines() if l.startswith('build '+executable+': link ')).split()[3:])
            after_dir=where/'after';after_dir.mkdir();case['phase']='after_generation';save();install(after,after_dir)
            changed=[p for p in mirror.rglob('*') if p.is_file() and digest(p.read_bytes())!=hashes.get(p)];graph=(app/'.prism/build'/config).read_text();new_objects=set(next(l for l in graph.splitlines() if l.startswith('build '+executable+': link ')).split()[3:])-old_objects
            case['changed_generated_inputs']=[str(p.relative_to(mirror)) for p in changed];case['forced_new_objects']=sorted(new_objects);outputs=None
            for trial in range(3):
                for path in changed:path.touch()
                for obj in new_objects:
                    if obj.endswith('.o'):(app/'.prism/build'/obj).unlink(missing_ok=True)
                label='edit-'+str(trial);case['phase']='native_edit';save();measure(app,config,label,where,[],jobs=12);case['phase']='after_validation';save();check(where,label)
                row=json.loads((where/'measurements.jsonl').read_text().splitlines()[-1]);current=sorted(x['output'] for x in row['native_steps'])
                if outputs is None:outputs=current
                assert outputs==current
                if row['native_wall_seconds']>20:case['early_stop']='one successful screen above20 native seconds';break
            case['result']='measured';case['phase']='complete';save();print('MEASURED',commit,flush=True)
        except (Exception,SystemExit) as e:
            case['result']='blocked';case['error']=str(e)[:2000];(where/'failure.log').write_text(str(e));save();print('BLOCKED',commit,case['phase'],case['error'][:300],flush=True)
finally:
    changed=[]
    for key,value in original.items():
        if (app/key).read_bytes()!=value:(app/key).write_bytes(value);changed.append(key)
    catalog_path.write_bytes(original_catalog)
    for key,value in fixture_original.items():(fixture_root/key).write_bytes(value)
    regenerate(changed);generate();warm(app,config);verify(app,corpus,out,'restored',executable)
    assert all((app/k).read_bytes()==v for k,v in original.items())
    summary['restored']=True;save()
