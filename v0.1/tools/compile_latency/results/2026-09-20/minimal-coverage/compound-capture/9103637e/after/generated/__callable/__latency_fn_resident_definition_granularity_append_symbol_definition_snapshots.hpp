#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
class ProjectSymbolIndex;
void __latency_fn_resident_definition_granularity_append_symbol_definition_snapshots(shared_p<CompilerProjectRunReport>& report, shared_p<ProjectSymbolIndex> index, int_t<std::uint32_t> ownerRunId);
}
