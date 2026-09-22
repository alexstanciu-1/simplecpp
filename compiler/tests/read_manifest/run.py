"""Manifest stage: outcome proofs in PHP and optionally converted strict native code."""
import argparse, hashlib, json, shutil, subprocess, time
from pathlib import Path
ROOT = Path(__file__).resolve().parents[3]
FILES = ['src/01_prepare_inputs/read_manifest/data/project_manifest.php',
         'src/01_prepare_inputs/read_manifest/utilities/manifest_syntax.php',
         'src/01_prepare_inputs/read_manifest/manifest_reader.php']


def main():
    p=argparse.ArgumentParser(description=__doc__)
    p.add_argument('--results',type=Path,required=True)
    p.add_argument('--target-checkout',type=Path)
    p.add_argument('--candidate-revision')
    a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
    source=out/'source';source.mkdir();inputs=out/'inputs';inputs.mkdir()
    report={'passed':False,'commands':[]}
    def run(label,command,cwd=ROOT,ok=True):
        start=time.monotonic();r=subprocess.run(list(map(str,command)),cwd=cwd,capture_output=True,text=True)
        (out/(label+'.stdout.log')).write_text(r.stdout);(out/(label+'.stderr.log')).write_text(r.stderr)
        report['commands'].append({'label':label,'command':list(map(str,command)),'cwd':str(cwd),'seconds':round(time.monotonic()-start,3),'exit':r.returncode})
        (out/'summary.json').write_text(json.dumps(report,indent=2)+'\n')
        assert (r.returncode==0)==ok,(label,r.stdout,r.stderr)
        return r.stdout
    cases=[
      ('valid',{'source_folders':['src','../shared','é space'],'entry':'src/main.phs'},True),
      ('numeric_folder',{'source_folders':['0','01'],'entry':'x'},True),
      ('missing_entry',{'source_folders':['src']},False),
      ('missing_roots',{'entry':'x'},False),
      ('null',None,False),('false',False,False),('array',[],False),('number',2,False),
      ('object_roots',{'source_folders':{},'entry':'x'},False),
      ('numeric_object',{'source_folders':{'0':'src'},'entry':'x'},False),
      ('empty',{'source_folders':[],'entry':'x'},False),
      ('null_roots',{'source_folders':None,'entry':'x'},False),
      ('null_entry',{'source_folders':['src'],'entry':None},False),
      ('bad_element',{'source_folders':[42],'entry':'x'},False),
      ('bad_entry',{'source_folders':['src'],'entry':False},False),
      ('empty_path',{'source_folders':[''],'entry':'x'},False),
      ('nul_path',{'source_folders':['src'],'entry':'a\0b'},False),
      ('duplicate',{'source_folders':['src','src'],'entry':'x'},False),
      ('unknown',{'source_folders':['src'],'entry':'x','other':1},False),
    ]
    expected=[];calls=[]
    def add(name,data,result):
        path=inputs/(name+'.json');path.write_bytes(data)
        calls.append('\\manifest_test\\Probe::read('+json.dumps(str(path))+');')
        if result:
            expected.append({'status':'ok','single':False,'path':str(path),'directory':str(inputs),'content':data.decode(),'entry':result['entry'],'folders':result['source_folders'],'files':[]})
        else:expected.append({'status':'invalid'})
    for name,value,valid in cases:add(name,json.dumps(value,ensure_ascii=False).encode(),value if valid else None)
    for name,data in [('syntax',b'{'),('trailing',b'{} garbage'),('utf8',b'{"entry":"\xff"}'),('surrogate',b'{"entry":"\\ud800"}')]:add(name,data,None)
    value={'source_folders':['src'],'entry':'last.phs'}
    add('duplicate_key',b'{"source_folders":[],"source_folders":["src"],"entry":"first","entry":"last.phs"}',value)
    add('whitespace',b' { "source_folders" : ["src"], "entry": "last.phs" }\n ',value)
    file=inputs/'single.phs';file.write_text('not parsed by manifest reading')
    calls.append('\\manifest_test\\Probe::read('+json.dumps(str(file))+');')
    expected.append({'status':'ok','single':True,'path':str(file),'directory':str(inputs),'content':'','entry':file.name,'folders':[],'files':[file.name]})
    for file in [inputs/'missing.json',inputs]:
        calls.append('\\manifest_test\\Probe::read('+json.dumps(str(file))+');');expected.append({'status':'io'})
    # Relative project input is resolved against the process working directory.
    calls.append('\\manifest_test\\Probe::read("inputs/valid.json");')
    relative_expected=dict(expected[0]);relative_expected['path']='inputs/valid.json';expected.append(relative_expected)
    link=inputs/'linked.json';link.symlink_to(inputs/'valid.json')
    calls.append('\\manifest_test\\Probe::read('+json.dumps(str(link))+');')
    linked_expected=dict(expected[0]);linked_expected['path']=str(link);expected.append(linked_expected)
    # Valid input after errors in the same process, plus independent result publication.
    path=inputs/'valid.json';calls.append('\\manifest_test\\Probe::retention('+json.dumps(str(path))+');');expected.append({'retained':'src/main.phs'})
    calls.append('\\manifest_test\\Probe::json_view();')
    expected.extend([{'key':'0','key2':'01','kind':'boolean','count':3,'missing':True,'retained':'old'},
                     {'adapter_error':'index'},{'adapter_error':'kind'},{'adapter_error':'missing'}])
    for relative in FILES:
        dest=source/relative;dest.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(ROOT/'compiler'/relative,dest)
    shutil.copy2(Path(__file__).parent/'probe.php',source/'probe.php')
    (source/'main.php').write_text('<?php\n'+ '\n'.join(calls)+'\n')
    run('imports',['php',ROOT/'tools/php_portability/sync_imports.php',source])
    run('check',['php',ROOT/'tools/php_portability/check.php',source])
    php=run('php',['php','-r','foreach(array_slice($argv,1) as $p) { require $p; }',ROOT/'tools/php_portability/runtime/bootstrap.php',*[source/f for f in FILES],source/'probe.php',source/'main.php'],out)
    actual=[json.loads(line) for line in php.splitlines()];assert actual==expected,(actual,expected)
    generated=out/'phpp';command=['php',ROOT/'tools/php_portability/convert.php',source,generated]
    assert json.loads(run('convert',command))['converted']==5
    assert json.loads(run('reuse',command))=={'converted':0,'reused':5,'removed':0}
    run('runtime',['php',ROOT/'tools/php_portability/install_native_runtime.php',generated,'--json','--filesystem'])
    if a.target_checkout:
        target=json.loads((ROOT/'compiler/tools/portability_target.json').read_text());revision=a.candidate_revision or target['verified_commit']
        assert len(revision)==40 and all(c in '0123456789abcdef' for c in revision)
        checkout=a.target_checkout.resolve()
        assert run('revision',['git','-C',checkout,'rev-parse','HEAD']).strip()==revision
        assert run('clean',['git','-C',checkout,'status','--porcelain']).strip()==''
        cli=checkout/'bin/scpp.php';run('init',['php',cli,'init','--php-profile=strict'],generated)
        config=json.loads((generated/'prism.json').read_text());config['build']['cxx']='clang++-18';config['runtime']['modules']=['json','filesystem'];(generated/'prism.json').write_text(json.dumps(config,indent=2)+'\n')
        run('native-build',['php',cli,'build','--build-runtime'],generated)
        native=run('native',[generated/'.prism/build/main'],out)
        actual=[json.loads(line) for line in native.splitlines() if line.startswith('{')]
        assert actual==expected,(actual,expected)
        report['target_revision']=revision
        assert run('clean-after',['git','-C',checkout,'status','--porcelain']).strip()==''
    report.update(passed=True,native=bool(a.target_checkout),cases=len(expected),production_files=FILES,source_sha256={f:hashlib.sha256((source/f).read_bytes()).hexdigest() for f in FILES})
    (out/'summary.json').write_text(json.dumps(report,indent=2)+'\n')
    (out/'expected.json').write_text(json.dumps(expected,indent=2,ensure_ascii=False)+'\n')
    print(f'Manifest stage: {len(expected)} structured outcomes passed; native={bool(a.target_checkout)}')

if __name__=='__main__':main()
