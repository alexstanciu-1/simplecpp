#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSymbolDefinitionChangeRow;
void __latency_fn_resident_recompute_targets_append_dependent_resolution_targets_from_lookup_cursor(shared_p<CompilerProjectRunReport>& report, ResidentSymbolDefinitionChangeRow change, int_t<std::uint32_t> firstLookupId);
}
