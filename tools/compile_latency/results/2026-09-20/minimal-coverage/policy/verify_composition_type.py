#!/usr/bin/env python3
"""Prove unchanged class bytes, shared aliasing and real composition output."""
import argparse,re,subprocess,json
from pathlib import Path
from callable_surface import CLASS
from private_composition_type import TYPE,OWNER,TYPE_HEADER
from experiment import require_scratch,write_changed
p=argparse.ArgumentParser();p.add_argument('--app',type=Path,required=True);p.add_argument('--out',type=Path,required=True);a=p.parse_args();app=a.app.resolve();require_scratch(app);a.out.mkdir(parents=True,exist_ok=True)
build=app/'.prism/build';gen=app/'.prism/expanded/generated'
original=next(m[0] for m in CLASS.finditer((app/'.prism/generated'/OWNER).read_text()) if m[1]==TYPE)
assert original in (gen/TYPE_HEADER).read_text()
assert TYPE not in (gen/OWNER).read_text()
code='''#include "__private_types/BackendFunctionCompositionInput.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_function_composition_input_from_call_arguments_text.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_function_text_from_call_composition.hpp"
#include <cstdio>
using namespace scpp;
int main() {
 if (!static_cast<bool>(BackendFunctionCompositionInput::__scpp_static_accepts(BackendFunctionCompositionInput::__scpp_static_token()))) return 4;
 if (static_cast<bool>(BackendFunctionCompositionInput::__scpp_static_accepts(nullptr))) return 5;
 auto input = __latency_fn_llvm_text_from_plan_function_composition_input_from_call_arguments_text(
 string_t("@main"), string_t("i32"), string_t("define i32 @target() { ret i32 42 }\\n"), string_t("@target"), string_t(""));
 auto alias = input;
 alias->caller_function_name = string_t("@witness");
 if (!static_cast<bool>(php::identical(input->caller_function_name,string_t("@witness")))) return 1;
 input->caller_function_name = string_t("@main");
 auto output = __latency_fn_llvm_text_from_plan_function_text_from_call_composition(input);
 string_t expected("define i32 @target() { ret i32 42 }\\ndefine i32 @main() {\\nentry:\\n  %direct_call_result_1 = call i32 @target()\\n  ret i32 %direct_call_result_1\\n}\\n\\n");
 if (!static_cast<bool>(php::identical(output,expected))) return 2;
 alias->target_function_name = string_t("");
 if (!static_cast<bool>(php::identical(__latency_fn_llvm_text_from_plan_function_text_from_call_composition(input),string_t("")))) return 3;
 std::puts("class_bytes=unchanged;class_identity=ok;shared_aliasing=ok;composition_output=ok;empty_target_rejection=ok");
}
'''
write_changed(build/'composition-proof.cpp',code)
graph=(build/'latency-expanded.ninja').read_text();link=re.search(r'^build main: link (.+)$',graph,re.M);objects=link[1].split();assert objects.count('expanded_parts/main.o')==1;objects[objects.index('expanded_parts/main.o')]='composition-proof.o'
graph=graph[:link.start()]+'build composition-proof: link '+' '.join(objects)+graph[link.end():];graph=graph.replace('default main','');graph+='\nbuild composition-proof.o: compile_callable_part composition-proof.cpp | expanded-project.pch\ndefault composition-proof\n';write_changed(build/'composition-proof.ninja',graph)
r=subprocess.run(['ninja','-f','composition-proof.ninja','-j12'],cwd=build,text=True,capture_output=True);(a.out/'proof-build.log').write_text(r.stdout+r.stderr);r.check_returncode()
r=subprocess.run([str(build/'composition-proof')],cwd=app,text=True,capture_output=True);(a.out/'proof-run.log').write_text(r.stdout+r.stderr);r.check_returncode();(a.out/'composition-proof.cpp').write_text(code)
llvm=a.out/'composition.ll';llvm.write_text('define i32 @target() { ret i32 42 }\ndefine i32 @main() {\nentry:\n  %direct_call_result_1 = call i32 @target()\n  ret i32 %direct_call_result_1\n}\n\n')
subprocess.run(['clang','-Wno-override-module',str(llvm),'-o',str(a.out/'composition')],check=True);assert subprocess.run([str(a.out/'composition')]).returncode==42
print(r.stdout.strip()+';llvm_exit=42')
