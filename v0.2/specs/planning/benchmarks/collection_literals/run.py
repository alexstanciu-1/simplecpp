#!/usr/bin/env python3
"""Isolated construction benchmark; never edits production or compiler sources."""
import argparse, hashlib, json, os, platform, random, statistics, subprocess, time
from pathlib import Path
MODES=['append','reserved_append','constructor_list','static_list','constructor_array','static_array','constructor_owned','static_owned']
CASES={'int3':('IVec',3),'int64':('IVec',64),'int512':('IVec',512),'short16':('SVec',16),'heap16':('SVec',16),'nested8x16':('Matrix',8)}
MASK=(1<<64)-1

def digest(values,kind):
    h=1469598103934665603
    def mix(x):
        nonlocal h
        h=((h^x)*1099511628211)&MASK
    mix(len(values))
    for x in values:
        if kind=='IVec':mix(x)
        elif kind=='SVec':
            mix(len(x))
            for b in x:mix(b)
        else:mix(digest(x,'IVec'))
    return h

def values(case,site):
    kind,n=CASES[case]
    if kind=='IVec':return list(range(1+site,n+1+site))
    if kind=='Matrix':return [list(range(1+site+r*16,17+site+r*16)) for r in range(n)]
    return [(('s'+str(site)+'-'+str(i)).encode() if case=='short16' else (('heap-'+str(site)+'-'+str(i)+'-').encode()+b'x'*80+b'\0tail')) for i in range(n)]

def text_literal(x):
    body=''.join(chr(b) if 32<=b<127 and b not in (34,92) else '\\%03o'%b for b in x)
    return 'scpp::string{"'+body+'", '+str(len(x))+'}'

def expression(mode,kind,vals):
    rendered=[str(x) if kind=='IVec' else text_literal(x) if kind=='SVec' else expression(mode,'IVec',x) for x in vals]
    return construct(mode,kind,rendered)

def construct(mode,kind,rendered):
    elem={'IVec':'I','SVec':'scpp::string','Matrix':'IVec'}[kind]
    body=', '.join(rendered);n=len(rendered)
    if mode=='constructor_list':return kind+'{'+body+'}'
    if mode=='static_list':return kind+'::from({'+body+'})'
    if mode in ('append','reserved_append'):
        return '([] { '+kind+' result; '+('result.reserve('+str(n)+'); ' if mode=='reserved_append' else '')+''.join('result.append('+x+'); ' for x in rendered)+'return result; }())'
    owned=mode.endswith('owned')
    call=(kind+'::from_owned(input, '+str(n)+')' if owned else kind+'::from(input, '+str(n)+')') if mode.startswith('static') else (kind+'(owned_input{}, input, '+str(n)+')' if owned else kind+'(input, '+str(n)+')')
    return '([] { '+('' if owned else 'const ')+elem+' input[] = {'+body+'}; return '+call+'; }())'

def command(cmd,cwd):
    begin=time.perf_counter();p=subprocess.run(list(map(str,cmd)),cwd=cwd,capture_output=True,text=True)
    if p.returncode:raise RuntimeError(' '.join(map(str,cmd))+'\n'+p.stdout+p.stderr)
    return (time.perf_counter()-begin)*1000,p.stdout

def summary(xs):
    xs=sorted(xs)
    return {'median':statistics.median(xs),'min':min(xs),'max':max(xs),'samples':xs}

def driver_code(case,sites,instrumented):
    kind,n=CASES[case]
    declarations='\n'.join(kind+' build_'+str(i)+'();' for i in range(sites))
    checks=''.join('{auto v=build_'+str(i)+'();if(digest(v)!='+str(digest(values(case,i),kind))+'ULL)return 2;}' for i in range(sites))
    head='#include "model.hpp"\n#include <chrono>\n#include <cstdio>\n#include <cstdlib>\n#include <cstring>\n#include <algorithm>\nusing namespace bench;\n'+declarations+'\n'
    if instrumented:
        return head+'''int main(int argc,char** argv){
    if(argc!=2)return 1;
    const char* mode=argv[1];bool sized=std::strcmp(mode,"append")!=0;
    reset_counts();counting=true;
    {auto v=build_0();if(digest(v)!='''+str(digest(values(case,0),kind))+'''ULL)return 2;
        if(sized&&!presized(v))return 3;}
    counting=false;if(counts.live)return 4;
    bool copies=std::strstr(mode,"list")||std::strcmp(mode,"constructor_array")==0||std::strcmp(mode,"static_array")==0;
    (void)copies;
    unsigned expected='''+('(copies?17:9)' if kind=='Matrix' else '1')+''';
    if(sized&&(counts.buffers!=expected||counts.growth!=0))return 5;
    auto c=counts;
    std::printf("{\\"allocations\\":%llu,\\"bytes\\":%llu,\\"peak\\":%llu,\\"copies\\":%llu,\\"moves\\":%llu,\\"buffers\\":%llu,\\"growth\\":%llu}\\n",
        (unsigned long long)c.allocations,(unsigned long long)c.bytes,(unsigned long long)c.peak,
        (unsigned long long)c.copies,(unsigned long long)c.moves,(unsigned long long)c.buffers,(unsigned long long)c.growth);
}'''
    return head+'''int main(){'''+checks+'''
    auto run=[](long loops){std::uint64_t sink=0;auto start=std::chrono::steady_clock::now();
        for(long i=0;i<loops;++i){auto value=build_0();sink+=observe(value);}
        auto end=std::chrono::steady_clock::now();
        if(!sink)std::abort();return std::chrono::duration<double,std::nano>(end-start).count()/loops;};
    double warm=run(200);long loops=std::clamp(static_cast<long>(30000000.0/warm),100L,1000000L);
    double ns=run(loops);
    '''+checks+'''std::printf("{\\"ns\\":%.4f,\\"loops\\":%ld}\\n",ns,loops);
}'''

def main():
    ap=argparse.ArgumentParser();ap.add_argument('--runtime',type=Path,required=True);ap.add_argument('--out',type=Path,required=True);ap.add_argument('--cxx',default='clang++');ap.add_argument('--repeats',type=int,default=5);ap.add_argument('--profiles',type=int,default=3);ap.add_argument('--sites',type=int,default=12);ap.add_argument('--smoke',action='store_true');args=ap.parse_args()
    assert min(args.sites,args.repeats,args.profiles)>0
    root=Path(__file__).resolve().parent;out=args.out.resolve();out.mkdir(parents=True,exist_ok=True);runtime=args.runtime.resolve()
    flags=['-std=c++23','-O2','-g0','-Wall','-Wextra','-Werror','-I'+str(root),'-I'+str(runtime/'generated/public')]
    private=['-I'+str(runtime/'src'/p) for p in ['numeric','sequence','hash']]+['-I'+str(runtime/'generated/private')]
    report={'revision':2,'method':{'flags':flags,'repeats':args.repeats,'profiles':args.profiles,'sites':args.sites,'lto':False,'pch':False,'ccache':False,'parallel_jobs':1,'library':'libstdc++','clean_timing':True,'separate_trace_compiles':True,'runtime':'production scpp::string; isolated concrete containers; no PHP frontend'},'machine':{'hostname':platform.node(),'platform':platform.platform(),'cpu':command(['lscpu'],out)[1],'affinity':sorted(os.sched_getaffinity(0)),'load':os.getloadavg(),'compiler':command([args.cxx,'--version'],out)[1]},'cases':{},'setup':[]}
    for name,src,extra in [('production',runtime/'generated/src/runtime.cpp',private),('model',root/'model.cpp',[]),('model-counted',root/'model.cpp',['-DBENCH_INSTRUMENT'])]:
        ms,_=command([args.cxx,*flags,*extra,'-c',src,'-o',out/(name+'.o')],out)
        report['setup'].append({'file':name,'ms':ms,'sha256':hashlib.sha256(src.read_bytes()).hexdigest()})
    for name,model in [('runtime','model'),('counted','model-counted')]:
        ms,_=command(['ar','rcs',out/(name+'.a'),out/(model+'.o'),out/'production.o'],out);report['setup'].append({'file':name+' archive','ms':ms})
    symbols=command(['nm','-C','--defined-only',out/'model.o'],out)[1]
    assert all(s not in symbols for s in ['operator new(', 'operator delete(', 'bench::counts','bench::counting','allocation_budget']),symbols
    report['clean_symbol_check']=True
    cases=['int3','heap16','nested8x16'] if args.smoke else list(CASES)
    jobs=[]
    for case in cases:
        kind,_=CASES[case]
        for counted in [False,True]:
            stem=case+('-counted' if counted else '-driver');src=out/(stem+'.cpp');src.write_text(driver_code(case,args.sites,counted))
            ms,_=command([args.cxx,*flags,*(['-DBENCH_INSTRUMENT'] if counted else []),'-c',src,'-o',src.with_suffix('.o')],out);report.setdefault('driver_setup_ms',{})[stem]=ms
        for mode in MODES:
            name=case+'-'+mode;src=out/(name+'.cpp');obj=src.with_suffix('.o');exe=out/(name+'.exe')
            src.write_text('#include "model.hpp"\nusing namespace bench;\n'+''.join(kind+' build_'+str(i)+'(){ return '+expression(mode,kind,values(case,i))+'; }\n' for i in range(args.sites)))
            report['cases'][name]={'case':case,'mode':mode,'source_bytes':src.stat().st_size,'source_sha256':hashlib.sha256(src.read_bytes()).hexdigest(),**{k:[] for k in ['compile_ms','link_ms','native_wall_ms','frontend_ms','backend_ms','optimizer_ms','codegen_ms','execution_ns','profile_compile_ms','runs','allocation_runs']}}
            jobs.append((name,case,mode,src,obj,exe))
    rng=random.Random(20261119)
    for repeat in range(args.repeats):
        shuffled=jobs.copy();rng.shuffle(shuffled)
        for name,case,mode,src,obj,exe in shuffled:
            entry=report['cases'][name]
            ms,_=command([args.cxx,*flags,'-c',src,'-o',obj],out)
            link,_=command([args.cxx,'-O2',obj,out/(case+'-driver.o'),out/'runtime.a','-o',exe],out)
            entry['compile_ms'].append(ms);entry['link_ms'].append(link);entry['native_wall_ms'].append(ms+link)
            entry['object_bytes']=obj.stat().st_size;entry['executable_bytes']=exe.stat().st_size
            run=json.loads(command([exe],out)[1]);entry['execution_ns'].append(run['ns']);entry['runs'].append(run)
        (out/'raw.json').write_text(json.dumps(report,indent=2)+'\n');print('clean round',repeat+1,'passed',flush=True)
    # Profiling and counting are separate binaries/compilations, never timing samples.
    for repeat in range(args.profiles):
        shuffled=jobs.copy();rng.shuffle(shuffled)
        for name,case,mode,src,obj,exe in shuffled:
            entry=report['cases'][name];trace=out/(name+'.trace.json')
            ms,_=command([args.cxx,*flags,'-ftime-trace='+str(trace),'-c',src,'-o',out/'profile.o'],out);entry['profile_compile_ms'].append(ms)
            totals={x['name']:x.get('dur',0)/1000 for x in json.loads(trace.read_text())['traceEvents'] if x['name'].startswith('Total ')}
            for key,event in [('frontend_ms','Total Frontend'),('backend_ms','Total Backend'),('optimizer_ms','Total Optimizer'),('codegen_ms','Total CodeGenPasses')]:
                assert event in totals,(name,event)
                entry[key].append(totals[event])
            counted=out/(name+'-counted.exe');command([args.cxx,'-O2',obj,out/(case+'-counted.o'),out/'counted.a','-o',counted],out)
            profile=json.loads(command([counted,mode],out)[1]);entry['allocation_runs'].append(profile)
        print('profile/count round',repeat+1,'passed',flush=True)
    for entry in report['cases'].values():
        for key in ['compile_ms','link_ms','native_wall_ms','frontend_ms','backend_ms','optimizer_ms','codegen_ms','execution_ns','profile_compile_ms']:entry[key]=summary(entry[key])
        assert all(p==entry['allocation_runs'][0] for p in entry['allocation_runs'])
        entry['allocation_profile']=entry['allocation_runs'][0]
    report['machine']['load_end']=os.getloadavg();(out/'results.json').write_text(json.dumps(report,indent=2)+'\n');print('Results:',out/'results.json',flush=True)

if __name__=='__main__':main()
