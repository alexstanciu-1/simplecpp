#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentReverseDependencyLookupRow;
int_t<std::uint32_t> __latency_fn_resident_reverse_dependencies_append_lookup(shared_p<CompilerProjectRunReport>& report, ResidentReverseDependencyLookupRow row);
}
