#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSourceUnitEarlySkipDecisionRow;
struct ResidentSourceUnitFrontendWorkDecisionRow;
ResidentSourceUnitFrontendWorkDecisionRow __latency_fn_source_unit_frontend_work_decisions_row_from_early_skip(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previous, ResidentSourceUnitEarlySkipDecisionRow earlySkip, vector_t<int_t<std::uint32_t>>& sourceChangeIds, vector_t<int_t<std::uint32_t>>& previousTokenSnapshotIds, vector_t<int_t<std::uint32_t>>& currentTokenSnapshotIds, vector_t<int_t<std::uint32_t>>& previousFrontendSnapshotIds, vector_t<int_t<std::uint32_t>>& currentFrontendSnapshotIds);
}
