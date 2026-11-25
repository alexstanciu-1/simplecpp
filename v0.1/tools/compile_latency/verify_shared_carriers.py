#!/usr/bin/env python3
"""Prove exact carrier definitions, standalone headers and shared/vector behavior."""
import argparse,re,subprocess
from pathlib import Path
from callable_surface import CLASS
from experiment import require_scratch,write_changed
p=argparse.ArgumentParser();p.add_argument('--app',type=Path,required=True);p.add_argument('--out',type=Path,required=True);a=p.parse_args();app=a.app.resolve();require_scratch(app);a.out.mkdir(parents=True,exist_ok=True)
build=app/'.prism/build';gen=app/'.prism/expanded/generated';owner='compile/backend/llvm_text_from_plan.hpp'
names=['EmissionLLVMWorkerInput','EmissionLLVMModuleCompositionSnapshot']
for name in names:
 original=next(m[0] for m in CLASS.finditer((app/'.prism/generated'/owner).read_text()) if m[1]==name)
 assert original in (gen/'__private_types'/f'{name}.hpp').read_text()
 assert not re.search(r'\b'+name+r'\b',(gen/owner).read_text())
headers=''.join('#include "__private_types/'+name+'.hpp"\n' for name in names)
standalone=headers+'void check_headers() { scpp::EmissionLLVMWorkerInput worker; scpp::EmissionLLVMModuleCompositionSnapshot snapshot; }\n'
code=headers+'''#include <cstdio>
using namespace scpp;
int main() {
 auto worker = shared_p<EmissionLLVMWorkerInput>(std::make_shared<EmissionLLVMWorkerInput>());
 auto alias = worker;
 alias->owner_run_id = int_t<>(73);
 alias->request_ids.push_back(int_t<>(41));
 alias->local_value_texts.push_back(string_t("carrier-witness"));
 if (!static_cast<bool>(php::identical(worker->owner_run_id,int_t<>(73)))) return 1;
 if (!static_cast<bool>(php::identical(worker->request_ids[int_t<>(0)],int_t<>(41)))) return 2;
 if (!static_cast<bool>(php::identical(worker->local_value_texts[int_t<>(0)],string_t("carrier-witness")))) return 3;
 auto snapshot = shared_p<EmissionLLVMModuleCompositionSnapshot>(std::make_shared<EmissionLLVMModuleCompositionSnapshot>());
 auto requests = shared_p<BackendRequestAuthorizationArtifact>(std::make_shared<BackendRequestAuthorizationArtifact>());
 auto plan = shared_p<LoweringPlan>(std::make_shared<LoweringPlan>());
 snapshot->target_requests = requests;
 snapshot->target_plan = plan;
 snapshot->target_requests->ready_count = int_t<std::uint32_t>(7);
 snapshot->target_plan->work_ids.push_back(int_t<std::uint32_t>(19));
 if (!static_cast<bool>(php::identical(requests->ready_count,int_t<std::uint32_t>(7)))) return 4;
 if (!static_cast<bool>(php::identical(plan->work_ids[int_t<>(0)],int_t<std::uint32_t>(19)))) return 5;
 if (!static_cast<bool>(EmissionLLVMWorkerInput::__scpp_static_accepts(EmissionLLVMWorkerInput::__scpp_static_token()))) return 6;
 if (static_cast<bool>(EmissionLLVMWorkerInput::__scpp_static_accepts(EmissionLLVMModuleCompositionSnapshot::__scpp_static_token()))) return 7;
 if (!static_cast<bool>(EmissionLLVMModuleCompositionSnapshot::__scpp_static_accepts(EmissionLLVMModuleCompositionSnapshot::__scpp_static_token()))) return 8;
 std::puts("carrier_bytes=unchanged;identity=ok;shared_mutation=ok;vector_contents=ok;shared_dependencies=ok");
}
'''
write_changed(build/'carrier-headers.cpp',standalone);write_changed(build/'carrier-proof.cpp',code)
graph=(build/'latency-expanded.ninja').read_text();link=re.search(r'^build main: link (.+)$',graph,re.M);objects=link[1].split();assert objects.count('expanded_parts/main.o')==1;objects[objects.index('expanded_parts/main.o')]='carrier-proof.o';graph=graph[:link.start()]+'build carrier-proof: link '+' '.join(objects)+graph[link.end():];graph=graph.replace('default main','')
graph+='\nbuild carrier-proof.o: compile_callable_part carrier-proof.cpp | expanded-project.pch\nbuild carrier-headers.o: compile carrier-headers.cpp | expanded_runtime_pch.hpp.gch\ndefault carrier-proof carrier-headers.o\n'
write_changed(build/'carrier-proof.ninja',graph)
r=subprocess.run(['ninja','-f','carrier-proof.ninja','-j12'],cwd=build,text=True,capture_output=True);(a.out/'build.log').write_text(r.stdout+r.stderr);r.check_returncode()
r=subprocess.run([str(build/'carrier-proof')],cwd=app,text=True,capture_output=True);(a.out/'run.log').write_text(r.stdout+r.stderr);r.check_returncode();(a.out/'carrier-proof.cpp').write_text(code);(a.out/'carrier-headers.cpp').write_text(standalone);print(r.stdout.strip()+';standalone_headers=ok')
