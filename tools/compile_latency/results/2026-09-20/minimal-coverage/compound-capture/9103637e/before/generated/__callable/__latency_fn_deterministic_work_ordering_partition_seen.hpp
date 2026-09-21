#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class DeterministicWorkOrderArtifact;
bool_t __latency_fn_deterministic_work_ordering_partition_seen(shared_p<DeterministicWorkOrderArtifact> artifact, int_t<std::uint32_t> partitionId);
}
