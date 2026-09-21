#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentFunctionBodySnapshotRow;
void __latency_fn_resident_function_body_ownership_append_snapshot(shared_p<CompilerProjectRunReport>& report, ResidentFunctionBodySnapshotRow row);
}
