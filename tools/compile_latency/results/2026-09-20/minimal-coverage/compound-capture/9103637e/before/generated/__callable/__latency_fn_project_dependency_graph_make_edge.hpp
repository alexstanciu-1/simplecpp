#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ProjectDependencyGraphEdgeRow;
ProjectDependencyGraphEdgeRow __latency_fn_project_dependency_graph_make_edge(int_t<std::uint32_t> edgeId, int_t<std::uint32_t> fromSourceUnitId, int_t<std::uint32_t> fromSymbolId, int_t<std::uint32_t> toSourceUnitId, int_t<std::uint32_t> toSymbolId, int_t<std::uint32_t> referenceId, int_t<std::uint32_t> callableContractId, int_t<std::uint16_t> edgeKindId, int_t<std::uint16_t> statusId);
}
