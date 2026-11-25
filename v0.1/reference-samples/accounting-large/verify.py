#!/usr/bin/env python3
"""Measure authored PHS bytes; optionally build with my-try and check the large CLI."""
import argparse
import hashlib
import json
from pathlib import Path
import subprocess
import sys
import time

APP = Path(__file__).resolve().parent
TARGET = 512 * 1024


def inventory():
    rows = []
    for p in sorted(APP.rglob('*.phs')):
        if '.prism' in p.parts:
            continue
        name = p.name
        checks = name.startswith('check_') or name in {
            'checks.phs', 'business_checks.phs', 'closing_checks.phs',
            'self_test.phs', 'workflow_checks.phs', 'document_catalog_checks.phs'}
        data = p.read_bytes()
        rows.append(dict(path=str(p.relative_to(APP)), bytes=len(data),
                         sha256=hashlib.sha256(data).hexdigest(),
                         category='checks' if checks else 'application'))
    total = sum(r['bytes'] for r in rows)
    return dict(target_bytes=TARGET, authored_phs_bytes=total, kib=total / 1024,
                within_five_percent=abs(total-TARGET) <= TARGET*.05,
                application_bytes=sum(r['bytes'] for r in rows if r['category']=='application'),
                check_bytes=sum(r['bytes'] for r in rows if r['category']=='checks'),
                files=rows)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--size-only', action='store_true')
    parser.add_argument('--compiler', type=Path, default=APP.parents[2])
    parser.add_argument('--results', type=Path)
    parser.add_argument('--php', default='php')
    parser.add_argument('--executable', type=Path)
    parser.add_argument('--native-driver', type=Path)
    parser.add_argument('--native-builder', type=Path)
    parser.add_argument('--catalogs', type=Path)
    parser.add_argument('--runtime', type=Path)
    parser.add_argument('--publisher', type=Path)
    args = parser.parse_args()
    source = inventory()
    print(json.dumps({k:v for k,v in source.items() if k!='files'},indent=2),flush=True)
    if not source['within_five_percent']:
        raise RuntimeError('Authored source must remain within five percent of 512 KiB')
    if args.size_only:
        return
    if args.results is None:
        parser.error('--results is required for builds or executable checks')
    results=args.results.resolve();results.mkdir(parents=True,exist_ok=False)
    (results/'source.json').write_text(json.dumps(source,indent=2)+'\n')
    repo=args.compiler.resolve()
    sys.path.insert(0,str(repo/'compiler/my-try/tools'))
    from test_runner import Task,run_tasks
    from test_cli import cases
    observations=[]
    def run(name,command,**kwargs):
        started=time.monotonic()
        p=subprocess.run(list(map(str,command)),capture_output=True,text=True,timeout=900,**kwargs)
        observations.append(dict(name=name,seconds=time.monotonic()-started,argv=list(map(str,command)),exit=p.returncode))
        (results/(name+'.stdout')).write_text(p.stdout)
        (results/(name+'.stderr')).write_text(p.stderr)
        (results/'commands.json').write_text(json.dumps(observations,indent=2))
        if p.returncode:
            raise RuntimeError(name+': '+p.stderr[-3000:])
        return p
    executable=args.executable
    if executable is None:
        published=results/'published';cache=results/'cache'
        if args.native_driver:
            if not all([args.catalogs,args.native_builder,args.publisher]):
                parser.error('--native-driver requires --catalogs, --native-builder and --publisher')
            bundle=results/'bundle';bundle.mkdir()
            runtime=args.runtime or repo/'runtime/include'
            p=run('native-source',[args.native_driver,args.catalogs,APP,bundle,cache,runtime,'12'],input='compile\nquit\n')
            replies=[json.loads(line) for line in p.stdout.splitlines()]
            (results/'source-phases.json').write_text(json.dumps(replies,indent=2))
            # The PHP publication workaround requires the selected module composition.
            # Obtain it from the same source compilation, then prove artifact parity.
            host=run('php-source',[args.php,repo/'compiler/my-try/main.php','--s2s',APP,'--entry','main.phs','--output',published])
            native=json.loads((bundle/'bundle.json').read_text());php=json.loads(host.stdout)
            normalize=lambda b:{a['path']:(a['text'],a.get('includes')) for a in b['artifacts']}
            assert normalize(native)==normalize(php),'PHP/native generated artifact mismatch'
            run('publish-native',[args.php,args.publisher,repo,bundle/'bundle.json',published/'.scpp-output.manifest',published])
            command=[args.native_builder,published,cache,runtime,'/usr/bin/clang++','--runtime-snapshot']
        else:
            run('php-source',[args.php,repo/'compiler/my-try/main.php','--s2s',APP,'--entry','main.phs','--output',published])
            command=[args.php,repo/'compiler/my-try/tools/build_cpp.php',published,cache,'--runtime-snapshot']
        p=run('native-build',['/usr/bin/time','-f','%M','-o',results/'build-peak-rss-kib.txt',*command])
        built=json.loads(p.stdout);executable=Path(built['executable'])
        (results/'build.json').write_text(json.dumps(built,indent=2))
    tests=cases()
    test_results=[]
    def check(row):
        name,argv,expected,status=row
        p=subprocess.run([str(executable),*argv],capture_output=True,text=True,timeout=60)
        assert (p.returncode,p.stdout,p.stderr)==(status,expected,''),(name,p.stdout,p.stderr)
        return dict(name=name,passed=True)
    for result in run_tasks([Task(r[0],lambda r=r:check(r)) for r in tests],jobs=12,report=False):
        test_results.append(result.value)
    report=dict(passed=True,source_bytes=source['authored_phs_bytes'],executable=str(executable),
                cli_cases=len(test_results),tests=test_results,commands=observations)
    (results/'summary.json').write_text(json.dumps(report,indent=2)+'\n')
    print('PASS',len(test_results),'CLI cases',flush=True)


if __name__=='__main__':
    main()
