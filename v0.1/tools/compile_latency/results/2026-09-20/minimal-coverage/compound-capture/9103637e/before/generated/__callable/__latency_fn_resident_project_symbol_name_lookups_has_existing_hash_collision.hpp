#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentProjectSymbolNameLookupRow;
bool_t __latency_fn_resident_project_symbol_name_lookups_has_existing_hash_collision(shared_p<CompilerProjectRunReport> report, ResidentProjectSymbolNameLookupRow candidate);
}
