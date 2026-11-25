#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSourceUnitChangeRow;
ResidentSourceUnitChangeRow __latency_fn_resident_recompute_targets_source_change_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> changeId);
}
