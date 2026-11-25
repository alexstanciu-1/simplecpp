#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendEmissionDecisionArtifact;
struct LlvmApiHandleMapSummaryRow;
struct LlvmApiModuleBuildRow;
LlvmApiHandleMapSummaryRow __latency_fn_llvm_api_sink_preflight_handle_summary_row_from_module_build(int_t<std::uint32_t> summaryId, LlvmApiModuleBuildRow build, BackendEmissionDecisionArtifact& emission);
}
