"""Completion runtime prerequisites: power, carrier text casts and NaN ordering."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1];sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks
from cpp_fixture import CppFixtureCompiler
p=argparse.ArgumentParser();p.add_argument('--results',required=True,type=Path);p.add_argument('--final',action='store_true');a=p.parse_args();out=a.results.resolve();out.mkdir(parents=True,exist_ok=False)
prefix='#include "'+str(APP/'tests/cross_identity_support.hpp')+'"\n#include "scpp/cast.hpp"\n#include "operators/arithmetic/power.hpp"\n#include <cmath>\nusing namespace identity_probe;\n'
text=r'''
template<int F> long probe(){long n=0;for(auto s:states<F,std::string>()){auto v=make<F,scpp::string_t>(s);bool failed=false;try{auto r=scpp::cast<scpp::string_t>(v);if(s.kind==state_kind::error)return -1;if(r.native_value()!=(s.kind==state_kind::payload?s.value:std::string{}))return -2;}catch(const std::exception&e){failed=true;if(std::string(e.what()).find("scpp::result:")==std::string::npos)return -4;}if(failed!=(s.kind==state_kind::error)||!same(v,s))return -3;++n;}return n;}
int main(){auto a=probe<0>(),b=probe<1>(),c=probe<2>(),d=probe<3>();if(a<0||b<0||c<0||d<0)return 1;auto n=a+b+c+d;std::cout<<"text_states="<<n<<"\n";}
'''
power=r'''
int main(){long n=0;for(auto b:values<std::int64_t>())for(std::int64_t e=-1;e<=65;++e){std::string error;__int128 expected=1;if(e<0)error="power_negative_exponent";else for(int i=0;i<e;++i){expected*=b;if(expected<std::numeric_limits<std::int64_t>::min()||expected>std::numeric_limits<std::int64_t>::max()){error="power_overflow";break;}}try{auto r=scpp::pow(I{b},I{e});if(!error.empty()||r.native_value()!=expected)return 1;}catch(const scpp::runtime_error&x){if(x.code()!=error||x.operator_symbol()!="**")return 2;}++n;}std::cout<<"power_cases="<<n<<"\n";}
'''
ordering=r'''
int main(){long n=0;for(double x:values<double>())for(double y:values<double>()){bool unordered=std::isnan(x)||std::isnan(y);try{auto r=scpp::numeric_compare(scpp::float_t{x},scpp::float_t{y});if(unordered||r.native_value()!=(x<y?-1:x>y?1:0))return 1;}catch(const scpp::runtime_error&e){if(!unordered||e.code()!="unordered_comparison"||e.operator_symbol()!="<=>")return 2;}++n;}std::cout<<"ordering_cases="<<n<<"\n";}
'''
legacy=r'''
int main(){scpp::float_t x{std::numeric_limits<double>::quiet_NaN()},y{0.};auto r=static_cast<bool>(x<y)?-1:static_cast<bool>(x>y)?1:0;if(r!=0)return 1;std::cout<<"legacy_comparison_nan_result=0; does_not_satisfy_NaN_error_contract\n";}
'''
sources={'text':prefix+text,'power':prefix+power,'ordering':prefix+('#include "operators/ordering/numeric_compare.hpp"\n'+ordering if a.final else legacy)}
with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
 cpp=CppFixtureCompiler(runner,ROOT,out,pch=False,runtime=True)
 def run(name):
  path=out/(name+'.cpp');path.write_text(sources[name]);cpp.compile(name+'-build',path,out/name);runner.run(name+'-execute',[out/name])
 run_tasks([Task(k,lambda k=k:run(k)) for k in sources])
(out/'summary.json').write_text(json.dumps({'observations_passed':True,'text_cast_supported':True,'checked_power_supported':True,'nan_error_ordering_supported':a.final,'baseline_not_acceptance':not a.final},indent=2)+'\n')
