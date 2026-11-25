"""Application hash values use the common collection signature/recovery proof for both strict key types."""
import argparse
import json
import subprocess
import tempfile
from pathlib import Path
from application_vector_signatures_native import prove
from extends_native import ROOT, runtime, build, source_edit
from classes_native import fixture
from app_namespace_native import service, invoke
from entry_probe import raw_entry


def shared_slot_regression(s, empty):
    v, access, infer = s['v'], s['access'], s['infer']
    nodes = 'hash<Node,string>'
    replace = s['function']('replaceOne', nodes, [('items', nodes)],
                            f'{v}items["changed"] = new Node(); return {v}items;')
    body = empty('before', nodes) + infer('oldNode', 'new Node()') + infer('keptNode', 'new Node()')
    body += f'{v}before["changed"] = {v}oldNode; {v}before["kept"] = {v}keptNode;'
    body += infer('after', f'replaceOne({v}before)')
    body += infer('oldSlot', f'{v}before["changed"]') + infer('newSlot', f'{v}after["changed"]')
    body += f'if ({v}oldSlot !== {v}oldNode || {v}newSlot === {v}oldSlot) {{return 1;}}'
    body += infer('keptBefore', f'{v}before["kept"]') + infer('keptAfter', f'{v}after["kept"]')
    body += f'if ({v}keptBefore !== {v}keptNode || {v}keptAfter !== {v}keptNode) {{return 2;}}'
    body += f'{v}keptAfter{access}x = 29; echo ":", {v}keptBefore{access}x, ":", {v}keptAfter{access}x;'
    return replace, body


def behavior(work, flavor, libraries, memcheck=False):
    s=fixture(flavor);v,access=s['v'],s['access']
    def empty(name,typ):
        return {'php':f'${name} {typ} = [];','cpp':f'{typ} {name} = [];','js':f'let {name}:{typ} = [];'}[flavor]
    ctor={'php':'public function __construct(int $n) {$this->x=$n;}',
          'cpp':'Point(int n) {this->x=n;}','js':'constructor(n:int) {this.x=n;}'}[flavor]
    code=s['record']('Point',s['field']('x','int','0')+s['field']('text','string','"heap-backed application hash value"')+ctor)
    code+=s['record']('Node',s['field']('x','int','11')).replace('struct ','class ',1)
    code+=s['record']('Leaf','', 'Node').replace('struct ','class ',1)
    replace_one, shared_slots = shared_slot_regression(s, empty)
    code+=replace_one
    code+=empty('a','hash<Point,int>')+{'php':'$p Point(7);','cpp':'Point p(7);','js':'let p:Point(7);'}[flavor]
    code+=s['infer']('i','0')+f'while ({v}i<260) {{{v}a[{v}i]={v}p; {v}i++;}}'
    code+=s['infer']('b',v+'a')+s['infer']('q',v+'b[0]')+f'{v}q{access}x=9; {v}q{access}text="changed"; {v}b[0]={v}q;'
    code+=s['infer']('r',v+'a[0]')+f'if ({v}r{access}text === "changed") {{return 91;}}'
    code+=empty('texts','hash<Point,string>')+f'{v}texts["x\\0y"]={v}p;'+s['infer']('t',v+'texts["x\\0y"]')
    code+=empty('owners','hash<Node,int>')+f'{v}owners[0]=new Leaf();'+s['infer']('other',v+'owners')
    code+=s['infer']('n',v+'other[0]')+f'{v}n{access}x=13;'+s['infer']('original',v+'owners[0]')
    code+=empty('sequence','vector<Point>')+f'{v}sequence[]={v}p;'
    code+=f'echo count({v}a),":",{v}r{access}x,":",{v}q{access}x,":",{v}t{access}x,":",{v}original{access}x,":",count({v}sequence);'
    code+=shared_slots
    code+=s['infer']('missing',v+'a[-1]')+'return 90;'
    app=work/(flavor+'-behavior');out=work/(flavor+'-behavior-out');app.mkdir()
    source_edit(app/'module.json',json.dumps({'entry':'main.'+flavor}));source_edit(app/('main.'+flavor),code)
    proc=service(app,out)
    try:
        result=invoke(proc);assert 'error' not in result,result
    finally:proc.stdin.close();proc.wait()
    entry=out/'entry.cpp'
    entry.write_text('#include <stdexcept>\n#include <cstring>\n'+raw_entry(entry.read_text())+
        '\nint main() { try { tested_entry(); } catch (const std::out_of_range& e) { return std::strcmp(e.what(),"hash_missing_key")==0 ? 8 : 92; } return 93; }\n')
    expected=b'260:7:9:7:13:1:29:29'
    for cc,lib in libraries.items():build(out,work/(flavor+'-behavior-'+cc),cc,lib,expected)
    if memcheck:
        units=[out/p for p in json.loads((out/'manifest.json').read_text())['translation_units']]
        exe=work/(flavor+'-sanitized')
        command=['clang++','-std=c++23','-O1','-g0','-fsanitize=address,undefined','-fno-omit-frame-pointer',
                 '-I',ROOT/'runtime/generated/public',*units,libraries['clang++'],'-o',exe]
        run=subprocess.run(list(map(str,command)),capture_output=True,text=True);assert run.returncode==0,run.stderr
        run=subprocess.run([exe],capture_output=True);assert (run.returncode,run.stdout,run.stderr)==(8,expected,b''),run
    print(flavor,'mixed collection demands, required constructors, growth, NUL keys, upcast, copies and checked failure passed',flush=True)


def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output',type=Path)
    parser.add_argument('--runtime-dir',type=Path)
    parser.add_argument('--memcheck',action='store_true')
    args=parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-application-hashes-') as temporary:
        work=(args.output or Path(temporary)).resolve();work.mkdir(exist_ok=True)
        libraries={cc:args.runtime_dir/cc/'runtime.a' if args.runtime_dir else runtime(work,cc,[ROOT/'runtime/generated/src/hash.cpp']) for cc in ['clang++','g++']}
        for key in ['int','string']:
            group=work/key;group.mkdir(exist_ok=True)
            for flavor in ['php','cpp','js']:prove(group,flavor,libraries,'hash-'+key)
        for flavor in ['php','cpp','js']:behavior(work,flavor,libraries,args.memcheck)


if __name__=='__main__':main()
