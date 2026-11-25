#include <scpp/lang/php.hpp>
#include "__types/AnalysisContext.hpp"
#include "__types/AnalysisEntryContextRow.hpp"
#include "__types/ProjectCallableContractArtifact.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/ProjectDependencyGraph.hpp"
#include "__types/ProjectDependencyGraphEdgeRow.hpp"
#include "__types/ProjectReferenceResolution.hpp"
#include "__types/ProjectReferenceResolutionRow.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__callable/__latency_fn_analysis_context_first_reference_id_for_symbol.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_analysis_context_reference_count_for_symbol.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_analysis_context_first_callable_contract_id_for_symbol.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_analysis_context_callable_contract_count_for_symbol.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_analysis_context_first_dependency_edge_id_for_symbol.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_analysis_context_dependency_edge_count_for_symbol.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_analysis_context_callable_contract_count_for_symbol.hpp"
#include "__callable/__latency_fn_analysis_context_dependency_edge_count_for_symbol.hpp"
#include "__callable/__latency_fn_analysis_context_downstream_status_ready_for_lowering_plan_rows_id.hpp"
#include "__callable/__latency_fn_analysis_context_entry_status_ready_id.hpp"
#include "__callable/__latency_fn_analysis_context_first_callable_contract_id_for_symbol.hpp"
#include "__callable/__latency_fn_analysis_context_first_dependency_edge_id_for_symbol.hpp"
#include "__callable/__latency_fn_analysis_context_first_reference_id_for_symbol.hpp"
#include "__callable/__latency_fn_analysis_context_graph_symbol_node_id.hpp"
#include "__callable/__latency_fn_analysis_context_reference_count_for_symbol.hpp"
#include "__callable/__latency_fn_analysis_context_row_from_entry_symbol.hpp"
#include "__callable/__latency_fn_analysis_context_append_entry.hpp"
#include "__callable/__latency_fn_analysis_context_row_from_entry_symbol.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_analysis_context_row_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
namespace scpp { extern const int __latency_lines_analysis_context[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_analysis_context_first_reference_id_for_symbol(shared_p<ProjectReferenceResolution> references, int_t<std::uint32_t> symbolId) {
	SCPP_CALL_DEPTH_GUARD("analysis_context::first_reference_id_for_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context.phs", __latency_lines_analysis_context[13]);
	auto __latency_local_0 = references->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto reference = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(reference->from_symbol_id), cast<int_t<>>(symbolId)))) {
			return reference->reference_id;
		}
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_analysis_context[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_analysis_context_reference_count_for_symbol(shared_p<ProjectReferenceResolution> references, int_t<std::uint32_t> symbolId) {
	SCPP_CALL_DEPTH_GUARD("analysis_context::reference_count_for_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context.phs", __latency_lines_analysis_context[14]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = references->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto reference = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(reference->from_symbol_id), cast<int_t<>>(symbolId)))) {
			count = (count + static_cast<int_t<> >(1));
		}
	}
	return __latency_fn_structure_row_ids_uint32_from_int(count);
}

}

namespace scpp { extern const int __latency_lines_analysis_context[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_analysis_context_first_callable_contract_id_for_symbol(shared_p<ProjectCallableContractArtifact> contracts, int_t<std::uint32_t> symbolId) {
	SCPP_CALL_DEPTH_GUARD("analysis_context::first_callable_contract_id_for_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context.phs", __latency_lines_analysis_context[15]);
	auto __latency_local_0 = contracts->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto contract = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(contract->from_symbol_id), cast<int_t<>>(symbolId)))) {
			return contract->contract_id;
		}
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_analysis_context[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_analysis_context_callable_contract_count_for_symbol(shared_p<ProjectCallableContractArtifact> contracts, int_t<std::uint32_t> symbolId) {
	SCPP_CALL_DEPTH_GUARD("analysis_context::callable_contract_count_for_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context.phs", __latency_lines_analysis_context[16]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = contracts->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto contract = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(contract->from_symbol_id), cast<int_t<>>(symbolId)))) {
			count = (count + static_cast<int_t<> >(1));
		}
	}
	return __latency_fn_structure_row_ids_uint32_from_int(count);
}

}

namespace scpp { extern const int __latency_lines_analysis_context[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_analysis_context_first_dependency_edge_id_for_symbol(ProjectDependencyGraph graph, int_t<std::uint32_t> symbolId) {
	SCPP_CALL_DEPTH_GUARD("analysis_context::first_dependency_edge_id_for_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context.phs", __latency_lines_analysis_context[17]);
	auto __latency_local_0 = graph->edges;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto edge = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(edge->from_symbol_id), cast<int_t<>>(symbolId)))) {
			return edge->edge_id;
		}
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_analysis_context[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_analysis_context_dependency_edge_count_for_symbol(ProjectDependencyGraph graph, int_t<std::uint32_t> symbolId) {
	SCPP_CALL_DEPTH_GUARD("analysis_context::dependency_edge_count_for_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context.phs", __latency_lines_analysis_context[18]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = graph->edges;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto edge = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(edge->from_symbol_id), cast<int_t<>>(symbolId)))) {
			count = (count + static_cast<int_t<> >(1));
		}
	}
	return __latency_fn_structure_row_ids_uint32_from_int(count);
}

}

namespace scpp { extern const int __latency_lines_analysis_context[]; }
namespace scpp {
AnalysisEntryContextRow __latency_fn_analysis_context_row_from_entry_symbol(ProjectSymbolIndexRow entrySymbol, shared_p<ProjectReferenceResolution> references, shared_p<ProjectCallableContractArtifact> contracts, ProjectDependencyGraph graph) {
	SCPP_CALL_DEPTH_GUARD("analysis_context::row_from_entry_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context.phs", __latency_lines_analysis_context[19]);
	AnalysisEntryContextRow row = AnalysisEntryContextRow{};
	row->source_unit_id = entrySymbol->source_unit_id;
	row->symbol_id = entrySymbol->symbol_id;
	row->declaration_node_id = entrySymbol->source_row_id;
	row->graph_symbol_node_id = __latency_fn_analysis_context_graph_symbol_node_id(graph, entrySymbol->symbol_id);
	row->first_reference_id = __latency_fn_analysis_context_first_reference_id_for_symbol(references, entrySymbol->symbol_id);
	row->reference_count = __latency_fn_analysis_context_reference_count_for_symbol(references, entrySymbol->symbol_id);
	row->first_callable_contract_id = __latency_fn_analysis_context_first_callable_contract_id_for_symbol(contracts, entrySymbol->symbol_id);
	row->callable_contract_count = __latency_fn_analysis_context_callable_contract_count_for_symbol(contracts, entrySymbol->symbol_id);
	row->first_dependency_edge_id = __latency_fn_analysis_context_first_dependency_edge_id_for_symbol(graph, entrySymbol->symbol_id);
	row->dependency_edge_count = __latency_fn_analysis_context_dependency_edge_count_for_symbol(graph, entrySymbol->symbol_id);
	row->status_id = __latency_fn_analysis_context_entry_status_ready_id();
	row->downstream_table_status_id = __latency_fn_analysis_context_downstream_status_ready_for_lowering_plan_rows_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_analysis_context[]; }
namespace scpp {
AnalysisEntryContextRow __latency_fn_analysis_context_append_entry(shared_p<AnalysisContext> context, ProjectSymbolIndexRow entrySymbol, shared_p<ProjectReferenceResolution> references, shared_p<ProjectCallableContractArtifact> contracts, ProjectDependencyGraph graph) {
	SCPP_CALL_DEPTH_GUARD("analysis_context::append_entry", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context.phs", __latency_lines_analysis_context[20]);
	AnalysisEntryContextRow row = __latency_fn_analysis_context_row_from_entry_symbol(entrySymbol, references, contracts, graph);
	row->context_id = __latency_fn_structure_row_ids_next_dense_id(php::count(context->rows));
	(void) context->rows.append(row);
	context->entry_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(context->rows));
	return row;
}

}

namespace scpp { extern const int __latency_lines_analysis_context[]; }
namespace scpp {
AnalysisEntryContextRow __latency_fn_analysis_context_row_by_id(shared_p<AnalysisContext> context, int_t<std::uint32_t> contextId) {
	SCPP_CALL_DEPTH_GUARD("analysis_context::row_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context.phs", __latency_lines_analysis_context[21]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(contextId, cast<int_t<>>(context->entry_count))))) {
		AnalysisEntryContextRow row = context->rows[__latency_fn_structure_row_ids_dense_index(contextId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->context_id), cast<int_t<>>(contextId)))) {
			return row;
		}
	}
	auto __latency_local_0 = context->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->context_id), cast<int_t<>>(contextId)))) {
			return row;
		}
	}
	AnalysisEntryContextRow empty = AnalysisEntryContextRow{};
	return empty;
}

}
