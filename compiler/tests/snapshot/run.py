"""Verified source byte reads, rejection and ownership outcomes in PHP/native."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import time

ROOT = Path(__file__).resolve().parents[3]
FILES = ['src/01_prepare_inputs/read_sources/data/buffer.php',
         'src/01_prepare_inputs/read_sources/read.php',
         'src/01_prepare_inputs/read_sources/main_read_sources.php']
DEPENDENCIES = ['src/01_prepare_inputs/read_manifest/data/project_manifest.php',
                'src/01_prepare_inputs/read_manifest/utilities/manifest_syntax.php',
                'src/01_prepare_inputs/read_manifest/manifest_reader.php',
                'src/01_prepare_inputs/read_sources/utilities/paths.php',
                'src/01_prepare_inputs/read_sources/data/listing.php',
                'src/01_prepare_inputs/read_sources/scan.php',
                'src/01_prepare_inputs/read_sources/main_discover_sources.php']


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    parser.add_argument('--target-checkout', type=Path)
    parser.add_argument('--candidate-revision')
    args = parser.parse_args()
    out = args.results.resolve(); out.mkdir(parents=True, exist_ok=False)
    source = out / 'source'; source.mkdir()
    inputs = out / 'inputs'; inputs.mkdir()
    report = {'passed': False, 'commands': [], 'native_build_attempts': 0}

    def run(label, command, cwd=ROOT):
        start = time.monotonic()
        result = subprocess.run(list(map(str, command)), cwd=cwd, capture_output=True, text=True)
        for kind, value in [('stdout', result.stdout), ('stderr', result.stderr)]:
            (out / f'{label}.{kind}.log').write_text(value)
        if label == 'native-build': report['native_build_attempts'] += 1
        report['commands'].append({'label': label, 'command': list(map(str, command)),
                                  'seconds': round(time.monotonic() - start, 3), 'exit': result.returncode})
        (out / 'summary.json').write_text(json.dumps(report, indent=2) + '\n')
        assert result.returncode == 0, (label, result.stdout, result.stderr)
        return result.stdout

    timestamp = 1700000000
    def write(path, data):
        path.parent.mkdir(parents=True, exist_ok=True); path.write_bytes(data)
        os.utime(path, (timestamp, timestamp))
    write(inputs/'text.phs', b'abc')
    write(inputs/'empty.phs', b'')
    write(inputs/'binary.phs', bytes(range(256)))
    write(inputs/'project/a.phs', b'first')
    write(inputs/'project/b.phs', b'second')
    write(inputs/'project.json', b'{"source_folders":["project"],"entry":"project/b.phs"}')
    (inputs/'link.phs').symlink_to(inputs/'text.phs')
    (inputs/'broken.phs').symlink_to(inputs/'missing')
    os.mkfifo(inputs/'fifo.phs')
    calls=[];expected=[]
    def file(name, mtime, size, data):
        path=str(inputs/name)
        calls.append('\\snapshot_test\\Probe::file('+json.dumps(path)+','+str(mtime)+','+str(size)+');')
        expected.append({'error':True} if data is None else {'path':path,'mtime':mtime,'bytes':list(data)})
    file('text.phs',timestamp,3,b'abc')
    file('empty.phs',timestamp,0,b'')
    file('binary.phs',timestamp,256,bytes(range(256)))
    for name,mtime,size in [('text.phs',timestamp,2),('text.phs',timestamp,4),
                          ('text.phs',timestamp+1,3),('text.phs',timestamp,-1),
                          ('missing',timestamp,0),('project',timestamp,0),
                          ('link.phs',timestamp,3),('broken.phs',timestamp,0),('fifo.phs',timestamp,0)]:
        file(name,mtime,size,None)
    calls.append('\\snapshot_test\\Probe::file("",0,0);');expected.append({'error':True})
    calls.append('\\snapshot_test\\Probe::file("x" . string_byte_from_int(0),0,0);');expected.append({'error':True})
    calls.append('\\snapshot_test\\Probe::project('+json.dumps(str(inputs/'project.json'))+');')
    expected.append({'entry':1,'contents':['first','second'],'retained':'first'})
    for entry in [-1,0,1]:
        calls.append('\\snapshot_test\\Probe::invalid_listing('+str(entry)+');');expected.append({'error':True})
    calls.append('\\snapshot_test\\Probe::partial('+json.dumps(str(inputs/'text.phs'))+','+str(timestamp)+');')
    expected.append({'partial_failed':True,'input_count':2,'input_size':3})
    file('text.phs',timestamp,3,b'abc')
    for relative in DEPENDENCIES + FILES:
        dest=source/relative;dest.parent.mkdir(parents=True,exist_ok=True)
        shutil.copy2(ROOT/'compiler'/relative,dest)
    shutil.copy2(Path(__file__).parent/'probe.php',source/'probe.php')
    (source/'main.php').write_text('<?php\n'+'\n'.join(calls)+'\n')
    run('imports',['php',ROOT/'tools/php_portability/sync_imports.php',source])
    run('check',['php',ROOT/'tools/php_portability/check.php',source])
    php=['php','-r','foreach(array_slice($argv,1) as $p) { require $p; }',
         ROOT/'tools/php_portability/runtime/bootstrap.php',*[source/f for f in DEPENDENCIES+FILES],
         source/'probe.php',source/'main.php']
    def prove(label, command, want):
        actual=[json.loads(line) for line in run(label,command,out).splitlines()]
        assert actual==want,(label,actual,want)
    prove('php',php,expected)
    generated=out/'phpp';conversion=['php',ROOT/'tools/php_portability/convert.php',source,generated]
    assert json.loads(run('convert',conversion))['converted']==12
    assert json.loads(run('reuse',conversion))=={'converted':0,'reused':12,'removed':0}
    run('runtime',['php',ROOT/'tools/php_portability/install_native_runtime.php',generated,'--json','--filesystem'])
    binary=None
    if args.target_checkout:
        target=json.loads((ROOT/'compiler/tools/portability_target.json').read_text())
        revision=args.candidate_revision or target['verified_commit'];checkout=args.target_checkout.resolve()
        assert len(revision)==40 and all(c in '0123456789abcdef' for c in revision)
        assert run('revision',['git','-C',checkout,'rev-parse','HEAD']).strip()==revision
        assert run('clean',['git','-C',checkout,'status','--porcelain']).strip()==''
        cli=checkout/'bin/scpp.php';run('init',['php',cli,'init','--php-profile=strict'],generated)
        config=json.loads((generated/'prism.json').read_text());config['build']['cxx']='clang++-18'
        config['runtime']['modules']=['json','filesystem'];(generated/'prism.json').write_text(json.dumps(config,indent=2)+'\n')
        run('native-build',['php',cli,'build','--build-runtime'],generated)
        binary=[generated/'.prism/build/main'];prove('native',binary,expected)
        report['target_revision']=revision
        assert run('clean-after',['git','-C',checkout,'status','--porcelain']).strip()==''
    (out/'expected.json').write_text(json.dumps(expected,indent=2)+'\n')
    # Stale discovery versions must fail when metadata changes. Same-size/mtime
    # edits demonstrate the documented non-atomic limitation, not false certainty.
    write(inputs/'text.phs',b'changed')
    write(inputs/'binary.phs',bytes(reversed(range(256))))
    changed=json.loads(json.dumps(expected))
    changed[0]={'error':True};changed[2]['bytes']=list(reversed(range(256)))
    changed[4]={'error':True};changed[-1]={'error':True}
    prove('php-refresh',php,changed)
    if binary:prove('native-refresh',binary,changed)
    (out/'expected-refresh.json').write_text(json.dumps(changed,indent=2)+'\n')
    report.update(passed=True,native=bool(binary),cases=len(expected),refresh_cases=len(changed),
                  production_files=FILES,source_sha256={f:hashlib.sha256((source/f).read_bytes()).hexdigest() for f in DEPENDENCIES+FILES})
    (out/'summary.json').write_text(json.dumps(report,indent=2)+'\n')
    print(f'Snapshot: {len(expected)} initial + {len(changed)} refreshed outcomes passed; native={bool(binary)}')

if __name__=='__main__':main()
