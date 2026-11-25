#!/usr/bin/env python3
"""Labelled evaluation order, consuming-input ownership, allocation failures and presizing."""
import argparse, os, subprocess
from pathlib import Path
from run import MODES,construct
p=argparse.ArgumentParser();p.add_argument('--runtime',type=Path,required=True);p.add_argument('--out',type=Path,required=True);p.add_argument('--cxx',default='clang++');args=p.parse_args()
root=Path(__file__).resolve().parent;out=args.out.resolve();out.mkdir(parents=True,exist_ok=True);runtime=args.runtime.resolve()
code=r'''#include "model.hpp"
#include <stdexcept>
#include <cstdio>
#include <string>
#include <cstdlib>
using namespace bench;
static int trace[64],visits=0,fail_id=-1;
static void require(bool ok){if(!ok)std::abort();}
static void reset_trace(){visits=0;fail_id=-1;}
I next(int id){trace[visits++]=id;if(id==fail_id)throw std::runtime_error("input failure");return id*3+1;}
scpp::string payload(int id){std::string s="heap source with identity "+std::to_string(id)+" and full bytes";s.push_back('\0');s+="tail";return scpp::string{s.data(),static_cast<I>(s.size())};}
scpp::string next_text(int id){next(id);return payload(id);}
static bool equal(const scpp::string& a,const scpp::string& b){if(a.size()!=b.size())return false;for(I i=0;i<a.size();++i)if(a.data()[i]!=b.data()[i])return false;return true;}
static int identity(int position){return 17+7*position;}
static void prefix(int n){require(visits==n);for(int i=0;i<n;++i)require(trace[i]==identity(i));}
static void contents(const IVec& v){for(std::size_t i=0;i<v.size();++i)require(v.at(i)==identity(i)*3+1);}
static void contents(const SVec& v){for(std::size_t i=0;i<v.size();++i)require(equal(v.at(i),payload(identity(i))));}
static void contents(const Matrix& v){int k=0;for(std::size_t r=0;r<v.size();++r)for(std::size_t j=0;j<v.at(r).size();++j)require(v.at(r).at(j)==identity(k++)*3+1);}
// A known-good destination remains alive while construction fails at every allocation.
template<class Build> void allocation_failures(Build build,bool traced,int entries){
    bool success=false;
    for(long budget=0;budget<256&&!success;++budget){
        reset_trace();
        {auto destination=build();auto original=digest(destination);
            reset_counts();reset_trace();counting=true;allocation_budget=budget;
            try{destination=build();success=true;}catch(const std::bad_alloc&){}
            allocation_budget=-1;counting=false;
            if(traced){prefix(visits);require(visits<=entries);if(success){prefix(entries);contents(destination);}}
            if(!success)require(digest(destination)==original);
        }
        require(counts.live==0);
    }
    require(success);
}
'''
for index,mode in enumerate(MODES):
    ids=[17+7*i for i in range(12)]
    code+='IVec ints_'+str(index)+'(){return '+construct(mode,'IVec',[f'next({i})' for i in ids[:8]])+';}\n'
    code+='SVec strings_'+str(index)+'(){return '+construct(mode,'SVec',[f'next_text({i})' for i in ids[:8]])+';}\n'
    rows=[construct(mode,'IVec',[f'next({i})' for i in ids[start:start+4]]) for start in range(0,12,4)]
    code+='Matrix nested_'+str(index)+'(){return '+construct(mode,'Matrix',rows)+';}\n'
    for typ,fn,elem in [('SVec','textcopy','scpp::string'),('Matrix','nestedcopy','IVec')]:
        code+=typ+' '+fn+'_'+str(index)+'(const '+elem+'& source){return '+construct(mode,typ,['source','source']).replace('([] {','([&] {')+';}\n'
code+='int main(){\n'
for index,mode in enumerate(MODES):
    copying=mode in ('constructor_list','static_list','constructor_array','static_array')
    for typ,prefix,n in [('IVec','ints',8),('SVec','strings',8),('Matrix','nested',12)]:
        build=prefix+'_'+str(index)+'()'
        buffers=7 if typ=='Matrix' and copying else 4 if typ=='Matrix' else 1
        code+='''{reset_counts();reset_trace();counting=true;
            {auto value='''+build+''';counting=false;prefix('''+str(n)+''');contents(value);'''
        if mode!='append':code+=f'require(presized(value)&&counts.growth==0&&counts.buffers=={buffers});'
        code+='}require(counts.live==0);}\n'
        code+='''for(int failure=0;failure<'''+str(n)+''';++failure){
            reset_trace();
            {auto destination='''+build+''';auto original=digest(destination);
                reset_counts();reset_trace();fail_id=identity(failure);counting=true;bool caught=false;
                try{destination='''+build+''';}catch(const std::runtime_error&){caught=true;}
                counting=false;require(caught);prefix(failure+1);
                require(counts.live==0&&digest(destination)==original);
            }
        }
        allocation_failures([]{return '''+build+''';},true,'''+str(n)+''');
'''
    # Repeated lvalues must be copied to temporary backing before any consuming transfer.
    code+='''{
        IVec source{11,22};const auto original=digest(source);
        allocation_failures([&]{auto v=nestedcopy_'''+str(index)+'''(source);require(digest(source)==original);return v;},false,0);
        require(digest(source)==original);
        reset_counts();counting=true;
        {auto value=nestedcopy_'''+str(index)+'''(source);counting=false;
            require(digest(source)==original&&value.size()==2&&digest(value.at(0))==original&&digest(value.at(1))==original);
            value.at(0).at(0)=99;require(source.at(0)==11&&value.at(1).at(0)==11);
            source.at(1)=77;require(value.at(0).at(1)==22&&value.at(1).at(1)==22);
            source=IVec{};require(value.at(0).at(0)==99&&value.at(1).at(0)==11);
        }require(counts.live==0);
    }
    {
        scpp::string source=payload(999),baseline=source;
        allocation_failures([&]{auto v=textcopy_'''+str(index)+'''(source);require(equal(source,baseline));return v;},false,0);
        require(equal(source,baseline));reset_counts();counting=true;
        {auto value=textcopy_'''+str(index)+'''(source);counting=false;
            require(value.size()==2&&equal(value.at(0),baseline)&&equal(value.at(1),baseline)&&equal(source,baseline));
            value.at(0).append(scpp::string{"!"});require(equal(source,baseline)&&equal(value.at(1),baseline));
            source.append(scpp::string{"?"});require(equal(value.at(1),baseline));
            source=scpp::string{};require(equal(value.at(1),baseline));
        }require(counts.live==0);
    }
'''
# Capacity boundaries and no growth for every append up to a known reserved count.
code+='''for(std::size_t n:{0,1,3,4,5,8,9,64,512}){
    reset_counts();counting=true;
    {IVec v;v.reserve(n);require(v.capacity()==n);
        auto initial=counts.buffers;
        for(std::size_t i=0;i<n;++i){v.append(static_cast<I>(i));require(counts.buffers==initial&&counts.growth==0&&v.capacity()==n);}
    }counting=false;require(counts.live==0&&counts.buffers==(n?1:0));
}
std::puts("PASS: labelled order/results/failure prefixes; allocation-failure assignment atomicity; repeated nested/string lvalue ownership and temporary lifetime; exact presizing/no growth at both levels and capacity boundaries");}
'''
src=out/'proof.cpp';src.write_text(code)
flags=['-std=c++23','-O1','-g','-Wall','-Wextra','-Werror','-DBENCH_INSTRUMENT','-fsanitize=address,undefined','-fsanitize-address-use-after-scope','-fno-sanitize-recover=all','-I'+str(root),'-I'+str(runtime/'generated/public')]+['-I'+str(runtime/'src'/p) for p in ['numeric','sequence','hash']]+['-I'+str(runtime/'generated/private')]
subprocess.run([args.cxx,*flags,src,root/'model.cpp',runtime/'generated/src/runtime.cpp','-o',out/'proof'],check=True)
subprocess.run([out/'proof'],check=True,env={**os.environ,'ASAN_OPTIONS':'detect_stack_use_after_return=1:detect_leaks=1'})
