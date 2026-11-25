#include <scpp/lang/php.hpp>
#include "__types/CompilerProfileEventRow.hpp"
#include "__types/CompilerProjectRunReport.hpp"
#include "__callable/__latency_fn_compiler_profile_events_append_elapsed.hpp"
#include "__callable/__latency_fn_compiler_profile_events_append_event.hpp"
#include "__callable/__latency_fn_compiler_profile_events_elapsed_us_since.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_analysis_capability_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_artifact_write_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_backend_direct_call_setup_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_backend_entry_call_emission_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_backend_lowering_writes_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_backend_target_local_emission_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_backend_target_local_match_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_capability_load_probe_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_compiler_project_run_total_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_frontend_payload_publication_metrics_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_frontend_source_units_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_frontend_symbols_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_function_body_ownership_snapshots_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_llvm_text_materialization_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_manifest_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_name.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_output_setup_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_parser_frontend_model_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_project_symbol_index_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_references_contracts_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_resident_frontend_node_snapshots_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_resident_token_snapshots_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_source_units_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_symbol_definition_snapshots_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_symbol_fact_publication_metrics_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_symbol_name_lookups_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_symbol_state_publication_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_tokenizer_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_status_blocked_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_status_completed_id.hpp"
#include "__callable/__latency_fn_compiler_profile_events_status_name.hpp"
#include "__callable/__latency_fn_compiler_profile_events_debug_string.hpp"
#include "__callable/__latency_fn_compiler_profile_events_stage_name.hpp"
namespace scpp { extern const int __latency_lines_compiler_profile_events[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_compiler_profile_events_append_elapsed(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, int_t<std::uint16_t> stageId, int_t<std::uint32_t> rowCount, int_t<std::uint64_t> startedUs, int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("compiler_profile_events::append_elapsed", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_profile_events.phs", __latency_lines_compiler_profile_events[32]);
	int_t<std::uint32_t> elapsedUs = required_cast<int_t<std::uint32_t>>(__latency_fn_compiler_profile_events_elapsed_us_since(cast<int_t<std::uint64_t>>(startedUs)));
	__latency_fn_compiler_profile_events_append_event(report, cast<int_t<std::uint32_t>>(ownerRunId), cast<int_t<std::uint16_t>>(stageId), cast<int_t<std::uint32_t>>(rowCount), cast<int_t<std::uint32_t>>(elapsedUs), cast<int_t<std::uint16_t>>(statusId));
	return cast<int_t<std::uint32_t>>(elapsedUs);
}

}

namespace scpp { extern const int __latency_lines_compiler_profile_events[]; }
namespace scpp {
string_t __latency_fn_compiler_profile_events_stage_name(int_t<std::uint16_t> stageId) {
	SCPP_CALL_DEPTH_GUARD("compiler_profile_events::stage_name", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_profile_events.phs", __latency_lines_compiler_profile_events[33]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_compiler_project_run_total_id())))) {
		return string_t("compiler_project_run_total");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_output_setup_id())))) {
		return string_t("output_setup");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_manifest_id())))) {
		return string_t("project_manifest");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_source_units_id())))) {
		return string_t("source_units");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_frontend_symbols_id())))) {
		return string_t("frontend_symbols");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_references_contracts_id())))) {
		return string_t("references_contracts");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_analysis_capability_id())))) {
		return string_t("analysis_capability");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_backend_lowering_writes_id())))) {
		return string_t("backend_lowering_writes");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_capability_load_probe_id())))) {
		return string_t("capability_load_probe");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_backend_direct_call_setup_id())))) {
		return string_t("backend_direct_call_setup");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_backend_target_local_match_id())))) {
		return string_t("backend_target_local_match");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_backend_target_local_emission_id())))) {
		return string_t("backend_target_local_emission");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_backend_entry_call_emission_id())))) {
		return string_t("backend_entry_call_emission");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_llvm_text_materialization_id())))) {
		return string_t("llvm_text_materialization");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_artifact_write_id())))) {
		return string_t("artifact_write");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_frontend_source_units_id())))) {
		return string_t("frontend_source_units");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_project_symbol_index_id())))) {
		return string_t("project_symbol_index");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_tokenizer_id())))) {
		return string_t("tokenizer");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_parser_frontend_model_id())))) {
		return string_t("parser_frontend_model");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_resident_token_snapshots_id())))) {
		return string_t("resident_token_snapshots");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_resident_frontend_node_snapshots_id())))) {
		return string_t("resident_frontend_node_snapshots");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_symbol_definition_snapshots_id())))) {
		return string_t("symbol_definition_snapshots");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_symbol_name_lookups_id())))) {
		return string_t("symbol_name_lookups");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_function_body_ownership_snapshots_id())))) {
		return string_t("function_body_ownership_snapshots");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_frontend_payload_publication_metrics_id())))) {
		return string_t("frontend_payload_publication_metrics");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_symbol_state_publication_id())))) {
		return string_t("symbol_state_publication");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(stageId), cast<int_t<>>(__latency_fn_compiler_profile_events_stage_symbol_fact_publication_metrics_id())))) {
		return string_t("symbol_fact_publication_metrics");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_compiler_profile_events[]; }
namespace scpp {
string_t __latency_fn_compiler_profile_events_status_name(int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("compiler_profile_events::status_name", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_profile_events.phs", __latency_lines_compiler_profile_events[34]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_compiler_profile_events_status_completed_id())))) {
		return string_t("completed");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_compiler_profile_events_status_blocked_id())))) {
		return string_t("blocked");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_compiler_profile_events[]; }
namespace scpp {
string_t __latency_fn_compiler_profile_events_debug_string(CompilerProfileEventRow row) {
	SCPP_CALL_DEPTH_GUARD("compiler_profile_events::debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/support/compiler_profile_events.phs", __latency_lines_compiler_profile_events[35]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->event_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	return (string_t("compiler_profile_event:") + cast<string_t>(cast<int_t<>>(row->owner_run_id)) + string_t(":") + cast<string_t>(__latency_fn_compiler_profile_events_stage_name(row->stage_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->elapsed_us)));
}

}
