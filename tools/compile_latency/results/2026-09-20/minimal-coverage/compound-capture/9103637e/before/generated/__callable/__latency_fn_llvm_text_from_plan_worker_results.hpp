#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class EmissionLLVMWorkerInput;
class EmissionLLVMWorkerResult;
vector_t<shared_p<EmissionLLVMWorkerResult>> __latency_fn_llvm_text_from_plan_worker_results(const vector_t<shared_p<EmissionLLVMWorkerInput>>& inputs, int_t<> workerCount);
}
