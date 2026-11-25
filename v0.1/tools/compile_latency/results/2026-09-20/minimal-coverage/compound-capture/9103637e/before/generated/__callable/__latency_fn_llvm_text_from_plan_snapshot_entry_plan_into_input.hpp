#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class EmissionLLVMWorkerInput;
class LoweringPlan;
void __latency_fn_llvm_text_from_plan_snapshot_entry_plan_into_input(shared_p<EmissionLLVMWorkerInput>& input, shared_p<LoweringPlan> plan);
}
