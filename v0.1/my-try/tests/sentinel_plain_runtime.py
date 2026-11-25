"""Direct runtime proof for 24 sentinel/plain comparisons and preserved boundaries."""
import argparse
import json
from pathlib import Path
import sys
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
parser=argparse.ArgumentParser(description=__doc__);parser.add_argument('--results',required=True,type=Path)
out=parser.parse_args().results.resolve();out.mkdir(parents=True,exist_ok=False)
source=r'''
#include "operators/identity/strict_identity.hpp"
#include <vector>
#include <limits>
#include <iostream>
using I=scpp::int_t<>; using D=scpp::float_t; using S=scpp::string_t; using B=scpp::bool_t;
long checks=0;
template<class W,class V> bool exercise(const std::vector<V>& values,bool two) {
 std::vector<W> states{W{scpp::false_sentinel}};
 if constexpr(std::is_same_v<W,scpp::result_or_bool<V>>) states.push_back(W{scpp::true_sentinel});
 for(auto v:values) states.push_back(W{v});
 for(std::size_t i=0;i<states.size();++i) for(auto v:values) {
  const auto& w=states[i]; bool expected=i>=(two?2u:1u) && (w.value()==v).native_value();
  if((w==v).native_value()!=expected || (v==w).native_value()!=expected || (w!=v).native_value()==expected || (v!=w).native_value()==expected) return false;
  if(w.has_value().native_value()!=(i>=(two?2u:1u))) return false;
  if(i==0 && !w.is_false().native_value()) return false;
  if constexpr(std::is_same_v<W,scpp::result_or_bool<V>>) if(i==1 && !w.is_true().native_value()) return false;
  checks+=4;
 }
 return true;
}
// Compare the unchanged boxed route with its original scalar-bool delegation,
// including exception outcomes, without assuming PHP loose-coercion behavior.
template<class F> std::string outcome(F f) { try {return f().native_value()?"true":"false";} catch(const std::exception& e) {return std::string("error:")+e.what();} }
template<class W> bool preserve(bool truth) {
 W w{scpp::false_sentinel};
 if constexpr(std::is_same_v<W,scpp::result_or_bool<B>>) if(truth) w=W{scpp::true_sentinel};
 B b{truth};
 for(bool value:{false,true}) {
  B v{value}; if((w==v).native_value()!=(truth==value) || (v==w).native_value()!=(truth==value)) return false;
  W payload{v}; if((w==payload).native_value() || scpp::identical(w,payload).native_value()!=(truth==value)) return false;
 }
 for(auto m:std::vector<scpp::mixed_t>{scpp::mixed_t(B{false}),scpp::mixed_t(B{true}),scpp::mixed_t(I{0}),scpp::mixed_t(S{""})}) {
  if(outcome([&]{return w==m;})!=outcome([&]{return b==m;}) || outcome([&]{return m==w;})!=outcome([&]{return m==b;})) return false;
 }
 return true;
}
int main() {
 std::vector<I> ints{I{0},I{1},I{-1},I{std::numeric_limits<std::int64_t>::min()},I{std::numeric_limits<std::int64_t>::max()}};
 std::vector<D> floats{D{0.0},D{-0.0},D{1.0},D{-1.0},D{std::numeric_limits<double>::infinity()},D{-std::numeric_limits<double>::infinity()},D{std::numeric_limits<double>::quiet_NaN()}};
 std::vector<S> strings{S{""},S{"0"},S{"1"},S{"text"},S{std::string("a\0b",3)},S{std::string("\xff",1)}};
 if(!exercise<scpp::result_or_false<I>>(ints,false) || !exercise<scpp::result_or_bool<I>>(ints,true)) return 1;
 if(!exercise<scpp::result_or_false<D>>(floats,false) || !exercise<scpp::result_or_bool<D>>(floats,true)) return 2;
 if(!exercise<scpp::result_or_false<S>>(strings,false) || !exercise<scpp::result_or_bool<S>>(strings,true)) return 3;
 if(!preserve<scpp::result_or_false<B>>(false) || !preserve<scpp::result_or_bool<B>>(false) || !preserve<scpp::result_or_bool<B>>(true)) return 4;
 std::cout<<"direct_comparisons="<<checks<<"\n";
}
'''
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
    cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
    target=out/'runtime.cpp';target.write_text(source)
    def positive():
        cpp.compile('runtime-build',target,out/'runtime')
        runner.run('runtime-execute',[out/'runtime'])
    def negative(payload,op,reverse):
        name=payload+'-'+('eq' if op=='==' else 'ne')+str(reverse)
        v={'int':'scpp::int_t<>','float':'scpp::float_t','string':'scpp::string_t'}[payload]
        expression='v '+op+' b' if reverse else 'b '+op+' v'
        f=out/(name+'.cpp');f.write_text('#include "operators/identity/strict_identity.hpp"\nscpp::bool_t probe(scpp::bool_t b,'+v+' v) { return '+expression+'; }')
        runner.run(name,['clang++','-std=c++20','-I',ROOT/'runtime/include','-fsyntax-only',f],expected=1)
        assert 'unsupported operator== combination' in (out/'logs'/(name+'.stderr')).read_text()
    def wrapper_negative(carrier,fixed,reverse):
        name=carrier+('-fixed' if fixed else '-string')+str(reverse)
        payload='scpp::int_t<std::int16_t>' if fixed else 'scpp::int_t<>'
        plain=payload if fixed else 'scpp::string_t'
        expression='v == w' if reverse else 'w == v'
        f=out/(name+'.cpp');f.write_text('#include "operators/identity/strict_identity.hpp"\nscpp::bool_t probe(scpp::'+carrier+'<'+payload+'> w,'+plain+' v) { return '+expression+'; }')
        runner.run(name,['clang++','-std=c++20','-I',ROOT/'runtime/include','-fsyntax-only',f],expected=1)
        assert 'unsupported operator== combination' in (out/'logs'/(name+'.stderr')).read_text()
    run_tasks([Task('runtime',positive)]+[Task(f'{p}-{op}-{rev}',lambda p=p,op=op,rev=rev:negative(p,op,rev)) for p in ['int','float','string'] for op in ['==','!='] for rev in [False,True]]+[Task(f'{c}-{fixed}-{rev}',lambda c=c,fixed=fixed,rev=rev:wrapper_negative(c,fixed,rev)) for c in ['result_or_false','result_or_bool'] for fixed in [False,True] for rev in [False,True]])
(out/'summary.json').write_text(json.dumps({'passed':True,'binary_signatures':24,'scalar_rejections':12,'wrapper_boundary_rejections':8,'bool_and_boxed_controls':True},indent=2)+'\n')
