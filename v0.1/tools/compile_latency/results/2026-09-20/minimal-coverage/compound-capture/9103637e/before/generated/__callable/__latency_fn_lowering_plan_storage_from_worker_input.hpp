#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class BackendLoweringWorkerInput;
struct StorageLifetimeRequestRow;
StorageLifetimeRequestRow __latency_fn_lowering_plan_storage_from_worker_input(shared_p<BackendLoweringWorkerInput> input);
}
