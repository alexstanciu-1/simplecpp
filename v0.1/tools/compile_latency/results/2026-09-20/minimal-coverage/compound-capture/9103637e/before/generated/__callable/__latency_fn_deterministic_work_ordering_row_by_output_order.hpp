#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class DeterministicWorkOrderArtifact;
struct DeterministicWorkOrderRow;
DeterministicWorkOrderRow __latency_fn_deterministic_work_ordering_row_by_output_order(shared_p<DeterministicWorkOrderArtifact> artifact, int_t<std::uint32_t> outputOrderId);
}
