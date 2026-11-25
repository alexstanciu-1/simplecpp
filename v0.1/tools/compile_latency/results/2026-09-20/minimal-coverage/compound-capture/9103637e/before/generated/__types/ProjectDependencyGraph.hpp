#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/ProjectDependencyGraphEdgeRow.hpp"
#include "__types/ProjectDependencyGraphNodeRow.hpp"
#include "__types/ProjectDirtyFanoutRow.hpp"
#include "__types/ProjectDirtyReuseProjectionRow.hpp"
namespace scpp {
struct ProjectDependencyGraph {
	ProjectDependencyGraph* operator->() { return this; }
	const ProjectDependencyGraph* operator->() const { return this; }
	int_t<std::uint16_t> artifact_kind_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> schema_version = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> source_model_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> graph_model_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint16_t> resolution_status_id = cast<int_t<std::uint16_t>>(static_cast<int_t<> >(1));
	int_t<std::uint32_t> node_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> edge_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> dirty_projection_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	int_t<std::uint32_t> dirty_fanout_count = cast<int_t<std::uint32_t>>(static_cast<int_t<> >(0));
	vector_t<ProjectDependencyGraphNodeRow> nodes = vector_t<ProjectDependencyGraphNodeRow>{};
	vector_t<ProjectDependencyGraphEdgeRow> edges = vector_t<ProjectDependencyGraphEdgeRow>{};
	vector_t<ProjectDirtyReuseProjectionRow> dirty_projections = vector_t<ProjectDirtyReuseProjectionRow>{};
	vector_t<ProjectDirtyFanoutRow> dirty_fanouts = vector_t<ProjectDirtyFanoutRow>{};
};
}
