#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/ResidentSourceUnitFrontendPayloadTableRow.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__types/TokenStream.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_source_unit_frontend_worker_shadow_handoff_skipped.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_source_unit_frontend_real_worker_publication_enabled.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_source_unit_frontend_worker_locked_publication_enabled.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_source_unit_frontend_worker_try_lock_publication_enabled.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_source_unit_frontend_worker_publish_batch_cap.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_source_unit_frontend_worker_local_symbol_publication_enabled.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_source_unit_frontend_worker_locked_publication_enabled.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_source_unit_frontend_worker_retain_token_result_enabled.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_result_full_model_debug_retention_enabled.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_id_for_source_unit.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_segment_count.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_segment_reserved_bytes.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_segment_slack_bytes.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_row_from_built.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_row_from_built_counts.hpp"
#include "__callable/__latency_fn_token_tables_reserved_segment_bytes.hpp"
#include "__callable/__latency_fn_token_tables_segment_count.hpp"
#include "__callable/__latency_fn_token_tables_segment_slack_bytes.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_owner_kind_worker_local_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_row_from_built_counts.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_id_for_source_unit.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_record_built_handle_metrics.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_record_built_handle_metrics_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_row_from_built.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_record_built_handle_metrics_from_counts.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_record_built_handle_metrics_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_row_from_built_counts.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
bool_t __latency_fn_resident_source_unit_frontend_payload_tables_source_unit_frontend_worker_shadow_handoff_skipped() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::source_unit_frontend_worker_shadow_handoff_skipped", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[33]);
	return bool_t(php::identical(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_SOURCE_UNIT_FRONTEND_WORKER_SHADOW_SKIP")), string_t("1")));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
bool_t __latency_fn_resident_source_unit_frontend_payload_tables_source_unit_frontend_real_worker_publication_enabled() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::source_unit_frontend_real_worker_publication_enabled", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[34]);
	return bool_t(php::identical(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_SOURCE_UNIT_FRONTEND_REAL_WORKER_PUBLICATION")), string_t("1")));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
bool_t __latency_fn_resident_source_unit_frontend_payload_tables_source_unit_frontend_worker_locked_publication_enabled() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::source_unit_frontend_worker_locked_publication_enabled", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[35]);
	return bool_t(php::identical(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_SOURCE_UNIT_FRONTEND_WORKER_LOCKED_PUBLICATION")), string_t("1")));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
bool_t __latency_fn_resident_source_unit_frontend_payload_tables_source_unit_frontend_worker_try_lock_publication_enabled() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::source_unit_frontend_worker_try_lock_publication_enabled", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[36]);
	return bool_t(php::identical(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_SOURCE_UNIT_FRONTEND_WORKER_TRY_LOCK_PUBLICATION")), string_t("1")));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_source_unit_frontend_worker_publish_batch_cap() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::source_unit_frontend_worker_publish_batch_cap", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[37]);
	string_t text = required_cast<string_t>(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_SOURCE_UNIT_FRONTEND_WORKER_PUBLISH_BATCH_CAP")));
	if (static_cast<bool>(php::identical(text, string_t("")))) {
		return static_cast<int_t<> >(0);
	}
	int_t<> value = required_cast<int_t<>>(cast<int_t<>>(text));
	if (static_cast<bool>((value < static_cast<int_t<> >(1)))) {
		return static_cast<int_t<> >(0);
	}
	return value;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
bool_t __latency_fn_resident_source_unit_frontend_payload_tables_source_unit_frontend_worker_local_symbol_publication_enabled() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::source_unit_frontend_worker_local_symbol_publication_enabled", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[38]);
	return (__latency_fn_resident_source_unit_frontend_payload_tables_source_unit_frontend_worker_locked_publication_enabled() || php::identical(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_SOURCE_UNIT_FRONTEND_WORKER_LOCAL_SYMBOL_PUBLICATION")), string_t("1")));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
bool_t __latency_fn_resident_source_unit_frontend_payload_tables_source_unit_frontend_worker_retain_token_result_enabled() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::source_unit_frontend_worker_retain_token_result_enabled", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[39]);
	return bool_t(php::identical(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_SOURCE_UNIT_FRONTEND_WORKER_RETAIN_TOKEN_RESULT")), string_t("1")));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
bool_t __latency_fn_resident_source_unit_frontend_payload_tables_worker_result_full_model_debug_retention_enabled() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_result_full_model_debug_retention_enabled", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[40]);
	return ((php::identical(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_WORKER_RESULT_FULL_MODEL_DEBUG_RETENTION")), string_t("1")) || php::identical(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_WORKER_RESULT_FULL_MODEL_DEBUG_INSTALL")), string_t("1"))) || php::identical(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_ENTRY_FULL_MODEL_DEBUG_MATERIALIZATION")), string_t("1")));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_source_unit_frontend_payload_tables_worker_id_for_source_unit(int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_id_for_source_unit", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[41]);
	int_t<> sourceId = required_cast<int_t<>>(cast<int_t<>>(sourceUnitId));
	return __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int((((sourceId - static_cast<int_t<> >(1)) % static_cast<int_t<> >(2)) + static_cast<int_t<> >(1)));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
ResidentSourceUnitFrontendPayloadTableRow __latency_fn_resident_source_unit_frontend_payload_tables_row_from_built(SourceUnitTableRow sourceUnit, shared_p<TokenStream> tokens, shared_p<FrontendModel> model, int_t<std::uint32_t> payloadTableId, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::row_from_built", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[42]);
	return __latency_fn_resident_source_unit_frontend_payload_tables_row_from_built_counts(sourceUnit, cast<int_t<std::uint32_t>>(payloadTableId), cast<int_t<std::uint32_t>>(ownerRunId), tokens->token_count, __latency_fn_token_tables_segment_count(tokens), __latency_fn_token_tables_reserved_segment_bytes(tokens), __latency_fn_token_tables_segment_slack_bytes(tokens), model->node_count, __latency_fn_frontend_model_tables_node_segment_count(model), __latency_fn_frontend_model_tables_node_segment_reserved_bytes(model), __latency_fn_frontend_model_tables_node_segment_slack_bytes(model), model->parser_error_count);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
ResidentSourceUnitFrontendPayloadTableRow __latency_fn_resident_source_unit_frontend_payload_tables_row_from_built_counts(SourceUnitTableRow sourceUnit, int_t<std::uint32_t> payloadTableId, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> tokenCount, int_t<std::uint32_t> tokenSegmentCount, int_t<std::uint32_t> tokenReservedSegmentBytes, int_t<std::uint32_t> tokenSegmentSlackBytes, int_t<std::uint32_t> frontendNodeCount, int_t<std::uint32_t> frontendNodeSegmentCount, int_t<std::uint32_t> frontendNodeReservedSegmentBytes, int_t<std::uint32_t> frontendNodeSegmentSlackBytes, int_t<std::uint32_t> parserErrorCount) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::row_from_built_counts", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[43]);
	ResidentSourceUnitFrontendPayloadTableRow row = ResidentSourceUnitFrontendPayloadTableRow{};
	row->payload_table_id = payloadTableId;
	row->owner_run_id = ownerRunId;
	row->source_unit_id = sourceUnit->source_unit_id;
	row->source_unit_key_id = sourceUnit->source_unit_key_id;
	row->worker_id = __latency_fn_resident_source_unit_frontend_payload_tables_worker_id_for_source_unit(sourceUnit->source_unit_id);
	row->input_snapshot_generation = ownerRunId;
	row->token_list_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(((cast<int_t<>>(payloadTableId) * static_cast<int_t<> >(10)) + static_cast<int_t<> >(1)));
	row->token_row_count = tokenCount;
	row->token_segment_count = tokenSegmentCount;
	row->token_reserved_segment_bytes = tokenReservedSegmentBytes;
	row->token_segment_slack_bytes = tokenSegmentSlackBytes;
	row->frontend_node_list_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(((cast<int_t<>>(payloadTableId) * static_cast<int_t<> >(10)) + static_cast<int_t<> >(2)));
	row->frontend_node_count = frontendNodeCount;
	row->frontend_node_segment_count = frontendNodeSegmentCount;
	row->frontend_node_reserved_segment_bytes = frontendNodeReservedSegmentBytes;
	row->frontend_node_segment_slack_bytes = frontendNodeSegmentSlackBytes;
	row->parser_error_count = parserErrorCount;
	row->payload_owner_kind_id = __latency_fn_resident_source_unit_frontend_payload_tables_owner_kind_worker_local_id();
	row->status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
ResidentSourceUnitFrontendPayloadTableRow __latency_fn_resident_source_unit_frontend_payload_tables_record_built_handle_metrics(shared_p<CompilerProjectRunReport>& report, SourceUnitTableRow sourceUnit, shared_p<TokenStream>& tokens, shared_p<FrontendModel>& model, int_t<std::uint32_t> payloadTableId, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::record_built_handle_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[44]);
	ResidentSourceUnitFrontendPayloadTableRow row = __latency_fn_resident_source_unit_frontend_payload_tables_row_from_built(sourceUnit, tokens, model, cast<int_t<std::uint32_t>>(payloadTableId), cast<int_t<std::uint32_t>>(ownerRunId));
	return __latency_fn_resident_source_unit_frontend_payload_tables_record_built_handle_metrics_row(report, row);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
ResidentSourceUnitFrontendPayloadTableRow __latency_fn_resident_source_unit_frontend_payload_tables_record_built_handle_metrics_from_counts(shared_p<CompilerProjectRunReport>& report, SourceUnitTableRow sourceUnit, int_t<std::uint32_t> payloadTableId, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> tokenCount, int_t<std::uint32_t> tokenSegmentCount, int_t<std::uint32_t> tokenReservedSegmentBytes, int_t<std::uint32_t> tokenSegmentSlackBytes, int_t<std::uint32_t> frontendNodeCount, int_t<std::uint32_t> frontendNodeSegmentCount, int_t<std::uint32_t> frontendNodeReservedSegmentBytes, int_t<std::uint32_t> frontendNodeSegmentSlackBytes, int_t<std::uint32_t> parserErrorCount) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::record_built_handle_metrics_from_counts", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[45]);
	ResidentSourceUnitFrontendPayloadTableRow row = __latency_fn_resident_source_unit_frontend_payload_tables_row_from_built_counts(sourceUnit, cast<int_t<std::uint32_t>>(payloadTableId), cast<int_t<std::uint32_t>>(ownerRunId), cast<int_t<std::uint32_t>>(tokenCount), cast<int_t<std::uint32_t>>(tokenSegmentCount), cast<int_t<std::uint32_t>>(tokenReservedSegmentBytes), cast<int_t<std::uint32_t>>(tokenSegmentSlackBytes), cast<int_t<std::uint32_t>>(frontendNodeCount), cast<int_t<std::uint32_t>>(frontendNodeSegmentCount), cast<int_t<std::uint32_t>>(frontendNodeReservedSegmentBytes), cast<int_t<std::uint32_t>>(frontendNodeSegmentSlackBytes), cast<int_t<std::uint32_t>>(parserErrorCount));
	return __latency_fn_resident_source_unit_frontend_payload_tables_record_built_handle_metrics_row(report, row);
}

}
