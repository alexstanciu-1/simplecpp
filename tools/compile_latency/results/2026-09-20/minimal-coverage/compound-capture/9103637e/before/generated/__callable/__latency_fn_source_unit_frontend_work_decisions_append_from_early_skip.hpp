#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSourceUnitEarlySkipArtifact;
void __latency_fn_source_unit_frontend_work_decisions_append_from_early_skip(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previous, ResidentSourceUnitEarlySkipArtifact artifact, int_t<std::uint32_t> ownerRunId);
}
