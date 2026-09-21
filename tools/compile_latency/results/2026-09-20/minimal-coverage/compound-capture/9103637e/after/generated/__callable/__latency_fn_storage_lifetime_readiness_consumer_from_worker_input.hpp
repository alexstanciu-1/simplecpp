#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct CapabilityConsumerRow;
class StorageLifetimeWorkerInput;
CapabilityConsumerRow __latency_fn_storage_lifetime_readiness_consumer_from_worker_input(shared_p<StorageLifetimeWorkerInput> input, bool_t ready);
}
