#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSourceUnitFrontendStateRow;
struct SourceUnitTableRow;
ResidentSourceUnitFrontendStateRow __latency_fn_resident_source_unit_frontend_states_append_reused_previous(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previous, int_t<std::uint32_t> ownerRunId, SourceUnitTableRow sourceUnit);
}
