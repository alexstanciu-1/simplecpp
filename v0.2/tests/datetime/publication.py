#!/usr/bin/env python3
"""Compile independently requested module subsets and reject public API leaks."""
import argparse
import json
from pathlib import Path
import shlex
import subprocess

parser = argparse.ArgumentParser(description=__doc__)
for name in ['generator','metadata','publication','source','work']:
    parser.add_argument('--'+name,type=Path,required=True)
parser.add_argument('--cxx',required=True)
parser.add_argument('--flags',default='')
a = parser.parse_args()
a.work.mkdir(parents=True,exist_ok=True)

def run(args, success=True):
    result = subprocess.run(list(map(str,args)),capture_output=True,text=True,timeout=60)
    assert (result.returncode==0)==success, result.stdout+result.stderr
    return result.stdout

p=json.loads(a.publication.read_text())
base=[a.cxx,*shlex.split(a.flags),'-std=c++23','-pthread']
private=a.source/'runtime/src'
provider=a.work/'provider.o'
run(base+['-I'+str(private/'datetime'),'-c',private/'datetime/datetime.cpp','-o',provider])
for name, operations, types, strings, carriers, body in [
    ('signed',['now_unix_seconds','sleep_millis'],['int64'],False,False,'scpp::dt::sleep_millis(scpp::int64{0}); (void)scpp::dt::now_unix_seconds();'),
    ('unsigned',['monotonic_nanos'],['uint64'],False,False,'(void)scpp::dt::monotonic_nanos();'),
    ('format',['format_iso_utc'],['int64'],True,False,'return scpp::dt::format_iso_utc(scpp::int64{0}).size().native_value()!=20;'),
    ('parse',['parse_iso_utc'],['int64'],True,True,'return !scpp::dt::parse_iso_utc(scpp::string{"1970-01-01T00:00:00Z"}).has_value().native_value();'),
    ('local-format',['format_local'],['int64'],True,False,'return scpp::dt::format_local(scpp::string{"U"},scpp::int64{0}).size().native_value()!=1;'),
    ('local-now',['format_local_now'],['int64'],True,False,'return scpp::dt::format_local_now(scpp::string{"x"}).size().native_value()!=1;'),
    ('local-parse',['parse_common_local'],['int64'],True,True,'return !scpp::dt::parse_common_local(scpp::string{"1970-01-01T00:00:00Z"}).has_value().native_value();'),
]:
    request=json.loads(json.dumps(p))
    request['types']=types
    request['datetime']['operations']=operations
    if not strings: request.pop('string')
    if not carriers: request.pop('carriers')
    directory=a.work/name; directory.mkdir(exist_ok=True)
    pub=directory/'publication.json'; pub.write_text(json.dumps(request))
    generated=directory/'generated'
    run([a.generator,a.metadata,pub,generated])
    before={str(f):f.stat().st_mtime_ns for f in generated.rglob('*') if f.is_file()}
    run([a.generator,a.metadata,pub,generated,'--check'])
    assert before=={str(f):f.stat().st_mtime_ns for f in generated.rglob('*') if f.is_file()}
    prepared=json.loads((generated/'prepared.json').read_text())
    assert [o['name'] for o in prepared['datetime']['operations']]==sorted(operations)
    assert prepared['datetime']['build']['requires']=='scpp_runtime'
    flags=base+['-I'+str(generated/'public')]
    sample=directory/'consumer.cpp'; sample.write_text('#include "scpp/datetime.hpp"\nint main() {'+body+'}\n')
    expanded=run(flags+['-E','-P',sample])
    assert 'template' not in expanded
    exe=directory/'consumer'
    run(flags+['-I'+str(private/'numeric'),'-I'+str(private/'sequence'),'-I'+str(private/'datetime'),
               sample,generated/'src/runtime.cpp',generated/'src/datetime.cpp',provider,'-o',exe])
    run([exe])
    # Deferred/unselected operations must not appear in the generated declarations.
    run([a.generator,a.metadata,pub,generated,'--check'])
    for rejected in ['scpp::dt::parse_natural_language(scpp::string{})',
                     'scpp::dt::sleep_millis(1)', 'scpp::dt::format_iso_utc(0)']:
        sample.write_text('#include "scpp/datetime.hpp"\nint main(){ '+rejected+'; }\n')
        run(flags+['-fsyntax-only',sample],success=False)
print('Datetime: signed/unsigned clocks, UTC and local standalone publications pass')
