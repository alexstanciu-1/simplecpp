#include "__private_types/EmissionLLVMWorkerInput.hpp"
#include "__private_types/EmissionLLVMModuleCompositionSnapshot.hpp"
#include <cstdio>
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
