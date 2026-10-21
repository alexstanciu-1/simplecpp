"""Focused runtime/metadata regressions and independent FNV oracle."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks

def oracle(data):
    value=14695981039346656037
    for byte in data:value=((value^byte)*1099511628211)%2**64
    return value

def main():
    p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',type=Path,required=True)
    out=p.parse_args().results.resolve();out.mkdir(parents=True,exist_ok=False)
    rows=[b'',b'a',b'abc',b'bcd',b'\0',b'\xff','aă😀'.encode(),bytes(range(256))]+[bytes((i*31+j*17)%256 for i in range(j)) for j in range(1,65)]
    source='#include "scpp/stable_hash.hpp"\n#include <cassert>\n#include <iostream>\nint main(){\n'
    for index,row in enumerate(rows):
        data=','.join(str(b) for b in row) or '0'
        source+=f'const unsigned char b{index}[]={{ {data} }}; const scpp::string_t s{index}(std::string(reinterpret_cast<const char*>(b{index}),{len(row)})); assert(scpp::stable_hash::string_u64(s{index})==UINT64_C({oracle(row)})); std::cout << scpp::stable_hash::string_hex(s{index}).native_value() << "\\n";\n'
    source+='}\n';(out/'oracle.cpp').write_text(source)
    expected=''.join(f'{oracle(row):016x}\n' for row in rows).encode()
    with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
        def native(name,cxx='/usr/bin/clang++',oracle_test=False):
            exe=out/name;path=out/'oracle.cpp' if oracle_test else ROOT/'tests/runtime/native'/(name+'.cpp')
            runner.run(name+'-compile',[cxx,'-std=c++23','-DSCPP_LANGUAGE_TARGET_PHP=1','-pthread','-I',ROOT/'runtime/include',path,ROOT/'runtime/include/core/runtime.cpp','-o',exe],timeout=300)
            for run in range(2 if oracle_test else 1):
                actual=runner.run(name+'-run-'+str(run),[exe])
                if oracle_test:assert actual==expected
        def php(name):
            folder=out/name;folder.mkdir();runner.run(name,['php',APP/'tests'/(name+'.php'),folder])
        run_tasks([Task(n,lambda n=n:native(n)) for n in ['test_stable_hash','test_text_builder','test_binary_codec']]+[Task('oracle-clang',lambda:native('oracle-clang',oracle_test=True)),Task('oracle-gcc',lambda:native('oracle-gcc','/usr/bin/g++',True))]+[Task(n,lambda n=n:php(n)) for n in ['runtime_source','runtime_memory','runtime_builders','runtime_types']])
    (out/'summary.json').write_text(json.dumps(dict(passed=True,oracle_rows=len(rows),standard_library='libstdc++ for both compilers; libc++ unavailable'),indent=2)+'\n')
if __name__=='__main__':main()
