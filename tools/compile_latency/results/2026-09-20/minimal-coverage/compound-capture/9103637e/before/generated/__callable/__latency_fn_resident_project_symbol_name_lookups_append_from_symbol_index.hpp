#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
class ProjectSymbolIndex;
void __latency_fn_resident_project_symbol_name_lookups_append_from_symbol_index(shared_p<CompilerProjectRunReport>& report, shared_p<ProjectSymbolIndex> symbols, int_t<std::uint32_t> ownerRunId);
}
