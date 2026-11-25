#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentDependencyEdgeSnapshotRow;
struct ResidentReverseDependencyIndexRow;
int_t<std::uint32_t> __latency_fn_resident_reverse_dependency_indexes_append_ref_from_edge(shared_p<CompilerProjectRunReport>& report, ResidentReverseDependencyIndexRow index, ResidentDependencyEdgeSnapshotRow edge);
}
