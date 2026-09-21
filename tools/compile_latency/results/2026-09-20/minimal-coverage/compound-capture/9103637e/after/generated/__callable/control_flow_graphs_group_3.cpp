#include <scpp/lang/php.hpp>
#include "__types/ControlFlowBlockRow.hpp"
#include "__types/ControlFlowEdgeRow.hpp"
#include "__types/ControlFlowGraphArtifact.hpp"
#include "__callable/__latency_fn_control_flow_graphs_tsv.hpp"
#include "__callable/__latency_fn_control_flow_graphs_tsv_body.hpp"
#include "__callable/__latency_fn_control_flow_graphs_tsv_body.hpp"
namespace scpp { extern const int __latency_lines_control_flow_graphs[]; }
namespace scpp {
string_t __latency_fn_control_flow_graphs_tsv(shared_p<ControlFlowGraphArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("control_flow_graphs::tsv", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_graphs.phs", __latency_lines_control_flow_graphs[36]);
	string_t text = required_cast<string_t>(string_t("section\trow_id\towner_symbol_id\tsource_unit_id\tkind_id\tstatement_kind_id\tstatement_node_id\tparent_or_from_block_id\tto_block_id\tfirst_source_row_id\tlast_source_row_id\tsource_row_id\tloop_depth\n"));
	text = (cast<string_t>(text) + cast<string_t>(__latency_fn_control_flow_graphs_tsv_body(artifact)));
	return text;
}

}

namespace scpp { extern const int __latency_lines_control_flow_graphs[]; }
namespace scpp {
string_t __latency_fn_control_flow_graphs_tsv_body(shared_p<ControlFlowGraphArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("control_flow_graphs::tsv_body", "/tmp/scpp-edit-latency-20260919/app/compile/control_flow/control_flow_graphs.phs", __latency_lines_control_flow_graphs[37]);
	string_t text = required_cast<string_t>(string_t(""));
	auto __latency_local_0 = artifact->blocks;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		text = (cast<string_t>(text) + string_t("block\t") + cast<string_t>(cast<int_t<>>(row->block_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->owner_symbol_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->source_unit_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->block_kind_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->statement_kind_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->statement_node_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->parent_block_id)) + string_t("\t0\t") + cast<string_t>(cast<int_t<>>(row->first_source_row_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->last_source_row_id)) + string_t("\t0\t") + cast<string_t>(cast<int_t<>>(row->loop_depth)) + string_t("\n"));
	}
	auto __latency_local_2 = artifact->edges;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto row = __latency_local_3.value_copy();
		text = (cast<string_t>(text) + string_t("edge\t") + cast<string_t>(cast<int_t<>>(row->edge_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->owner_symbol_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->source_unit_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->edge_kind_id)) + string_t("\t0\t0\t") + cast<string_t>(cast<int_t<>>(row->from_block_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->to_block_id)) + string_t("\t0\t0\t") + cast<string_t>(cast<int_t<>>(row->source_row_id)) + string_t("\t0\n"));
	}
	return text;
}

}
