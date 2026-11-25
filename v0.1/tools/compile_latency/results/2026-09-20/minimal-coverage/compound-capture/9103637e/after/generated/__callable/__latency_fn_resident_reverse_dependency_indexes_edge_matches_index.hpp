#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ResidentDependencyEdgeSnapshotRow;
struct ResidentReverseDependencyIndexRow;
bool_t __latency_fn_resident_reverse_dependency_indexes_edge_matches_index(ResidentDependencyEdgeSnapshotRow edge, ResidentReverseDependencyIndexRow index);
}
