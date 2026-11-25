"""Measure source transfer against the existing cpp-flavor in-slot scanner."""
import argparse
import json
import re
from pathlib import Path

from measure import HERE, once, run, verify
from pinned_operators import measure


def build(root, runtime, work, cc, flavor):
    source_dir = work / (cc + '-' + flavor) / 'slot'
    dest = source_dir.with_name('transfer')
    dest.mkdir(exist_ok=True)
    text = (source_dir / 'scanner.cpp').read_text()
    assignment = re.search(r'(\(var_out_\d+\)\.source) = (var_text_\d+);', text)
    field, arg = assignment.groups()
    text = once(text, assignment[0], f'{field} = std::move({arg});')
    text = once(text, f'const scpp::string owner(std::move({arg}));', '')
    text = once(text, 'split_view view(owner);', f'split_view view({field});')
    source = dest / 'scanner.cpp'
    source.write_text(text)
    flags = [cc, '-std=c++23', '-O2', '-g0', '-I'+str(source_dir), '-I'+str(HERE/'byte_access')]
    flags += ['-I'+str(runtime/p) for p in ('generated/public','generated/private','src/numeric','src/sequence','src/hash')]
    obj = dest/'scanner.o'
    run([*flags,'-c',source,'-o',obj])
    run([*flags,obj,HERE/'driver.cpp',work/(cc+'.o'),'-o',dest/'clean'])
    run([*flags,'-DTRACE','-DCOUNT',source,work/'trace-driver.cpp',work/'trace.cpp',work/(cc+'.o'),'-o',dest/'trace'])
    verify(dest/'clean',root/flavor)
    # Check the entire retained source, including bytes with no token and failures.
    driver = (HERE/'driver.cpp').read_text()
    driver = once(driver, '    if (mode == "dump")', '''
    if (mode == "retained") {
        auto original = run(text,0);
        const std::string bytes(text.data(), text.size());
        text = make_input("replacement");
        auto sibling = run(text,0);
        return std::string_view(original.source.data(),original.source.size()) == bytes ? 0 : 1;
    }
    if (mode == "dump")''')
    (dest/'ownership.cpp').write_text(driver)
    run([*flags,obj,dest/'ownership.cpp',work/(cc+'.o'),'-o',dest/'ownership'])
    for i in range(11):
        run([dest/'ownership','retained',root/flavor/f'input{i}'])


if __name__ == '__main__':
    parser=argparse.ArgumentParser(description=__doc__)
    for name in ('fixtures','runtime','work'):
        parser.add_argument(name,type=Path)
    parser.add_argument('--cpu',type=int,required=True)
    parser.add_argument('--handwritten', action='store_true',
                        help='Compare transfer with handwritten C++ for every flavor')
    args=parser.parse_args()
    root,runtime,work=(p.resolve() for p in (args.fixtures,args.runtime,args.work))
    flavors = ('cpp','php','js') if args.handwritten else ('cpp',)
    for flavor in flavors:
        for cc in ('clang++','g++'):
            build(root,runtime,work,cc,flavor)
            if args.handwritten:
                verify(work/(cc+'-'+flavor)/'handwritten'/'clean',root/flavor)
    if args.handwritten:
        results = [measure(root,work,args.cpu,24,('transfer','handwritten'),flavor) for flavor in flavors]
        print(json.dumps(results,indent=2))
        raise SystemExit(0)
    data=measure(root,work,args.cpu,24,('slot','transfer'))
    data['allocations']={}
    for cc in ('clang++','g++'):
        for mode in ('slot','transfer'):
            exe=work/(cc+'-cpp')/mode/'trace'
            data['allocations'][cc+' '+mode]=run([exe,'count',root/'cpp/input9',work/'prior-operators']).decode().splitlines()
    print(json.dumps(data,indent=2))
