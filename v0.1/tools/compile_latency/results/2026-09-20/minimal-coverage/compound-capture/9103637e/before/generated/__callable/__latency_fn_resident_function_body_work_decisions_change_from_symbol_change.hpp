#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentFunctionBodyChangeRow;
struct ResidentSymbolDefinitionChangeRow;
ResidentFunctionBodyChangeRow __latency_fn_resident_function_body_work_decisions_change_from_symbol_change(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previous, ResidentSymbolDefinitionChangeRow change, vector_t<int_t<std::uint32_t>>& currentSnapshotIds, vector_t<int_t<std::uint32_t>>& previousSnapshotIds);
}
