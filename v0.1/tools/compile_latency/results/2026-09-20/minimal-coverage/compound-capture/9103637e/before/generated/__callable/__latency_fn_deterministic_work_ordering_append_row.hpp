#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class DeterministicWorkOrderArtifact;
struct DeterministicWorkOrderRow;
void __latency_fn_deterministic_work_ordering_append_row(shared_p<DeterministicWorkOrderArtifact>& artifact, DeterministicWorkOrderRow row);
}
