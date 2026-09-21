#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentProjectSymbolNameLookupRow;
ResidentProjectSymbolNameLookupRow __latency_fn_resident_project_symbol_name_lookups_row_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> lookupId);
}
