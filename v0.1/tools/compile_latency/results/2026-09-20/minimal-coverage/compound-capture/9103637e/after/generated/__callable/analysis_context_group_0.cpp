#include <scpp/lang/php.hpp>
#include "__types/AnalysisContext.hpp"
#include "__types/AnalysisEntryContextRow.hpp"
#include "__types/ProjectDependencyGraph.hpp"
#include "__types/ProjectDependencyGraphNodeRow.hpp"
#include "__types/analysis_context.hpp"
#include "__callable/__latency_fn_analysis_context_artifact_kind_analysis_context_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_analysis_context_source_model_project_rows_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_analysis_context_context_model_per_entry_rows_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_analysis_context_status_analyzer_context_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_analysis_context_entry_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_analysis_context_downstream_status_ready_for_lowering_plan_rows_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_analysis_context_downstream_status_blocked_until_readiness_rows_id.hpp"
#include "__callable/__latency_fn_analysis_context_downstream_status_ready_for_lowering_plan_rows_id.hpp"
#include "__callable/__latency_fn_analysis_context_blocked_downstream_table_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_analysis_context_artifact_kind_analysis_context_id.hpp"
#include "__callable/__latency_fn_analysis_context_blocked_downstream_table_count.hpp"
#include "__callable/__latency_fn_analysis_context_context_model_per_entry_rows_id.hpp"
#include "__callable/__latency_fn_analysis_context_new_context.hpp"
#include "__callable/__latency_fn_analysis_context_reserve_context.hpp"
#include "__callable/__latency_fn_analysis_context_source_model_project_rows_id.hpp"
#include "__callable/__latency_fn_analysis_context_status_analyzer_context_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_analysis_context_reserve_context.hpp"
#include "__callable/__latency_fn_analysis_context_entry_status_name.hpp"
#include "__callable/__latency_fn_analysis_context_entry_status_ready_id.hpp"
#include "__callable/__latency_fn_analysis_context_downstream_status_name.hpp"
#include "__callable/__latency_fn_analysis_context_downstream_status_ready_for_lowering_plan_rows_id.hpp"
#include "__callable/__latency_fn_analysis_context_graph_symbol_node_id.hpp"
#include "__callable/__latency_fn_project_dependency_graph_node_kind_symbol_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
namespace scpp { extern const int __latency_lines_analysis_context[]; }
namespace scpp {
bool_t analysis_context::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == analysis_context::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_analysis_context[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_analysis_context_artifact_kind_analysis_context_id() {
	SCPP_CALL_DEPTH_GUARD("analysis_context::artifact_kind_analysis_context_id", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context.phs", __latency_lines_analysis_context[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_analysis_context[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_analysis_context_source_model_project_rows_id() {
	SCPP_CALL_DEPTH_GUARD("analysis_context::source_model_project_rows_id", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context.phs", __latency_lines_analysis_context[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_analysis_context[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_analysis_context_context_model_per_entry_rows_id() {
	SCPP_CALL_DEPTH_GUARD("analysis_context::context_model_per_entry_rows_id", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context.phs", __latency_lines_analysis_context[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_analysis_context[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_analysis_context_status_analyzer_context_ready_id() {
	SCPP_CALL_DEPTH_GUARD("analysis_context::status_analyzer_context_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context.phs", __latency_lines_analysis_context[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_analysis_context[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_analysis_context_entry_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("analysis_context::entry_status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context.phs", __latency_lines_analysis_context[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_analysis_context[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_analysis_context_downstream_status_ready_for_lowering_plan_rows_id() {
	SCPP_CALL_DEPTH_GUARD("analysis_context::downstream_status_ready_for_lowering_plan_rows_id", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context.phs", __latency_lines_analysis_context[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_analysis_context[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_analysis_context_downstream_status_blocked_until_readiness_rows_id() {
	SCPP_CALL_DEPTH_GUARD("analysis_context::downstream_status_blocked_until_readiness_rows_id", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context.phs", __latency_lines_analysis_context[6]);
	return __latency_fn_analysis_context_downstream_status_ready_for_lowering_plan_rows_id();
}

}

namespace scpp { extern const int __latency_lines_analysis_context[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_analysis_context_blocked_downstream_table_count() {
	SCPP_CALL_DEPTH_GUARD("analysis_context::blocked_downstream_table_count", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context.phs", __latency_lines_analysis_context[7]);
	return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0));
}

}

namespace scpp { extern const int __latency_lines_analysis_context[]; }
namespace scpp {
shared_p<AnalysisContext> __latency_fn_analysis_context_new_context(int_t<> entryCapacity) {
	SCPP_CALL_DEPTH_GUARD("analysis_context::new_context", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context.phs", __latency_lines_analysis_context[8]);
	shared_p<AnalysisContext> context = create<AnalysisContext>();
	context->artifact_kind_id = __latency_fn_analysis_context_artifact_kind_analysis_context_id();
	context->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	context->source_model_id = __latency_fn_analysis_context_source_model_project_rows_id();
	context->context_model_id = __latency_fn_analysis_context_context_model_per_entry_rows_id();
	context->status_id = __latency_fn_analysis_context_status_analyzer_context_ready_id();
	context->blocked_downstream_table_count = __latency_fn_analysis_context_blocked_downstream_table_count();
	__latency_fn_analysis_context_reserve_context(context, entryCapacity);
	return context;
}

}

namespace scpp { extern const int __latency_lines_analysis_context[]; }
namespace scpp {
void __latency_fn_analysis_context_reserve_context(shared_p<AnalysisContext> context, int_t<> entryCapacity) {
	SCPP_CALL_DEPTH_GUARD("analysis_context::reserve_context", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context.phs", __latency_lines_analysis_context[9]);
	php::vector_reserve(context->rows, entryCapacity);
}

}

namespace scpp { extern const int __latency_lines_analysis_context[]; }
namespace scpp {
string_t __latency_fn_analysis_context_entry_status_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("analysis_context::entry_status_name", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context.phs", __latency_lines_analysis_context[10]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_analysis_context_entry_status_ready_id())))) {
		return string_t("ready");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_analysis_context[]; }
namespace scpp {
string_t __latency_fn_analysis_context_downstream_status_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("analysis_context::downstream_status_name", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context.phs", __latency_lines_analysis_context[11]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(id), cast<int_t<>>(__latency_fn_analysis_context_downstream_status_ready_for_lowering_plan_rows_id())))) {
		return string_t("ready_for_lowering_plan_rows");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_analysis_context[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_analysis_context_graph_symbol_node_id(ProjectDependencyGraph graph, int_t<std::uint32_t> symbolId) {
	SCPP_CALL_DEPTH_GUARD("analysis_context::graph_symbol_node_id", "/tmp/scpp-edit-latency-20260919/app/compile/artifacts/analysis_context.phs", __latency_lines_analysis_context[12]);
	auto __latency_local_0 = graph->nodes;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto node = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(node->symbol_id), cast<int_t<>>(symbolId)) && php::identical(cast<int_t<>>(node->node_kind_id), cast<int_t<>>(__latency_fn_project_dependency_graph_node_kind_symbol_id()))))) {
			return node->node_id;
		}
	}
	return __latency_fn_structure_row_ids_none_id();
}

}
