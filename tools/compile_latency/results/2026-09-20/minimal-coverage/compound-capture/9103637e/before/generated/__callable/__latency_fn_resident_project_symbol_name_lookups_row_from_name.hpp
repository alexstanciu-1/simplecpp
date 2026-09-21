#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class ProjectSymbolIndex;
struct ResidentProjectSymbolNameLookupRow;
ResidentProjectSymbolNameLookupRow __latency_fn_resident_project_symbol_name_lookups_row_from_name(shared_p<ProjectSymbolIndex> symbols, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> nameId);
}
