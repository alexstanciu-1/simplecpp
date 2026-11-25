#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSourceUnitChangeRow;
void __latency_fn_resident_recompute_targets_append_targets_for_source_change(shared_p<CompilerProjectRunReport>& report, ResidentSourceUnitChangeRow change);
}
