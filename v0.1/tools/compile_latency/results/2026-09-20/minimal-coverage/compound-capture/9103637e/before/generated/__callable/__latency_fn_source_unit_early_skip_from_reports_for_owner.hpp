#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSourceUnitEarlySkipArtifact;
ResidentSourceUnitEarlySkipArtifact __latency_fn_source_unit_early_skip_from_reports_for_owner(shared_p<CompilerProjectRunReport> currentReport, shared_p<CompilerProjectRunReport> previousReport, int_t<std::uint32_t> ownerRunId);
}
