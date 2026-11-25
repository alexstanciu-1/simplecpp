"""Clang storage-only control: replay the PHP oracle records, bypassing recognition."""
import argparse
import json
import os
import re
from pathlib import Path
from measure import run

DRIVER = '''
#include "sink.hpp"
#include <chrono>
#include <fstream>
#include <iostream>
#include <vector>
int main(int argc, char** argv) {
    std::ifstream file(argv[1]);
    std::vector<Record> input;
    Record t;
    std::string spelling;
    while (file >> t.offset >> t.length >> t.kind >> spelling) input.push_back(t);
    if (input.size() != 2560) return 1;
    auto check = replay(input.data(),input.size());
    if (check.size() != input.size()) return 2;
    for (unsigned i=0;i<input.size();++i) {
        auto value=check.at(i);
        if (value.offset!=input[i].offset || value.length!=input[i].length || value.kind!=input[i].kind) return 3;
    }
    using clock=std::chrono::steady_clock;
    for (int b=0;b<9;++b) {
        auto start=clock::now();
        for(int j=0;j<128;++j) {
            auto v=replay(input.data(),input.size());
            if(v.size()!=input.size()) return 4;
        }
        std::cout<<std::chrono::duration<double>(clock::now()-start).count()/128<<'\\n';
    }
}
'''


def build(slot,runtime,work,flavor,mode):
    emitted=slot/('clang++-'+flavor)/'slot'
    src=(emitted/'scanner.cpp').read_text()
    includes=re.findall(r'^#include "[a-f0-9]+\.hpp"$',src,re.M)
    concrete=re.search(r'(scpp_vector_\w+) var_items',src)[1]
    folder=work/flavor/mode
    folder.mkdir(parents=True,exist_ok=True)
    header='#include <vector>\n'+'\n'.join(includes)+'\nusing Record=App::scpp_records::Token;\n'
    container='App::'+concrete if mode=='scpp' else 'std::vector<Record>'
    header+=f'using Sink={container};\nSink replay(const Record*, std::size_t);\n'
    (folder/'sink.hpp').write_text(header)
    action='out.append_fields(r.offset,r.length,r.kind);' if mode=='scpp' else 'out.push_back({r.offset,r.length,r.kind});'
    (folder/'sink.cpp').write_text('#include "sink.hpp"\nSink replay(const Record* input, std::size_t count) {\n'
        '    Sink out; out.reserve(count+1);\n    for (std::size_t i=0;i<count;++i) {\n'
        '        const auto& r=input[i]; '+action+'\n    }\n    return out;\n}\n')
    (folder/'driver.cpp').write_text(DRIVER)
    exe=folder/'clean'
    run(['clang++','-std=c++23','-O2','-g0','-I'+str(emitted),'-I'+str(runtime/'generated/public'),
         folder/'sink.cpp',folder/'driver.cpp',slot/'clang++.o','-o',exe])
    return exe


if __name__=='__main__':
    p=argparse.ArgumentParser(description=__doc__)
    for name in ('root','slot','runtime','work'):
        p.add_argument(name,type=Path)
    p.add_argument('--cpu',type=int,required=True)
    a=p.parse_args()
    exes={(f,m):build(a.slot,a.runtime,a.work,f,m) for f in ('cpp','php','js') for m in ('scpp','std')}
    os.sched_setaffinity(0,{a.cpu})
    rows=[]
    for f in ('cpp','php','js'):
        for i in range(25):
            for m in (('scpp','std') if i%2 else ('std','scpp')):
                values=list(map(float,run([exes[f,m],a.root/f/'expected9']).split()))
                assert len(values)==9
                if i: rows.append(dict(flavor=f,round=i,storage=m,seconds=values))
    print(json.dumps(dict(cpu=a.cpu,tokens=2560,rows=rows),indent=2))
