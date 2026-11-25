#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendLoweringWorkerInput;
struct ProjectCallableContractRow;
ProjectCallableContractRow __latency_fn_lowering_plan_contract_from_worker_input(shared_p<BackendLoweringWorkerInput> input);
}
