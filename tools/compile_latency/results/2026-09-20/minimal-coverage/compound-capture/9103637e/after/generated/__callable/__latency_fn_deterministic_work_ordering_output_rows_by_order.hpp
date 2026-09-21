#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class DeterministicWorkOrderArtifact;
struct DeterministicWorkOrderRow;
vector_t<DeterministicWorkOrderRow> __latency_fn_deterministic_work_ordering_output_rows_by_order(shared_p<DeterministicWorkOrderArtifact> artifact);
}
