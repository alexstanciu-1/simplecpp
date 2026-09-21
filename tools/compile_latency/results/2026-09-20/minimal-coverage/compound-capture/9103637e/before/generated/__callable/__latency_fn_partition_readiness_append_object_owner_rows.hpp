#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct CompilerProjectRunRow;
void __latency_fn_partition_readiness_append_object_owner_rows(shared_p<CompilerProjectRunReport>& report, CompilerProjectRunRow projectRunRow);
}
