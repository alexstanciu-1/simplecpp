#include <scpp/lang/php.hpp>
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendEmissionDecisionRow.hpp"
#include "__types/BackendEmissionValueRow.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/BackendRequestAuthorizationRow.hpp"
#include "__types/BackendRequestRowList.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendNodeSpan.hpp"
#include "__types/FunctionBodyTextEmissionPreflightArtifact.hpp"
#include "__types/FunctionBodyTextEmissionPreflightRow.hpp"
#include "__types/LoweringBlockedRequestRow.hpp"
#include "__types/LoweringPlan.hpp"
#include "__types/LoweringStep.hpp"
#include "__types/PipelineConfig.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__types/TokenRow.hpp"
#include "__types/TokenStream.hpp"
#include "__types/pure_tdd_stage_artifacts.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_project_run_artifact_path_from_env.hpp"
#include "__callable/__latency_fn_pure_tdd_stage_artifacts_export_root_from_env.hpp"
#include "__callable/__latency_fn_pure_tdd_stage_artifacts_export_root_from_env.hpp"
#include "__callable/__latency_fn_pure_tdd_stage_artifacts_project_dir.hpp"
#include "__callable/__latency_fn_pure_tdd_stage_artifacts_enabled.hpp"
#include "__callable/__latency_fn_pure_tdd_stage_artifacts_project_dir.hpp"
#include "__callable/__latency_fn_output_paths_ensure_parent_and_dir.hpp"
#include "__callable/__latency_fn_pure_tdd_stage_artifacts_project_dir.hpp"
#include "__callable/__latency_fn_pure_tdd_stage_artifacts_write_text.hpp"
#include "__callable/__latency_fn_pure_tdd_stage_artifacts_clean_tsv_value.hpp"
#include "__callable/__latency_fn_pure_tdd_stage_artifacts_append_tokens_tsv__exec.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_token_tables_resolved_length.hpp"
#include "__callable/__latency_fn_token_tables_token_by_index.hpp"
#include "__callable/__latency_fn_frontend_model_tables_first_node_span.hpp"
#include "__callable/__latency_fn_frontend_model_tables_span_node_at.hpp"
#include "__callable/__latency_fn_pure_tdd_stage_artifacts_append_parse_tsv__exec.hpp"
#include "__callable/__latency_fn_pure_tdd_stage_artifacts_parse_resolve_tsv.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_row_by_index.hpp"
#include "__callable/__latency_fn_pure_tdd_stage_artifacts_lowering_tsv.hpp"
namespace scpp { extern const int __latency_lines_pure_tdd_stage_artifacts[]; }
namespace scpp {
bool_t pure_tdd_stage_artifacts::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == pure_tdd_stage_artifacts::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_pure_tdd_stage_artifacts[]; }
namespace scpp {
string_t __latency_fn_pure_tdd_stage_artifacts_export_root_from_env() {
	SCPP_CALL_DEPTH_GUARD("pure_tdd_stage_artifacts::export_root_from_env", "/tmp/scpp-edit-latency-20260919/app/compile/support/pure_tdd_stage_artifacts.phs", __latency_lines_pure_tdd_stage_artifacts[0]);
	string_t exact = required_cast<string_t>(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_PURE_TDD_STAGE_ARTIFACT_DIR")));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(exact, string_t(""))))) {
		return exact;
	}
	string_t artifactRoot = required_cast<string_t>(__latency_fn_pipeline_config_helpers_project_run_artifact_path_from_env());
	if (static_cast<bool>(php::identical(artifactRoot, string_t("")))) {
		return string_t("");
	}
	return (cast<string_t>(artifactRoot) + string_t("/stages"));
}

}

namespace scpp { extern const int __latency_lines_pure_tdd_stage_artifacts[]; }
namespace scpp {
string_t __latency_fn_pure_tdd_stage_artifacts_project_dir(shared_p<PipelineConfig> config) {
	SCPP_CALL_DEPTH_GUARD("pure_tdd_stage_artifacts::project_dir", "/tmp/scpp-edit-latency-20260919/app/compile/support/pure_tdd_stage_artifacts.phs", __latency_lines_pure_tdd_stage_artifacts[1]);
	string_t root = required_cast<string_t>(__latency_fn_pure_tdd_stage_artifacts_export_root_from_env());
	if (static_cast<bool>(php::identical(root, string_t("")))) {
		return string_t("");
	}
	return (cast<string_t>(root) + string_t("/") + cast<string_t>(config->run_label));
}

}

namespace scpp { extern const int __latency_lines_pure_tdd_stage_artifacts[]; }
namespace scpp {
bool_t __latency_fn_pure_tdd_stage_artifacts_enabled(shared_p<PipelineConfig> config) {
	SCPP_CALL_DEPTH_GUARD("pure_tdd_stage_artifacts::enabled", "/tmp/scpp-edit-latency-20260919/app/compile/support/pure_tdd_stage_artifacts.phs", __latency_lines_pure_tdd_stage_artifacts[2]);
	return bool_t(php::not_identical(__latency_fn_pure_tdd_stage_artifacts_project_dir(config), string_t("")));
}

}

namespace scpp { extern const int __latency_lines_pure_tdd_stage_artifacts[]; }
namespace scpp {
bool_t __latency_fn_pure_tdd_stage_artifacts_write_text(shared_p<PipelineConfig> config, const string_t& fileName, const string_t& text) {
	SCPP_CALL_DEPTH_GUARD("pure_tdd_stage_artifacts::write_text", "/tmp/scpp-edit-latency-20260919/app/compile/support/pure_tdd_stage_artifacts.phs", __latency_lines_pure_tdd_stage_artifacts[3]);
	string_t dir = required_cast<string_t>(__latency_fn_pure_tdd_stage_artifacts_project_dir(config));
	if (static_cast<bool>(php::identical(dir, string_t("")))) {
		return bool_t(static_cast<bool_t>(true));
	}
	if (static_cast<bool>((!__latency_fn_output_paths_ensure_parent_and_dir(fs::dirname(dir), dir)))) {
		php::echo_one(string_t("pure_tdd_stage_write=failed\n"));
		php::echo_one(string_t("pure_tdd_stage_dir="));
		php::echo_one(dir);
		php::echo_one(string_t("\n"));
		return bool_t(static_cast<bool_t>(false));
	}
	string_t path = required_cast<string_t>((cast<string_t>(dir) + string_t("/") + cast<string_t>(fileName)));
	error_t err;
	int_t<> written;
	if (static_cast<bool>((!php::take(written, err, fs::put(path, text))))) {
		php::echo_one(string_t("pure_tdd_stage_write=failed\n"));
		php::echo_one(string_t("pure_tdd_stage_path="));
		php::echo_one(path);
		php::echo_one(string_t("\n"));
		return bool_t(static_cast<bool_t>(false));
	}
	return bool_t(static_cast<bool_t>(true));
}

}

namespace scpp { extern const int __latency_lines_pure_tdd_stage_artifacts[]; }
namespace scpp {
string_t __latency_fn_pure_tdd_stage_artifacts_clean_tsv_value(const string_t& value) {
	SCPP_CALL_DEPTH_GUARD("pure_tdd_stage_artifacts::clean_tsv_value", "/tmp/scpp-edit-latency-20260919/app/compile/support/pure_tdd_stage_artifacts.phs", __latency_lines_pure_tdd_stage_artifacts[4]);
	string_t clean = required_cast<string_t>(str::replace(string_t("\t"), string_t(" "), value));
	clean = str::replace(string_t("\r"), string_t(" "), clean);
	clean = str::replace(string_t("\n"), string_t(" "), clean);
	return clean;
}

}

namespace scpp { extern const int __latency_lines_pure_tdd_stage_artifacts[]; }
namespace scpp {
void __latency_fn_pure_tdd_stage_artifacts_append_tokens_tsv__exec(string_t& text, SourceUnitTableRow sourceUnit, shared_p<TokenStream> tokens) {
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < cast<int_t<>>(tokens->token_count)))) {
		TokenRow row = __latency_fn_token_tables_token_by_index(tokens, index);
		int_t<> tokenId = required_cast<int_t<>>((index + static_cast<int_t<> >(1)));
		text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(sourceUnit->source_unit_id)) + string_t("\t") + cast<string_t>(tokenId) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->kind_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->start_offset)) + string_t("\t") + cast<string_t>(cast<int_t<>>(__latency_fn_token_tables_resolved_length(tokens, __latency_fn_structure_row_ids_uint32_from_int(tokenId)))) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->flags)) + string_t("\n"));
		index = (index + static_cast<int_t<> >(1));
	}
}

}

namespace scpp { extern const int __latency_lines_pure_tdd_stage_artifacts[]; }
namespace scpp {
void __latency_fn_pure_tdd_stage_artifacts_append_parse_tsv__exec(string_t& text, SourceUnitTableRow sourceUnit, shared_p<FrontendModel> model) {
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < cast<int_t<>>(model->node_count)))) {
		FrontendNodeRow row = __latency_fn_frontend_model_tables_span_node_at(model, __latency_fn_frontend_model_tables_first_node_span(model), index);
		text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(sourceUnit->source_unit_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->node_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->parent_node_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->first_child_node_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->next_sibling_node_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->row_family_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->row_kind_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->payload_kind_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->payload_row_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->source_range_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->flags)) + string_t("\n"));
		index = (index + static_cast<int_t<> >(1));
	}
}

}

namespace scpp { extern const int __latency_lines_pure_tdd_stage_artifacts[]; }
namespace scpp {
string_t __latency_fn_pure_tdd_stage_artifacts_parse_resolve_tsv(shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("pure_tdd_stage_artifacts::parse_resolve_tsv", "/tmp/scpp-edit-latency-20260919/app/compile/support/pure_tdd_stage_artifacts.phs", __latency_lines_pure_tdd_stage_artifacts[5]);
	string_t text = required_cast<string_t>(string_t("symbol_id\tsource_unit_id\tsymbol_kind_id\tstatus_id\tname_id\treturn_type_ref_id\tparameter_count\tbody_first_node_id\n"));
	auto __latency_local_0 = symbols->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		text = (cast<string_t>(text) + cast<string_t>(cast<int_t<>>(row->symbol_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->source_unit_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->symbol_kind_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->name_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->return_type_ref_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->parameter_count)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->body_first_node_id)) + string_t("\n"));
	}
	return text;
}

}

namespace scpp { extern const int __latency_lines_pure_tdd_stage_artifacts[]; }
namespace scpp {
string_t __latency_fn_pure_tdd_stage_artifacts_lowering_tsv(shared_p<LoweringPlan> plan, shared_p<BackendRequestAuthorizationArtifact> requests, BackendEmissionDecisionArtifact emission, FunctionBodyTextEmissionPreflightArtifact preflight) {
	SCPP_CALL_DEPTH_GUARD("pure_tdd_stage_artifacts::lowering_tsv", "/tmp/scpp-edit-latency-20260919/app/compile/support/pure_tdd_stage_artifacts.phs", __latency_lines_pure_tdd_stage_artifacts[6]);
	string_t text = required_cast<string_t>(string_t("section\trow_id\tstatus_id\tblocked_reason_id\tkind_id\tfeature_or_contract_id\tlowering_adapter_id\tsource_row_id\tsource_reference_id\ttarget_symbol_id\towner_symbol_id\ttype_ref_id\tvalue\n"));
	int_t<> requestIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((requestIndex < cast<int_t<>>(requests->request_count)))) {
		BackendRequestAuthorizationRow row = __latency_fn_backend_request_row_lists_row_by_index(requests->request_rows, requestIndex);
		text = (cast<string_t>(text) + string_t("backend_request\t") + cast<string_t>(cast<int_t<>>(row->request_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->blocked_reason_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->lowering_step_kind_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->feature_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->lowering_adapter_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->source_row_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->source_reference_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->target_symbol_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->cache_owner_symbol_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->provider_type_ref_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->value)) + string_t("\n"));
		requestIndex = (requestIndex + static_cast<int_t<> >(1));
	}
	auto __latency_local_0 = plan->steps;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		text = (cast<string_t>(text) + string_t("lowering_step\t") + cast<string_t>(cast<int_t<>>(row->step_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(plan->backend_emit_status_id)) + string_t("\t0\t") + cast<string_t>(cast<int_t<>>(row->step_kind_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->contract_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->lowering_adapter_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->source_row_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->source_reference_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->target_symbol_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(plan->owner_symbol_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->type_ref_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->value)) + string_t("\n"));
	}
	auto __latency_local_2 = plan->blocked_requests;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto row = __latency_local_3.value_copy();
		text = (cast<string_t>(text) + string_t("lowering_blocked_request\t") + cast<string_t>(cast<int_t<>>(row->blocked_request_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->blocked_reason_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->lowering_adapter_id)) + string_t("\t") + string_t("0\t") + cast<string_t>(cast<int_t<>>(row->lowering_adapter_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->source_row_id)) + string_t("\t0\t0\t") + cast<string_t>(cast<int_t<>>(plan->owner_symbol_id)) + string_t("\t0\t0\n"));
	}
	auto __latency_local_4 = emission->decisions;
	for (auto __latency_local_5 : foreach_range(__latency_local_4)) {
		auto row = __latency_local_5.value_copy();
		text = (cast<string_t>(text) + string_t("backend_emission_decision\t") + cast<string_t>(cast<int_t<>>(row->decision_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->blocked_reason_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->decision_kind_id)) + string_t("\t0\t0\t0\t0\t0\t") + cast<string_t>(cast<int_t<>>(row->owner_symbol_id)) + string_t("\t0\t0\n"));
	}
	auto __latency_local_6 = emission->values;
	for (auto __latency_local_7 : foreach_range(__latency_local_6)) {
		auto row = __latency_local_7.value_copy();
		text = (cast<string_t>(text) + string_t("backend_emission_value\t") + cast<string_t>(cast<int_t<>>(row->value_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->blocked_reason_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->value_role_id)) + string_t("\t") + string_t("0\t0\t") + cast<string_t>(cast<int_t<>>(row->source_row_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->source_reference_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->target_symbol_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(emission->owner_symbol_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->type_ref_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->value)) + string_t("\n"));
	}
	auto __latency_local_8 = preflight->rows;
	for (auto __latency_local_9 : foreach_range(__latency_local_8)) {
		auto row = __latency_local_9.value_copy();
		text = (cast<string_t>(text) + string_t("llvm_preflight\t") + cast<string_t>(cast<int_t<>>(row->preflight_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->blocked_reason_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->text_sink_policy_id)) + string_t("\t") + string_t("0\t0\t") + cast<string_t>(cast<int_t<>>(row->symbol_id)) + string_t("\t0\t") + cast<string_t>(cast<int_t<>>(row->symbol_id)) + string_t("\t") + cast<string_t>(cast<int_t<>>(row->symbol_id)) + string_t("\t0\t0\n"));
	}
	return text;
}

}
