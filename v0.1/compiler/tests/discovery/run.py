"""Source discovery outcomes, host path policy and repeat observations in PHP/native."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import time

ROOT = Path(__file__).resolve().parents[3]
FILES = ['src/01_prepare_inputs/read_sources/utilities/paths.php',
         'src/01_prepare_inputs/read_sources/data/listing.php',
         'src/01_prepare_inputs/read_sources/scan.php',
         'src/01_prepare_inputs/read_sources/main_discover_sources.php']
DEPENDENCIES = ['src/01_prepare_inputs/read_manifest/data/project_manifest.php',
                'src/01_prepare_inputs/read_manifest/utilities/manifest_syntax.php',
                'src/01_prepare_inputs/read_manifest/manifest_reader.php']


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
    def write(path, text):
        path.parent.mkdir(parents=True, exist_ok=True); path.write_text(text)
        os.utime(path, (timestamp, timestamp))
    project = inputs / 'project'
    for name, text in [('src/z.phs', 'zz'), ('src/a.phs', 'a'), ('src/sub/b.phs', 'bbb'),
                       ('src/sub/deep/c.phs', 'cccc'), ('extra/e.phs', 'eeeee'),
                       ('src/.hidden.phs', 'h'), ('src/ignore.txt', 'ignored'),
                       ('src/upper.PHS', 'ignored'), ('src2/out.phs', 'out'),
                       ('src/é space.phs', 'utf8'), ('src/back\\slash.phs', 'slash')]:
        write(project / name, text)
    (project / 'src/empty').mkdir()
    expected = []; calls = []
    def row(root, relative, root_index, size):
        return {'path': str(project/root/relative), 'relative': relative,
                'root': root_index, 'size': size, 'mtime': timestamp}
    initial = {'entry': 1, 'roots': [str(project/'src'), str(project/'extra')], 'files': [
        row('src','.hidden.phs',0,1), row('src','a.phs',0,1), row('src','back\\slash.phs',0,5),
        row('src','z.phs',0,2), row('src','é space.phs',0,4), row('extra','e.phs',1,5),
        row('src','sub/b.phs',0,3), row('src','sub/deep/c.phs',0,4)]}
    def manifest(name, folders, entry, result):
        path = project / (name + '.json')
        write(path, json.dumps({'source_folders': folders, 'entry': entry}))
        calls.append('\\discovery_test\\Probe::run(' + json.dumps(str(path)) + ');')
        expected.append(result)
    manifest('valid', ['src', 'extra'], 'src/a.phs', initial)
    manifest('absolute', [str(project/'src'),str(project/'extra')], str(project/'src/a.phs'), initial)
    manifest('dot', ['./src/../src','extra'], './src/a.phs', initial)
    for name, folders, entry in [('overlap',['src','src/sub'],'src/a.phs'),
                                  ('overlap_reverse',['src/sub','src'],'src/a.phs'),
                                  ('same_resolved',['src','./src'],'src/a.phs'),
                                  ('missing_root',['missing'],'src/a.phs'),
                                  ('file_root',['src/a.phs'],'src/a.phs'),
                                  ('outside_entry',['src'],'src2/out.phs'),
                                  ('missing_entry',['src'],'src/missing.phs'),
                                  ('wrong_suffix',['src'],'src/ignore.txt')]:
        manifest(name, folders, entry, {'error': True})
    manifest('sibling_prefix', ['src2','extra'], 'src2/out.phs',
             {'entry':0,'roots':[str(project/'src2'),str(project/'extra')],
              'files':[row('src2','out.phs',0,3),row('extra','e.phs',1,5)]})
    (project/'root_alias').symlink_to(project/'src',target_is_directory=True)
    manifest('root_alias', ['root_alias','extra'], 'root_alias/a.phs', initial)
    manifest('alias_overlap', ['src','root_alias'],'src/a.phs',{'error':True})
    for name, target in [('link_file',project/'src/a.phs'),('link_dir',project/'src'),
                         ('broken_link',project/'absent')]:
        folder=project/name;folder.mkdir();(folder/'ignored.link').symlink_to(target)
        manifest(name,[name],'src/a.phs',{'error':True})
    folder=project/'special';folder.mkdir();os.mkfifo(folder/'bad.phs')
    manifest('special',['special'],'special/bad.phs',{'error':True})
    calls.append('\\discovery_test\\Probe::run('+json.dumps(str(project/'src/a.phs'))+');')
    expected.append({'entry':0,'roots':[str(project/'src')],'files':[row('src','a.phs',0,1)]})
    for path, posix, windows in [('',False,False),('x',False,False),('C:',False,False),
            ('C:x',False,False),('C:/x',False,True),('C:\\x',False,True),
            ('/x',True,True),('\\x',False,True),('\\\\server\\share',False,True),
            ('relative\\file',False,False)]:
        calls.append('\\discovery_test\\Probe::syntax('+json.dumps(path)+');')
        expected.append({'posix':posix,'windows':windows,'normalized':path.replace('\\','/'),'preserved':path})
    calls.append('\\discovery_test\\Probe::host();');expected.append({'windows_host':False})
    # Repeated discovery in one process proves deterministic membership after prior failures.
    calls.append('\\discovery_test\\Probe::run('+json.dumps(str(project/'valid.json'))+');');expected.append(initial)
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
    assert json.loads(run('convert',conversion))['converted']==9
    assert json.loads(run('reuse',conversion))=={'converted':0,'reused':9,'removed':0}
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
    # Modify membership/metadata, then require fresh observations from the unchanged program.
    write(project/'src/z.phs','changed!');(project/'src/sub/b.phs').unlink()
    write(project/'src/new.phs','new')
    changed=json.loads(json.dumps(expected))
    new_initial=json.loads(json.dumps(initial));rows=new_initial['files']
    rows[3]['size']=8;rows.insert(3,row('src','new.phs',0,3));rows[:]=[r for r in rows if r['relative']!='sub/b.phs']
    for index in [0,1,2,12,len(changed)-1]:changed[index]=new_initial
    prove('php-refresh',php,changed)
    if binary:prove('native-refresh',binary,changed)
    (out/'expected-refresh.json').write_text(json.dumps(changed,indent=2)+'\n')
    report.update(passed=True,native=bool(binary),cases=len(expected),refresh_cases=len(changed),
                  production_files=FILES,source_sha256={f:hashlib.sha256((source/f).read_bytes()).hexdigest() for f in DEPENDENCIES+FILES})
    (out/'summary.json').write_text(json.dumps(report,indent=2)+'\n')
    print(f'Discovery: {len(expected)} initial + {len(changed)} refreshed outcomes passed; native={bool(binary)}')

if __name__=='__main__':main()
