#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ResidentSourceUnitFrontendStateRow.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__types/TokenStream.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__types/resident_source_unit_frontend_states.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_reuse_model_sidecar_debug_copy_enabled.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_resident_source_unit_frontend_reuse_model_sidecar_debug_blocked.hpp"
#include "__callable/__latency_fn_proof_metrics_key_resident_source_unit_frontend_reuse_model_sidecar_debug_copied.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_model_sidecar.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_model_by_sidecar_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_reuse_model_sidecar_debug_copy_enabled.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_reused_frontend_model_sidecar_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_state.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_state_kind_rebuilt_replacement_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_state_kind_reused_previous_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_segment_count.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_segment_reserved_bytes.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_segment_slack_bytes.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_row_from_built.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_row_from_built_counts.hpp"
#include "__callable/__latency_fn_token_tables_reserved_segment_bytes.hpp"
#include "__callable/__latency_fn_token_tables_segment_count.hpp"
#include "__callable/__latency_fn_token_tables_segment_slack_bytes.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_row_from_built_counts.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_built.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_state.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_debug_model_sidecar_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_row_from_built.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_built_state_from_counts.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_state.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_row_from_built_counts.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_built_from_counts_ref.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_append_built_state_from_counts.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_states_debug_model_sidecar_id.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
bool_t __latency_fn_resident_source_unit_frontend_states_reuse_model_sidecar_debug_copy_enabled() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::reuse_model_sidecar_debug_copy_enabled", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[12]);
	return bool_t(php::identical(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_REUSE_FULL_MODEL_SIDECAR_DEBUG_COPY")), string_t("1")));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_source_unit_frontend_states_reused_frontend_model_sidecar_id(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previous, ResidentSourceUnitFrontendStateRow previousState) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::reused_frontend_model_sidecar_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[13]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_resident_source_unit_frontend_states_reuse_model_sidecar_debug_copy_enabled()))) {
		shared_p<FrontendModel> previousModel = __latency_fn_resident_source_unit_frontend_states_model_by_sidecar_id(previous, previousState->frontend_model_sidecar_id);
		__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_resident_source_unit_frontend_reuse_model_sidecar_debug_copied(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
		return __latency_fn_resident_source_unit_frontend_states_append_model_sidecar(report, previousModel);
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_proof_metrics_key_resident_source_unit_frontend_reuse_model_sidecar_debug_blocked(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
ResidentSourceUnitFrontendStateRow __latency_fn_resident_source_unit_frontend_states_append_state(shared_p<CompilerProjectRunReport>& report, ResidentSourceUnitFrontendStateRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::append_state", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[14]);
	row->state_id = __latency_fn_structure_row_ids_next_dense_id(php::count(report->resident_source_unit_frontend_states));
	(void) report->resident_source_unit_frontend_states.append(row);
	report->resident_source_unit_frontend_state_count = __latency_fn_resident_source_unit_frontend_states_uint32_from_int(php::count(report->resident_source_unit_frontend_states));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->state_kind_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_states_state_kind_reused_previous_id())))) {
		report->resident_source_unit_frontend_state_reuse_previous_count = __latency_fn_resident_source_unit_frontend_states_uint32_from_int((cast<int_t<>>(report->resident_source_unit_frontend_state_reuse_previous_count) + static_cast<int_t<> >(1)));
	}
	else {
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->state_kind_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_states_state_kind_rebuilt_replacement_id())))) {
			report->resident_source_unit_frontend_state_rebuilt_replacement_count = __latency_fn_resident_source_unit_frontend_states_uint32_from_int((cast<int_t<>>(report->resident_source_unit_frontend_state_rebuilt_replacement_count) + static_cast<int_t<> >(1)));
		}
		else {
			report->resident_source_unit_frontend_state_built_current_count = __latency_fn_resident_source_unit_frontend_states_uint32_from_int((cast<int_t<>>(report->resident_source_unit_frontend_state_built_current_count) + static_cast<int_t<> >(1)));
		}
	}
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
void __latency_fn_resident_source_unit_frontend_states_append_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::append_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[15]);
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_resident_source_unit_frontend_states_uint32_from_int(rowCount), __latency_fn_resident_source_unit_frontend_states_uint32_from_int((rowCount * static_cast<int_t<> >(sizeof(ResidentSourceUnitFrontendStateRow)))), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
ResidentSourceUnitFrontendStateRow __latency_fn_resident_source_unit_frontend_states_row_from_built(SourceUnitTableRow sourceUnit, shared_p<TokenStream>& tokens, shared_p<FrontendModel>& model, int_t<std::uint32_t> sidecarId, int_t<std::uint16_t> stateKindId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::row_from_built", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[16]);
	return __latency_fn_resident_source_unit_frontend_states_row_from_built_counts(sourceUnit, cast<int_t<std::uint32_t>>(sidecarId), cast<int_t<std::uint16_t>>(stateKindId), tokens->token_count, __latency_fn_token_tables_segment_count(tokens), __latency_fn_token_tables_reserved_segment_bytes(tokens), __latency_fn_token_tables_segment_slack_bytes(tokens), model->node_count, __latency_fn_frontend_model_tables_node_segment_count(model), __latency_fn_frontend_model_tables_node_segment_reserved_bytes(model), __latency_fn_frontend_model_tables_node_segment_slack_bytes(model), model->declaration_count, model->statement_count, model->expression_count, model->parser_error_count);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
ResidentSourceUnitFrontendStateRow __latency_fn_resident_source_unit_frontend_states_row_from_built_counts(SourceUnitTableRow sourceUnit, int_t<std::uint32_t> sidecarId, int_t<std::uint16_t> stateKindId, int_t<std::uint32_t> tokenCount, int_t<std::uint32_t> tokenSegmentCount, int_t<std::uint32_t> tokenSegmentReservedBytes, int_t<std::uint32_t> tokenSegmentSlackBytes, int_t<std::uint32_t> frontendNodeCount, int_t<std::uint32_t> frontendNodeSegmentCount, int_t<std::uint32_t> frontendNodeSegmentReservedBytes, int_t<std::uint32_t> frontendNodeSegmentSlackBytes, int_t<std::uint32_t> declarationCount, int_t<std::uint32_t> statementCount, int_t<std::uint32_t> expressionCount, int_t<std::uint32_t> parserErrorCount) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::row_from_built_counts", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[17]);
	ResidentSourceUnitFrontendStateRow row = ResidentSourceUnitFrontendStateRow{};
	row->source_unit_id = sourceUnit->source_unit_id;
	row->source_unit_key_id = sourceUnit->source_unit_key_id;
	row->frontend_model_sidecar_id = sidecarId;
	row->token_count = tokenCount;
	row->token_segment_count = tokenSegmentCount;
	row->token_segment_reserved_bytes = tokenSegmentReservedBytes;
	row->token_segment_slack_bytes = tokenSegmentSlackBytes;
	row->frontend_node_count = frontendNodeCount;
	row->frontend_node_segment_count = frontendNodeSegmentCount;
	row->frontend_node_segment_reserved_bytes = frontendNodeSegmentReservedBytes;
	row->frontend_node_segment_slack_bytes = frontendNodeSegmentSlackBytes;
	row->declaration_count = declarationCount;
	row->statement_count = statementCount;
	row->expression_count = expressionCount;
	row->parser_error_count = parserErrorCount;
	row->source_status_id = sourceUnit->status_id;
	row->state_kind_id = stateKindId;
	row->status_id = __latency_fn_resident_source_unit_frontend_states_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_states_blocked_reason_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
ResidentSourceUnitFrontendStateRow __latency_fn_resident_source_unit_frontend_states_append_built(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, SourceUnitTableRow sourceUnit, shared_p<TokenStream>& tokens, shared_p<FrontendModel>& model, int_t<std::uint16_t> stateKindId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::append_built", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[18]);
	int_t<std::uint32_t> sidecarId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_source_unit_frontend_states_debug_model_sidecar_id(report, model));
	ResidentSourceUnitFrontendStateRow row = __latency_fn_resident_source_unit_frontend_states_row_from_built(sourceUnit, tokens, model, cast<int_t<std::uint32_t>>(sidecarId), cast<int_t<std::uint16_t>>(stateKindId));
	row->owner_run_id = ownerRunId;
	ResidentSourceUnitFrontendStateRow result = __latency_fn_resident_source_unit_frontend_states_append_state(report, row);
	__latency_fn_resident_source_unit_frontend_states_append_memory_estimate(report, static_cast<int_t<> >(1));
	return result;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
ResidentSourceUnitFrontendStateRow __latency_fn_resident_source_unit_frontend_states_append_built_state_from_counts(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, SourceUnitTableRow sourceUnit, int_t<std::uint32_t> sidecarId, int_t<std::uint16_t> stateKindId, int_t<std::uint32_t> tokenCount, int_t<std::uint32_t> tokenSegmentCount, int_t<std::uint32_t> tokenSegmentReservedBytes, int_t<std::uint32_t> tokenSegmentSlackBytes, int_t<std::uint32_t> frontendNodeCount, int_t<std::uint32_t> frontendNodeSegmentCount, int_t<std::uint32_t> frontendNodeSegmentReservedBytes, int_t<std::uint32_t> frontendNodeSegmentSlackBytes, int_t<std::uint32_t> declarationCount, int_t<std::uint32_t> statementCount, int_t<std::uint32_t> expressionCount, int_t<std::uint32_t> parserErrorCount) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::append_built_state_from_counts", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[19]);
	ResidentSourceUnitFrontendStateRow row = __latency_fn_resident_source_unit_frontend_states_row_from_built_counts(sourceUnit, cast<int_t<std::uint32_t>>(sidecarId), cast<int_t<std::uint16_t>>(stateKindId), cast<int_t<std::uint32_t>>(tokenCount), cast<int_t<std::uint32_t>>(tokenSegmentCount), cast<int_t<std::uint32_t>>(tokenSegmentReservedBytes), cast<int_t<std::uint32_t>>(tokenSegmentSlackBytes), cast<int_t<std::uint32_t>>(frontendNodeCount), cast<int_t<std::uint32_t>>(frontendNodeSegmentCount), cast<int_t<std::uint32_t>>(frontendNodeSegmentReservedBytes), cast<int_t<std::uint32_t>>(frontendNodeSegmentSlackBytes), cast<int_t<std::uint32_t>>(declarationCount), cast<int_t<std::uint32_t>>(statementCount), cast<int_t<std::uint32_t>>(expressionCount), cast<int_t<std::uint32_t>>(parserErrorCount));
	row->owner_run_id = ownerRunId;
	ResidentSourceUnitFrontendStateRow result = __latency_fn_resident_source_unit_frontend_states_append_state(report, row);
	__latency_fn_resident_source_unit_frontend_states_append_memory_estimate(report, static_cast<int_t<> >(1));
	return result;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_states[]; }
namespace scpp {
ResidentSourceUnitFrontendStateRow __latency_fn_resident_source_unit_frontend_states_append_built_from_counts_ref(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, SourceUnitTableRow sourceUnit, shared_p<FrontendModel>& model, int_t<std::uint16_t> stateKindId, int_t<std::uint32_t> tokenCount, int_t<std::uint32_t> tokenSegmentCount, int_t<std::uint32_t> tokenSegmentReservedBytes, int_t<std::uint32_t> tokenSegmentSlackBytes, int_t<std::uint32_t> frontendNodeCount, int_t<std::uint32_t> frontendNodeSegmentCount, int_t<std::uint32_t> frontendNodeSegmentReservedBytes, int_t<std::uint32_t> frontendNodeSegmentSlackBytes, int_t<std::uint32_t> declarationCount, int_t<std::uint32_t> statementCount, int_t<std::uint32_t> expressionCount, int_t<std::uint32_t> parserErrorCount) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_states::append_built_from_counts_ref", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_states.phs", __latency_lines_resident_source_unit_frontend_states[20]);
	int_t<std::uint32_t> sidecarId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_source_unit_frontend_states_debug_model_sidecar_id(report, model));
	return __latency_fn_resident_source_unit_frontend_states_append_built_state_from_counts(report, cast<int_t<std::uint32_t>>(ownerRunId), sourceUnit, cast<int_t<std::uint32_t>>(sidecarId), cast<int_t<std::uint16_t>>(stateKindId), cast<int_t<std::uint32_t>>(tokenCount), cast<int_t<std::uint32_t>>(tokenSegmentCount), cast<int_t<std::uint32_t>>(tokenSegmentReservedBytes), cast<int_t<std::uint32_t>>(tokenSegmentSlackBytes), cast<int_t<std::uint32_t>>(frontendNodeCount), cast<int_t<std::uint32_t>>(frontendNodeSegmentCount), cast<int_t<std::uint32_t>>(frontendNodeSegmentReservedBytes), cast<int_t<std::uint32_t>>(frontendNodeSegmentSlackBytes), cast<int_t<std::uint32_t>>(declarationCount), cast<int_t<std::uint32_t>>(statementCount), cast<int_t<std::uint32_t>>(expressionCount), cast<int_t<std::uint32_t>>(parserErrorCount));
}

}
