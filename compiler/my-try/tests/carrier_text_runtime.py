"""Probe existing text helpers and state/payload composition before source admission."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
p=argparse.ArgumentParser(description=__doc__);p.add_argument('--results',required=True,type=Path);a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
source=r'''
#include "lang/php/support/php_string.hpp"
#include "scpp/cast.hpp"
#include "SUPPORT"
using namespace identity_probe;
template<int F,class V,class P> long check(){long n=0;for(auto state:states<F,P>()){auto v=make<F,V>(state);bool failed=false;try{scpp::string_t text;
 if constexpr(F==1)text=scpp::php::to_string(v.require_value("text: required Result payload"));
 else text=v.has_value().native_value()?scpp::php::to_string(v.require_value("text: present payload")):scpp::string_t{};
 if(state.kind==state_kind::error)return -1;
 auto expected=state.kind==state_kind::payload?scpp::php::to_string(V{state.value}).native_value():std::string{};
 if(text.native_value()!=expected)return -2;
 }catch(const std::exception&e){failed=true;if(std::string(e.what()).find("scpp::result:")==std::string::npos)return -3;}
 if(failed!=(state.kind==state_kind::error)||!same(v,state))return -4;++n;}return n;}
template<class V,class P> long families(){auto a=check<0,V,P>(),b=check<1,V,P>(),c=check<2,V,P>(),d=check<3,V,P>();if(a<0||b<0||c<0||d<0)throw std::runtime_error("text composition failure");return a+b+c+d;}
int main(){auto n=families<scpp::int_t<>,std::int64_t>()+families<scpp::float_t,double>()+families<scpp::bool_t,bool>()+families<scpp::string_t,std::string>();
 scpp::float_t value{1.23456789012345};auto plain=scpp::php::to_string(value).native_value();auto cast=scpp::cast<scpp::string_t>(scpp::nullable<scpp::float_t>{value}).native_value();
 std::cout<<"plain_float="<<plain<<"\ncarrier_cast_float="<<cast<<"\nformatting_equivalent="<<(plain==cast)<<"\ncomposition_states="<<n<<"\n";
 auto true_state=scpp::result_or_bool<scpp::int_t<>>{scpp::true_sentinel};std::cout<<"display_true_sentinel="<<scpp::php::to_string(true_state).native_value()<<"\ncast_true_sentinel="<<scpp::cast<scpp::string_t>(true_state).native_value()<<"\n";
}
'''.replace('SUPPORT',str(APP/'tests/cross_identity_support.hpp'))
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
 def proof():
  path=out/'probe.cpp';path.write_text(source);cpp.compile('probe-build',path,out/'native');runner.run('probe-execute',[out/'native'])
 run_tasks([Task('runtime-text',proof)])
observations=dict(line.split('=',1) for line in (out/'logs/probe-execute.stdout').read_text().splitlines())
(out/'summary.json').write_text(json.dumps({'passed':True,'observations':observations,'cast_matches_plain_float':observations['formatting_equivalent']=='1','existing_state_payload_composition_supported':True,'runtime_changes':False},indent=2)+'\n')
