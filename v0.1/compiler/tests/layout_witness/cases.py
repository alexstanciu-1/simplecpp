import random
import re

def build():
    cases=[]
    def add(nodes,root):
        order=[];seen=set()
        def visit(i):
            if i in seen:return
            for child in nodes[i-1].get('children',[]):visit(child)
            seen.add(i);order.append(i)
        visit(root)
        primitives=[];lines=[];opaque=False
        for i in order:
            n=nodes[i-1];k=n['kind']
            if k=='integer':
                primitives.append([i,'i'+str(n['width'])]);lines.append(f"using t{i} = unsigned _BitInt({n['width']});\n")
            elif k=='opaque':
                opaque=True;lines.append(f"struct alignas({n['alignment']}) t{i} {{ unsigned char bytes[{n['size']}]; }};\n")
            elif k=='record':lines.append(f'struct t{i} {{\n'+''.join(f't{c} f{j};\n' for j,c in enumerate(n['children']))+'};\n')
            elif k=='array':lines.append(f"using t{i} = t{n['children'][0]}[{n['count']}];\n")
            else:
                cases.append(dict(nodes=nodes,root=root,expected=dict(rejected=True)));return
        facts={'size':f'sizeof(t{root})','alignment':f'alignof(t{root})'}
        if nodes[root-1]['kind']=='record':
            facts.update({f'field_{j}':f'__builtin_offsetof(t{root}, f{j})' for j in range(len(nodes[root-1]['children']))})
        for i,_ in primitives:
            facts[f'primitive_size_{i}']=f'sizeof(t{i})';facts[f'primitive_alignment_{i}']=f'alignof(t{i})'
        source=''.join(lines)+''.join(f'extern "C" const unsigned long long {k} = {v};\n' for k,v in facts.items()) if opaque else ''
        cases.append(dict(nodes=nodes,root=root,expected=dict(source=source,primitives=primitives)))
    integer=lambda width:dict(kind='integer',width=width)
    opaque=lambda size,alignment:dict(kind='opaque',size=size,alignment=alignment)
    record=lambda *ids:dict(kind='record',children=list(ids))
    add([integer(8)],1);add([integer(8),record(1,1)],2);add([record()],1)
    add([opaque(32,16)],1)
    for width in [1,7,8,16,32,64]:add([integer(width),opaque(16,8),record(1,2,1)],3)
    add([integer(32),opaque(32,16),record(1,2),dict(kind='array',children=[3],count=3),record(4,3,1)],5)
    add([record(),opaque(16,8),record(1,2,1)],3)
    add([opaque(32,16),dict(kind='array',children=[1],count=4)],2)
    for f in ['ieee_binary16','bfloat16','ieee_binary32','ieee_binary64','ieee_binary128']:
        add([dict(kind='float',format=f),opaque(8,8),record(1,2)],3)
    rng=random.Random(614)
    for _ in range(16):
        nodes=[integer(32),opaque(16,8)]
        for i in range(3,12):nodes.append(record(*[rng.randrange(1,i) for _ in range(rng.randrange(1,4))]))
        nodes.append(record(11,2));add(nodes,12)
    add([opaque(16,8)]+[record(i) for i in range(1,201)],201)
    return cases

def measure(cases,expected,out,run):
    """External harness proof: configured x86_64 target, constants only, no target execution."""
    run('clang-version',['clang++-18','--version'])
    def facts(text):return {k:int(v) for k,v in re.findall(r'^@(\w+) = [^\n]*constant i64 (\d+)\b',text,re.M)}
    for index,(case,result) in enumerate(zip(cases,expected)):
        if not result.get('source'):continue
        nodes=case['nodes'];cache={}
        def layout(i):
            if i in cache:return cache[i]
            n=nodes[i-1];k=n['kind'];offsets=[]
            if k=='integer':
                size=1
                while size*8<n['width']:size*=2
                align=size
            elif k=='opaque':size=n['size'];align=n['alignment']
            elif k=='array':
                size,align,_=layout(n['children'][0]);size*=n['count']
            else:
                size=0;align=1
                for child in n['children']:
                    child_size,child_align,_=layout(child)
                    size=(size+child_align-1)//child_align*child_align
                    offsets.append(size);size+=child_size;align=max(align,child_align)
                size=max(1,(size+align-1)//align*align)
            cache[i]=(size,align,offsets);return cache[i]
        size,align,offsets=layout(case['root']);want=dict(size=size,alignment=align)
        want.update({f'field_{j}':v for j,v in enumerate(offsets)})
        for i,_ in result['primitives']:
            size,align,_=layout(i);want[f'primitive_size_{i}']=size;want[f'primitive_alignment_{i}']=align
        cpp=out/f'witness-{index}.cpp';ir=out/f'witness-{index}.ll';cpp.write_text(result['source'])
        run(f'clang-witness-{index}',['clang++-18','-target','x86_64-unknown-linux-gnu','-x','c++','-std=c++20','-S','-emit-llvm','-O2',cpp,'-o',ir])
        llvm=ir.read_text();assert facts(llvm)==want,(index,facts(llvm),want)
        # Primitive ABI equivalence is measured independently through LLVM GEP constants.
        data_layout=re.search(r'target datalayout = "([^"]+)"',llvm).group(1)
        lines=[f'target datalayout = "{data_layout}"','target triple = "x86_64-unknown-linux-gnu"']
        primitive_want={}
        for i,t in result['primitives']:
            for name,query in [(f'primitive_size_{i}',f'getelementptr ({t}, ptr null, i32 1)'),(f'primitive_alignment_{i}',f'getelementptr ({{ i8, {t} }}, ptr null, i32 0, i32 1)')]:
                lines.append(f'@{name} = constant i64 ptrtoint (ptr {query} to i64)');primitive_want[name]=want[name]
        if primitive_want:
            raw=out/f'primitive-{index}.ll';folded=out/f'primitive-{index}-folded.ll';raw.write_text('\n'.join(lines)+'\n')
            run(f'llvm-primitive-{index}',['clang-18','-target','x86_64-unknown-linux-gnu','-x','ir','-S','-emit-llvm','-O2',raw,'-o',folded])
            assert facts(folded.read_text())==primitive_want,(index,facts(folded.read_text()),primitive_want)
