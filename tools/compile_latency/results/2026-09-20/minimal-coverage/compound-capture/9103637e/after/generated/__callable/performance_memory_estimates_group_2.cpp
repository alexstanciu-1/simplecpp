#include <scpp/lang/php.hpp>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendCallArgumentRow.hpp"
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__types/BackendLocalOperandRow.hpp"
#include "__types/BackendRequestAuthorizationRow.hpp"
#include "__types/CapabilityReadinessRow.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/ProjectDependencyGraphEdgeRow.hpp"
#include "__types/ProjectReferenceResolutionRow.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/TokenRow.hpp"
#include "__types/TypeRefRow.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_artifact_boundary_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_derived_text_boundary_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_lookup_cache_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_name.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_segment_capacity_slack_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_source_text_and_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_status_estimated_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_status_name.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_status_estimated_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_source_unit_owner_row_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_table_owner_row_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_source_text_and_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_source_unit_owner_row_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_token_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_token_segment_slack_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_frontend_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_frontend_node_segment_slack_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_symbol_reference_contract_dependency_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_project_symbol_sidecar_cache_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_type_capability_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_backend_request_rows_bytes.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_backend_request_sidecar_rows_bytes.hpp"
namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
string_t __latency_fn_performance_memory_estimates_policy_name(int_t<std::uint16_t> policyId) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::policy_name", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[27]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(policyId), cast<int_t<>>(__latency_fn_performance_memory_estimates_policy_source_text_and_rows_id())))) {
		return string_t("source_text_and_rows");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(policyId), cast<int_t<>>(__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id())))) {
		return string_t("compact_hot_rows");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(policyId), cast<int_t<>>(__latency_fn_performance_memory_estimates_policy_artifact_boundary_id())))) {
		return string_t("artifact_boundary");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(policyId), cast<int_t<>>(__latency_fn_performance_memory_estimates_policy_derived_text_boundary_id())))) {
		return string_t("derived_text_boundary");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(policyId), cast<int_t<>>(__latency_fn_performance_memory_estimates_policy_segment_capacity_slack_id())))) {
		return string_t("segment_capacity_slack");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(policyId), cast<int_t<>>(__latency_fn_performance_memory_estimates_policy_lookup_cache_id())))) {
		return string_t("lookup_cache");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
string_t __latency_fn_performance_memory_estimates_status_name(int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::status_name", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[28]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_performance_memory_estimates_status_estimated_id())))) {
		return string_t("estimated");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
PerformanceMemoryEstimateRow __latency_fn_performance_memory_estimates_row(int_t<std::uint32_t> estimateId, int_t<std::uint16_t> bucketId, int_t<std::uint32_t> rowCount, int_t<std::uint32_t> estimatedHotBytes, int_t<std::uint16_t> policyId) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::row", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[29]);
	PerformanceMemoryEstimateRow row = PerformanceMemoryEstimateRow{};
	row->estimate_id = estimateId;
	row->bucket_id = bucketId;
	row->row_count = rowCount;
	row->estimated_hot_bytes = estimatedHotBytes;
	row->compactness_policy_id = policyId;
	row->status_id = __latency_fn_performance_memory_estimates_status_estimated_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_source_unit_owner_row_bytes() {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::source_unit_owner_row_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[30]);
	return static_cast<int_t<> >(64);
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_table_owner_row_bytes() {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::table_owner_row_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[31]);
	return static_cast<int_t<> >(64);
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_source_text_and_rows_bytes(CompilerProjectRunRow row, int_t<> sourceRows) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::source_text_and_rows_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[32]);
	return (cast<int_t<>>(row->source_byte_count) + (sourceRows * __latency_fn_performance_memory_estimates_source_unit_owner_row_bytes()));
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_token_rows_bytes(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::token_rows_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[33]);
	return (cast<int_t<>>(row->token_count) * static_cast<int_t<> >(sizeof(TokenRow)));
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_token_segment_slack_bytes(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::token_segment_slack_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[34]);
	return cast<int_t<>>(row->token_segment_slack_bytes);
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_frontend_rows_bytes(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::frontend_rows_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[35]);
	return (cast<int_t<>>(row->frontend_node_count) * static_cast<int_t<> >(sizeof(FrontendNodeRow)));
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_frontend_node_segment_slack_bytes(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::frontend_node_segment_slack_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[36]);
	return cast<int_t<>>(row->frontend_node_segment_slack_bytes);
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_symbol_reference_contract_dependency_rows_bytes(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::symbol_reference_contract_dependency_rows_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[37]);
	return ((((cast<int_t<>>(row->symbol_count) * static_cast<int_t<> >(sizeof(ProjectSymbolIndexRow))) + (cast<int_t<>>(row->reference_count) * static_cast<int_t<> >(sizeof(ProjectReferenceResolutionRow)))) + (cast<int_t<>>(row->callable_contract_count) * static_cast<int_t<> >(sizeof(ProjectCallableContractRow)))) + (cast<int_t<>>(row->dependency_edge_count) * static_cast<int_t<> >(sizeof(ProjectDependencyGraphEdgeRow))));
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_project_symbol_sidecar_cache_bytes(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::project_symbol_sidecar_cache_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[38]);
	return (((cast<int_t<>>(row->project_symbol_sidecar_string_count) * static_cast<int_t<> >(32)) + (cast<int_t<>>(row->project_symbol_sidecar_string_byte_count) * static_cast<int_t<> >(2))) + (cast<int_t<>>(row->project_symbol_sidecar_lookup_entry_count) * static_cast<int_t<> >(48)));
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_type_capability_rows_bytes(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::type_capability_rows_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[39]);
	return ((cast<int_t<>>(row->type_ref_count) * static_cast<int_t<> >(sizeof(TypeRefRow))) + (cast<int_t<>>(row->capability_readiness_count) * static_cast<int_t<> >(sizeof(CapabilityReadinessRow))));
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_backend_request_rows_bytes(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::backend_request_rows_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[40]);
	return (cast<int_t<>>(row->backend_request_count) * static_cast<int_t<> >(sizeof(BackendRequestAuthorizationRow)));
}

}

namespace scpp { extern const int __latency_lines_performance_memory_estimates[]; }
namespace scpp {
int_t<> __latency_fn_performance_memory_estimates_backend_request_sidecar_rows_bytes(CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("performance_memory_estimates::backend_request_sidecar_rows_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/performance_memory_estimates.phs", __latency_lines_performance_memory_estimates[41]);
	return ((((cast<int_t<>>(row->backend_request_binary_operand_count) * static_cast<int_t<> >(sizeof(BackendBinaryOperandRow))) + (cast<int_t<>>(row->backend_request_local_operand_count) * static_cast<int_t<> >(sizeof(BackendLocalOperandRow)))) + (cast<int_t<>>(row->backend_request_call_argument_count) * static_cast<int_t<> >(sizeof(BackendCallArgumentRow)))) + (cast<int_t<>>(row->backend_request_control_flow_operand_count) * static_cast<int_t<> >(sizeof(BackendControlFlowOperandRow))));
}

}
