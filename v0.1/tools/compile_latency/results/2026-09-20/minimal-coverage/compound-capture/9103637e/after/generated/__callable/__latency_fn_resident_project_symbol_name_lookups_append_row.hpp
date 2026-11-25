#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentProjectSymbolNameLookupRow;
void __latency_fn_resident_project_symbol_name_lookups_append_row(shared_p<CompilerProjectRunReport>& report, ResidentProjectSymbolNameLookupRow row);
}
