#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSourceUnitFrontendStateRow;
ResidentSourceUnitFrontendStateRow __latency_fn_resident_function_body_ownership_frontend_state_from_lookup(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> report, vector_t<int_t<std::uint32_t>>& stateIds, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId);
}
