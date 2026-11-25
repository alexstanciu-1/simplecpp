"""Runtime-first probes for the S09/V-R07 closure batch."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1];sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',type=Path,required=True)
out=p.parse_args().results.resolve();out.mkdir(parents=True,exist_ok=False)
source=r'''#include "scpp/lang/php.hpp"
#include <iostream>
#include <memory>
#include <limits>
using I=scpp::int_t<>;
void check(bool ok){if(!ok)throw std::runtime_error("runtime prerequisite failed");}
struct Lifetime { static inline int live=0; Lifetime(){++live;} ~Lifetime(){--live;} };
int main(){
 scpp::nullable<I> n{I{0}};scpp::result<I> r{I{7}};scpp::result_or_false<I> f{I{8}};scpp::result_or_bool<I> b{I{9}};
 check(n.require_value("probe").native_value()==0 && r.require_value("probe").native_value()==7
  && f.require_value("probe").native_value()==8 && b.require_value("probe").native_value()==9);
 int missing=0;scpp::nullable<I> absent{};
 try{absent.require_value("probe");}catch(const scpp::runtime_error& e){check(e.code()=="invalid_nullable_unwrap_empty");++missing;}
 auto bad=[&](const auto& v){try{v.require_value("probe");}catch(const std::runtime_error& e){check(std::string(e.what()).find("requires a present value")!=std::string::npos);++missing;}check(!v.has_value().native_value());};
 bad(scpp::result<I>{scpp::error_t{scpp::string_t{"failure"}}});bad(scpp::result_or_false<I>{scpp::false_sentinel});
 bad(scpp::result_or_bool<I>{scpp::false_sentinel});bad(scpp::result_or_bool<I>{scpp::true_sentinel});check(missing==5);
 auto v=scpp::php::vector_filled(I{3},I{7});check(v.size()==3 && v.at(I{2}).native_value()==7);
 scpp::php::vector_reserve(v,I{16});auto capacity=v.capacity();check(capacity>=16);
 scpp::php::vector_resize(v,I{5},I{9});check(v.at(I{0}).native_value()==7 && v.at(I{4}).native_value()==9);
 scpp::php::vector_resize(v,I{2},I{0});check(v.size()==2 && v.at(I{1}).native_value()==7);
 scpp::php::vector_compact(v,I{1});check(v.size()==2 && v.capacity()>=2 && v.at(I{1}).native_value()==7);
 scpp::php::vector_compact(v);check(v.capacity()>=v.size());
 scpp::php::vector_reserve(v,I{12});capacity=v.capacity();scpp::php::vector_clear(v);check(v.size()==0 && v.capacity()==capacity);
 scpp::php::vector_resize(v,I{2},I{4});scpp::php::vector_clear_keep_capacity(v);check(v.size()==0 && v.capacity()==capacity);
 check(scpp::php::vector_capacity(v).native_value()==static_cast<std::int64_t>(capacity));
 int errors=0;auto negative=[&](auto call){try{call();}catch(const scpp::runtime_error& e){check(e.code()=="value_error");++errors;}check(v.size()==0 && v.capacity()==capacity);};
 for(auto z:{std::int64_t{-1},std::numeric_limits<std::int64_t>::min()}){
  negative([&]{scpp::php::vector_reserve(v,I{z});});negative([&]{scpp::php::vector_resize(v,I{z},I{3});});
  negative([&]{scpp::php::vector_filled(I{z},I{3});});negative([&]{scpp::php::vector_compact(v,I{z});});
 }check(errors==8);
 scpp::vector_t<std::unique_ptr<Lifetime>> owned;owned.append(std::make_unique<Lifetime>());check(Lifetime::live==1);
 scpp::php::vector_reserve(owned,I{8});scpp::php::vector_compact(owned,I{2});scpp::php::vector_compact(owned);
 check(owned.size()==1 && Lifetime::live==1);auto kept=owned.capacity();scpp::php::vector_clear_keep_capacity(owned);
 check(Lifetime::live==0 && owned.capacity()==kept);
 std::cout<<"4 payloads; 5 extraction failures; 8 vector forms; 8 negative checks; move-only lifetime preserved\n";
}'''
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True);cpp.flags.extend(['-fsanitize=address,undefined','-fno-sanitize-recover=undefined'])
 def probe():
  path=out/'runtime.cpp';path.write_text(source);cpp.compile('runtime-build',path,out/'native');runner.run('runtime-execute',['env','ASAN_OPTIONS=detect_leaks=0',out/'native'])
 run_tasks([Task('runtime-prerequisites',probe)])
(out/'summary.json').write_text(json.dumps({'passed':True,'observation':(out/'logs/runtime-execute.stdout').read_text(),'runtime_changes':False},indent=2)+'\n')
