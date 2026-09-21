#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct OperationReadiness;
class StorageLifetimeWorkerInput;
OperationReadiness __latency_fn_storage_lifetime_readiness_operation_from_worker_input(shared_p<StorageLifetimeWorkerInput> input, bool_t ready);
}
