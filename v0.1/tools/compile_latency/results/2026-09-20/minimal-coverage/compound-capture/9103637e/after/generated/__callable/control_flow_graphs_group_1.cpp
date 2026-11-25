#include <scpp/lang/php.hpp>
#include "__types/ControlFlowBlockRow.hpp"
#include "__types/ControlFlowEdgeRow.hpp"
#include "__types/ControlFlowGraphArtifact.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_entry_to_condition_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_condition_true_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_condition_false_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_then_to_merge_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_else_to_merge_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_merge_to_exit_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_loop_body_to_back_edge_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_loop_back_edge_to_condition_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_loop_exit_to_function_exit_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_for_init_to_condition_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_for_body_to_update_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_graphs_edge_kind_for_update_to_back_edge_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_control_flow_graphs_empty_artifact.hpp"
#include "__callable/__latency_fn_control_flow_graphs_append_block.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_control_flow_graphs_append_edge.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_control_flow_graphs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_control_flow_graphs_edge_kind_entry_to_condition_id() {
	SCPP_CALL_DEPTH_GUARD("control_flow_graphs::edge_kind_entry_to_condition_id", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_graphs.phs", __latency_lines_control_flow_graphs[16]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_control_flow_graphs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_control_flow_graphs_edge_kind_condition_true_id() {
	SCPP_CALL_DEPTH_GUARD("control_flow_graphs::edge_kind_condition_true_id", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_graphs.phs", __latency_lines_control_flow_graphs[17]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_control_flow_graphs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_control_flow_graphs_edge_kind_condition_false_id() {
	SCPP_CALL_DEPTH_GUARD("control_flow_graphs::edge_kind_condition_false_id", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_graphs.phs", __latency_lines_control_flow_graphs[18]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_control_flow_graphs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_control_flow_graphs_edge_kind_then_to_merge_id() {
	SCPP_CALL_DEPTH_GUARD("control_flow_graphs::edge_kind_then_to_merge_id", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_graphs.phs", __latency_lines_control_flow_graphs[19]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_control_flow_graphs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_control_flow_graphs_edge_kind_else_to_merge_id() {
	SCPP_CALL_DEPTH_GUARD("control_flow_graphs::edge_kind_else_to_merge_id", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_graphs.phs", __latency_lines_control_flow_graphs[20]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5));
}

}

namespace scpp { extern const int __latency_lines_control_flow_graphs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_control_flow_graphs_edge_kind_merge_to_exit_id() {
	SCPP_CALL_DEPTH_GUARD("control_flow_graphs::edge_kind_merge_to_exit_id", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_graphs.phs", __latency_lines_control_flow_graphs[21]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(6));
}

}

namespace scpp { extern const int __latency_lines_control_flow_graphs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_control_flow_graphs_edge_kind_loop_body_to_back_edge_id() {
	SCPP_CALL_DEPTH_GUARD("control_flow_graphs::edge_kind_loop_body_to_back_edge_id", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_graphs.phs", __latency_lines_control_flow_graphs[22]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(7));
}

}

namespace scpp { extern const int __latency_lines_control_flow_graphs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_control_flow_graphs_edge_kind_loop_back_edge_to_condition_id() {
	SCPP_CALL_DEPTH_GUARD("control_flow_graphs::edge_kind_loop_back_edge_to_condition_id", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_graphs.phs", __latency_lines_control_flow_graphs[23]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(8));
}

}

namespace scpp { extern const int __latency_lines_control_flow_graphs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_control_flow_graphs_edge_kind_loop_exit_to_function_exit_id() {
	SCPP_CALL_DEPTH_GUARD("control_flow_graphs::edge_kind_loop_exit_to_function_exit_id", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_graphs.phs", __latency_lines_control_flow_graphs[24]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(9));
}

}

namespace scpp { extern const int __latency_lines_control_flow_graphs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_control_flow_graphs_edge_kind_for_init_to_condition_id() {
	SCPP_CALL_DEPTH_GUARD("control_flow_graphs::edge_kind_for_init_to_condition_id", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_graphs.phs", __latency_lines_control_flow_graphs[25]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(10));
}

}

namespace scpp { extern const int __latency_lines_control_flow_graphs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_control_flow_graphs_edge_kind_for_body_to_update_id() {
	SCPP_CALL_DEPTH_GUARD("control_flow_graphs::edge_kind_for_body_to_update_id", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_graphs.phs", __latency_lines_control_flow_graphs[26]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(11));
}

}

namespace scpp { extern const int __latency_lines_control_flow_graphs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_control_flow_graphs_edge_kind_for_update_to_back_edge_id() {
	SCPP_CALL_DEPTH_GUARD("control_flow_graphs::edge_kind_for_update_to_back_edge_id", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_graphs.phs", __latency_lines_control_flow_graphs[27]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(12));
}

}

namespace scpp { extern const int __latency_lines_control_flow_graphs[]; }
namespace scpp {
shared_p<ControlFlowGraphArtifact> __latency_fn_control_flow_graphs_empty_artifact(int_t<std::uint32_t> sourceUnitId, int_t<std::uint32_t> symbolId) {
	SCPP_CALL_DEPTH_GUARD("control_flow_graphs::empty_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_graphs.phs", __latency_lines_control_flow_graphs[28]);
	shared_p<ControlFlowGraphArtifact> artifact = create<ControlFlowGraphArtifact>();
	artifact->owner_source_unit_id = sourceUnitId;
	artifact->owner_symbol_id = symbolId;
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_control_flow_graphs[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_control_flow_graphs_append_block(shared_p<ControlFlowGraphArtifact>& artifact, int_t<std::uint16_t> blockKindId, int_t<std::uint16_t> statementKindId, int_t<std::uint32_t> statementNodeId, int_t<std::uint32_t> parentBlockId, int_t<std::uint32_t> firstSourceRowId, int_t<std::uint32_t> lastSourceRowId, int_t<std::uint16_t> loopDepth) {
	SCPP_CALL_DEPTH_GUARD("control_flow_graphs::append_block", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_graphs.phs", __latency_lines_control_flow_graphs[29]);
	ControlFlowBlockRow row = ControlFlowBlockRow{};
	row->block_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->blocks));
	row->owner_symbol_id = artifact->owner_symbol_id;
	row->source_unit_id = artifact->owner_source_unit_id;
	row->statement_node_id = statementNodeId;
	row->block_kind_id = blockKindId;
	row->statement_kind_id = statementKindId;
	row->parent_block_id = parentBlockId;
	row->first_source_row_id = firstSourceRowId;
	row->last_source_row_id = lastSourceRowId;
	row->loop_depth = loopDepth;
	(void) artifact->blocks.append(row);
	artifact->block_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->blocks));
	return row->block_id;
}

}

namespace scpp { extern const int __latency_lines_control_flow_graphs[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_control_flow_graphs_append_edge(shared_p<ControlFlowGraphArtifact>& artifact, int_t<std::uint32_t> fromBlockId, int_t<std::uint32_t> toBlockId, int_t<std::uint16_t> edgeKindId, int_t<std::uint32_t> sourceRowId) {
	SCPP_CALL_DEPTH_GUARD("control_flow_graphs::append_edge", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_graphs.phs", __latency_lines_control_flow_graphs[30]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(fromBlockId), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(toBlockId), static_cast<int_t<> >(0))))) {
		return __latency_fn_structure_row_ids_none_id();
	}
	ControlFlowEdgeRow row = ControlFlowEdgeRow{};
	row->edge_id = __latency_fn_structure_row_ids_next_dense_id(php::count(artifact->edges));
	row->owner_symbol_id = artifact->owner_symbol_id;
	row->source_unit_id = artifact->owner_source_unit_id;
	row->from_block_id = fromBlockId;
	row->to_block_id = toBlockId;
	row->edge_kind_id = edgeKindId;
	row->source_row_id = sourceRowId;
	(void) artifact->edges.append(row);
	artifact->edge_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(artifact->edges));
	return row->edge_id;
}

}
