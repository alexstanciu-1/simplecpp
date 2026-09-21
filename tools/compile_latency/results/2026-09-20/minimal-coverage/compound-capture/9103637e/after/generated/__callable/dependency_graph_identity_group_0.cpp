#include <scpp/lang/php.hpp>
#include "__types/ProjectDependencyGraphEdgeRow.hpp"
#include "__types/ProjectDependencyGraphNodeRow.hpp"
#include "__types/ProjectDirtyFanoutRow.hpp"
#include "__types/ProjectDirtyReuseProjectionRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/dependency_graph_identity.hpp"
#include "__callable/__latency_fn_dependency_graph_identity_node_debug_string.hpp"
#include "__callable/__latency_fn_project_dependency_graph_node_key.hpp"
#include "__callable/__latency_fn_dependency_graph_identity_edge_debug_string.hpp"
#include "__callable/__latency_fn_project_dependency_graph_edge_kind_name.hpp"
#include "__callable/__latency_fn_project_dependency_graph_node_key.hpp"
#include "__callable/__latency_fn_dependency_graph_identity_dirty_projection_debug_string.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_status_name.hpp"
#include "__callable/__latency_fn_project_dependency_graph_symbol_key_for_id.hpp"
#include "__callable/__latency_fn_dependency_graph_identity_dirty_fanout_debug_string.hpp"
#include "__callable/__latency_fn_project_dependency_graph_dirty_status_name.hpp"
#include "__callable/__latency_fn_project_dependency_graph_symbol_key_for_id.hpp"
#include "__callable/__latency_fn_dependency_graph_identity_node_equals.hpp"
#include "__callable/__latency_fn_dependency_graph_identity_edge_equals.hpp"
#include "__callable/__latency_fn_dependency_graph_identity_node_debug_string.hpp"
#include "__callable/__latency_fn_dependency_graph_identity_stable_hash_node.hpp"
#include "__callable/__latency_fn_dependency_graph_identity_edge_debug_string.hpp"
#include "__callable/__latency_fn_dependency_graph_identity_stable_hash_edge.hpp"
#include "__callable/__latency_fn_dependency_graph_identity_dirty_projection_debug_string.hpp"
#include "__callable/__latency_fn_dependency_graph_identity_stable_hash_projection.hpp"
#include "__callable/__latency_fn_dependency_graph_identity_dirty_fanout_debug_string.hpp"
#include "__callable/__latency_fn_dependency_graph_identity_stable_hash_fanout.hpp"
namespace scpp { extern const int __latency_lines_dependency_graph_identity[]; }
namespace scpp {
bool_t dependency_graph_identity::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == dependency_graph_identity::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_dependency_graph_identity[]; }
namespace scpp {
string_t __latency_fn_dependency_graph_identity_node_debug_string(ProjectDependencyGraphNodeRow row, shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("dependency_graph_identity::node_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/model/dependency_graph_identity.phs", __latency_lines_dependency_graph_identity[0]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->node_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	return __latency_fn_project_dependency_graph_node_key(symbols, row->source_unit_id, row->symbol_id);
}

}

namespace scpp { extern const int __latency_lines_dependency_graph_identity[]; }
namespace scpp {
string_t __latency_fn_dependency_graph_identity_edge_debug_string(ProjectDependencyGraphEdgeRow row, shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("dependency_graph_identity::edge_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/model/dependency_graph_identity.phs", __latency_lines_dependency_graph_identity[1]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->edge_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	string_t fromKey = required_cast<string_t>(__latency_fn_project_dependency_graph_node_key(symbols, row->from_source_unit_id, row->from_symbol_id));
	string_t toKey = required_cast<string_t>(__latency_fn_project_dependency_graph_node_key(symbols, row->to_source_unit_id, row->to_symbol_id));
	return (string_t("dependency_edge:") + cast<string_t>(__latency_fn_project_dependency_graph_edge_kind_name(row->edge_kind_id)) + string_t(":") + cast<string_t>(fromKey) + string_t("->") + cast<string_t>(toKey) + string_t("#reference:") + cast<string_t>(cast<int_t<>>(row->reference_id)) + string_t("#contract:") + cast<string_t>(cast<int_t<>>(row->callable_contract_id)));
}

}

namespace scpp { extern const int __latency_lines_dependency_graph_identity[]; }
namespace scpp {
string_t __latency_fn_dependency_graph_identity_dirty_projection_debug_string(ProjectDirtyReuseProjectionRow row, shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("dependency_graph_identity::dirty_projection_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/model/dependency_graph_identity.phs", __latency_lines_dependency_graph_identity[2]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->projection_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	return (string_t("dirty_projection:") + cast<string_t>(__latency_fn_project_dependency_graph_symbol_key_for_id(symbols, row->symbol_id)) + string_t(":") + cast<string_t>(__latency_fn_project_dependency_graph_dirty_status_name(row->status_id)));
}

}

namespace scpp { extern const int __latency_lines_dependency_graph_identity[]; }
namespace scpp {
string_t __latency_fn_dependency_graph_identity_dirty_fanout_debug_string(ProjectDirtyFanoutRow row, shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("dependency_graph_identity::dirty_fanout_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/model/dependency_graph_identity.phs", __latency_lines_dependency_graph_identity[3]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->fanout_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	return (string_t("dirty_fanout:") + cast<string_t>(__latency_fn_project_dependency_graph_symbol_key_for_id(symbols, row->symbol_id)) + string_t("->") + cast<string_t>(__latency_fn_project_dependency_graph_symbol_key_for_id(symbols, row->dependent_symbol_id)) + string_t(":") + cast<string_t>(__latency_fn_project_dependency_graph_dirty_status_name(row->status_id)));
}

}

namespace scpp { extern const int __latency_lines_dependency_graph_identity[]; }
namespace scpp {
bool_t __latency_fn_dependency_graph_identity_node_equals(ProjectDependencyGraphNodeRow left, ProjectDependencyGraphNodeRow right) {
	SCPP_CALL_DEPTH_GUARD("dependency_graph_identity::node_equals", "/tmp/scpp-edit-latency-20260919/app/compile/model/dependency_graph_identity.phs", __latency_lines_dependency_graph_identity[4]);
	if (static_cast<bool>(((cast<int_t<>>(left->node_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(right->node_id) > static_cast<int_t<> >(0))))) {
		return bool_t(php::identical(cast<int_t<>>(left->node_id), cast<int_t<>>(right->node_id)));
	}
	return ((php::identical(cast<int_t<>>(left->source_unit_id), cast<int_t<>>(right->source_unit_id)) && php::identical(cast<int_t<>>(left->symbol_id), cast<int_t<>>(right->symbol_id))) && php::identical(cast<int_t<>>(left->node_kind_id), cast<int_t<>>(right->node_kind_id)));
}

}

namespace scpp { extern const int __latency_lines_dependency_graph_identity[]; }
namespace scpp {
bool_t __latency_fn_dependency_graph_identity_edge_equals(ProjectDependencyGraphEdgeRow left, ProjectDependencyGraphEdgeRow right) {
	SCPP_CALL_DEPTH_GUARD("dependency_graph_identity::edge_equals", "/tmp/scpp-edit-latency-20260919/app/compile/model/dependency_graph_identity.phs", __latency_lines_dependency_graph_identity[5]);
	if (static_cast<bool>(((cast<int_t<>>(left->edge_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(right->edge_id) > static_cast<int_t<> >(0))))) {
		return bool_t(php::identical(cast<int_t<>>(left->edge_id), cast<int_t<>>(right->edge_id)));
	}
	return ((((php::identical(cast<int_t<>>(left->from_symbol_id), cast<int_t<>>(right->from_symbol_id)) && php::identical(cast<int_t<>>(left->to_symbol_id), cast<int_t<>>(right->to_symbol_id))) && php::identical(cast<int_t<>>(left->reference_id), cast<int_t<>>(right->reference_id))) && php::identical(cast<int_t<>>(left->callable_contract_id), cast<int_t<>>(right->callable_contract_id))) && php::identical(cast<int_t<>>(left->edge_kind_id), cast<int_t<>>(right->edge_kind_id)));
}

}

namespace scpp { extern const int __latency_lines_dependency_graph_identity[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_dependency_graph_identity_stable_hash_node(ProjectDependencyGraphNodeRow row, shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("dependency_graph_identity::stable_hash_node", "/tmp/scpp-edit-latency-20260919/app/compile/model/dependency_graph_identity.phs", __latency_lines_dependency_graph_identity[6]);
	string_t identity = required_cast<string_t>((string_t("dependency_node:v2:") + cast<string_t>(__latency_fn_dependency_graph_identity_node_debug_string(row, symbols)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->node_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_dependency_graph_identity[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_dependency_graph_identity_stable_hash_edge(ProjectDependencyGraphEdgeRow row, shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("dependency_graph_identity::stable_hash_edge", "/tmp/scpp-edit-latency-20260919/app/compile/model/dependency_graph_identity.phs", __latency_lines_dependency_graph_identity[7]);
	string_t identity = required_cast<string_t>((string_t("dependency_edge:v2:") + cast<string_t>(__latency_fn_dependency_graph_identity_edge_debug_string(row, symbols)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_dependency_graph_identity[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_dependency_graph_identity_stable_hash_projection(ProjectDirtyReuseProjectionRow row, shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("dependency_graph_identity::stable_hash_projection", "/tmp/scpp-edit-latency-20260919/app/compile/model/dependency_graph_identity.phs", __latency_lines_dependency_graph_identity[8]);
	string_t identity = required_cast<string_t>((string_t("dirty_projection:v2:") + cast<string_t>(__latency_fn_dependency_graph_identity_dirty_projection_debug_string(row, symbols)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->recompute_target_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->change_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->reuse_scope_id))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_dependency_graph_identity[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_dependency_graph_identity_stable_hash_fanout(ProjectDirtyFanoutRow row, shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("dependency_graph_identity::stable_hash_fanout", "/tmp/scpp-edit-latency-20260919/app/compile/model/dependency_graph_identity.phs", __latency_lines_dependency_graph_identity[9]);
	string_t identity = required_cast<string_t>((string_t("dirty_fanout:v2:") + cast<string_t>(__latency_fn_dependency_graph_identity_dirty_fanout_debug_string(row, symbols)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->recompute_target_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->change_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->graph_edge_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->reuse_scope_id))));
	return php::stable_hash_string_u64(identity);
}

}
