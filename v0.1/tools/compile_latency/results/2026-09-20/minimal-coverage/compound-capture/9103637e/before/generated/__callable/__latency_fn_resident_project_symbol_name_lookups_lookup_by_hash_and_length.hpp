#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentProjectSymbolNameLookupRow;
ResidentProjectSymbolNameLookupRow __latency_fn_resident_project_symbol_name_lookups_lookup_by_hash_and_length(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> lookupReport, vector_t<int_t<std::uint32_t>>& lookupIds, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> nameHash, int_t<std::uint32_t> nameLength);
}
