#include <scpp/lang/php.hpp>
#include "__types/ArtifactWriteRecord.hpp"
#include "__types/ArtifactWriteReportRecord.hpp"
#include "__types/BackendEmissionDecisionArtifact.hpp"
#include "__types/BackendEmissionValueRow.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__types/CapabilityCoverageArtifact.hpp"
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNamePayloadRow.hpp"
#include "__types/FunctionBodyTextEmissionPreflightArtifact.hpp"
#include "__types/FunctionBodyTextEmissionPreflightRow.hpp"
#include "__types/LoweringPlan.hpp"
#include "__types/PipelineConfig.hpp"
#include "__types/ProjectManifest.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__types/TokenStream.hpp"
#include "__counter/create.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_row.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_status_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_empty_row.hpp"
#include "__callable/__latency_fn_compiler_project_runner_append_row.hpp"
#include "__callable/__latency_fn_compiler_project_runner_completed_status_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_compiler_project_runner_append_source_model.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_source_unit.hpp"
#include "__callable/__latency_fn_phs_tokenizer_from_source_text.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_from_model.hpp"
#include "__callable/__latency_fn_source_units_source_text_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_artifact_writes_report_from_llvm_text_preflight.hpp"
#include "__callable/__latency_fn_backend_module_composition_module_text_for_entry.hpp"
#include "__callable/__latency_fn_compiler_entry_backend_backend_emission_for_entry.hpp"
#include "__callable/__latency_fn_compiler_export_artifacts_append_parse_tsv.hpp"
#include "__callable/__latency_fn_compiler_export_artifacts_append_tokens_tsv.hpp"
#include "__callable/__latency_fn_compiler_export_artifacts_branch_dataflow_tsv.hpp"
#include "__callable/__latency_fn_compiler_export_artifacts_control_transfer_tsv.hpp"
#include "__callable/__latency_fn_compiler_export_artifacts_lowering_tsv.hpp"
#include "__callable/__latency_fn_compiler_export_artifacts_parse_resolve_tsv.hpp"
#include "__callable/__latency_fn_compiler_export_artifacts_project_cfg_tsv.hpp"
#include "__callable/__latency_fn_compiler_export_artifacts_write_stage_text.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_layer_artifact_writes_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_layer_backend_text_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_layer_frontend_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_layer_symbol_index_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_reason_artifact_write_failed_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_reason_backend_text_not_ready_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_reason_entry_symbol_missing_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_reason_frontend_parse_failed_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_reason_source_load_failed_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_row.hpp"
#include "__callable/__latency_fn_compiler_project_runner_blocked_status_id.hpp"
#include "__callable/__latency_fn_compiler_project_runner_completed_row.hpp"
#include "__callable/__latency_fn_compiler_project_runner_row_from_config.hpp"
#include "__callable/__latency_fn_control_flow_dataflows_project_has_blocking_diagnostic.hpp"
#include "__callable/__latency_fn_control_flow_transfers_project_has_blocking_diagnostic.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_source_unit.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_preflight_by_id.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_preflight_from_emission.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_publication_llvm_text_nonempty_row_count.hpp"
#include "__callable/__latency_fn_llvm_text_from_plan_publication_sink_boundary_row_count.hpp"
#include "__callable/__latency_fn_lowering_plan_publication_plan_row_count.hpp"
#include "__callable/__latency_fn_output_paths_ensure_parent_and_dir.hpp"
#include "__callable/__latency_fn_phs_tokenizer_from_source_text.hpp"
#include "__callable/__latency_fn_project_manifest_from_project_dir.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_from_model.hpp"
#include "__callable/__latency_fn_project_symbol_index_function_symbol_by_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_new_index.hpp"
#include "__callable/__latency_fn_source_units_source_text_by_id.hpp"
#include "__callable/__latency_fn_source_units_table_from_manifest.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
CompilerProjectRunRow __latency_fn_compiler_project_runner_blocked_row(int_t<std::uint32_t> runId, int_t<std::uint16_t> blockedLayerId, int_t<std::uint16_t> blockedReasonId) {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::blocked_row", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[15]);
	CompilerProjectRunRow row = __latency_fn_compiler_project_runner_empty_row();
	row->run_id = runId;
	row->run_label_id = runId;
	row->status_id = __latency_fn_compiler_project_runner_blocked_status_id();
	row->blocked_layer_id = blockedLayerId;
	row->blocked_reason_id = blockedReasonId;
	return row;
}

}

namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
void __latency_fn_compiler_project_runner_append_row(shared_p<CompilerProjectRunReport>& report, const string_t& runLabel, CompilerProjectRunRow row) {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::append_row", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[16]);
	int_t<std::uint32_t> runId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_next_dense_id(php::count(report->rows)));
	(void) report->run_labels.append(runLabel);
	row->run_id = runId;
	row->run_label_id = runId;
	(void) report->rows.append(row);
	report->row_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(report->rows));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_compiler_project_runner_completed_status_id())))) {
		report->completed_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->completed_count) + static_cast<int_t<> >(1)));
	}
	else {
		report->blocked_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->blocked_count) + static_cast<int_t<> >(1)));
	}
	report->source_byte_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->source_byte_count) + cast<int_t<>>(row->source_byte_count)));
	report->symbol_lookup_probe_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(report->symbol_lookup_probe_count) + cast<int_t<>>(row->symbol_lookup_probe_count)));
}

}

namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
shared_p<FrontendModel> __latency_fn_compiler_project_runner_append_source_model(CompilerProjectRunRow& row, shared_p<ProjectSymbolIndex>& symbols, shared_p<SourceUnitTable> sourceUnits, SourceUnitTableRow sourceUnit) {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::append_source_model", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[17]);
	string_t source = required_cast<string_t>(__latency_fn_source_units_source_text_by_id(sourceUnits, sourceUnit->source_unit_id));
	row->source_byte_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(row->source_byte_count) + str::byte_length(source)));
	shared_p<TokenStream> tokens = __latency_fn_phs_tokenizer_from_source_text(sourceUnit->source_unit_id, __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), source);
	row->token_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(row->token_count) + cast<int_t<>>(tokens->token_count)));
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	shared_p<FrontendModel> model = __latency_fn_frontend_model_builder_parse_source_unit(sourceUnit, source, tokens, counters);
	row->frontend_node_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(row->frontend_node_count) + cast<int_t<>>(model->node_count)));
	row->frontend_declaration_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(row->frontend_declaration_count) + cast<int_t<>>(model->declaration_count)));
	row->parser_error_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(row->parser_error_count) + cast<int_t<>>(model->parser_error_count)));
	__latency_fn_project_symbol_index_append_from_model(symbols, sourceUnits, sourceUnit, source, model);
	return model;
}

}

namespace scpp { extern const int __latency_lines_compiler_project_runner[]; }
namespace scpp {
CompilerProjectRunRow __latency_fn_compiler_project_runner_row_from_config(int_t<std::uint32_t> runId, shared_p<PipelineConfig> config) {
	SCPP_CALL_DEPTH_GUARD("compiler_project_runner::row_from_config", "/tmp/scpp-edit-latency-20260919/app/compile/pipeline/compiler_project_runner.phs", __latency_lines_compiler_project_runner[18]);
	CompilerProjectRunRow row = __latency_fn_compiler_project_runner_completed_row(cast<int_t<std::uint32_t>>(runId), config->run_label);
	shared_p<ProjectManifest> manifest = __latency_fn_project_manifest_from_project_dir(config->project_dir);
	shared_p<SourceUnitTable> sourceUnits = __latency_fn_source_units_table_from_manifest(manifest);
	row->source_count = sourceUnits->source_unit_count;
	if (static_cast<bool>(php::identical(cast<int_t<>>(sourceUnits->source_unit_count), static_cast<int_t<> >(0)))) {
		return __latency_fn_compiler_project_runner_blocked_row(cast<int_t<std::uint32_t>>(runId), __latency_fn_compiler_project_runner_blocked_layer_frontend_id(), __latency_fn_compiler_project_runner_blocked_reason_source_load_failed_id());
	}
	shared_p<ProjectSymbolIndex> symbols = __latency_fn_project_symbol_index_new_index(cast<int_t<>>(sourceUnits->source_unit_count));
	shared_p<FrontendModel> entryModel = create<FrontendModel>();
	string_t entrySource = required_cast<string_t>(string_t(""));
	string_t tokenizeTsv = required_cast<string_t>(string_t("source_unit_id\ttoken_id\tkind_id\tstart_offset\tlength\tflags\n"));
	string_t parseTsv = required_cast<string_t>(string_t("source_unit_id\tnode_id\tparent_node_id\tfirst_child_node_id\tnext_sibling_node_id\trow_family_id\trow_kind_id\tpayload_kind_id\tpayload_row_id\tsource_range_id\tflags\n"));
	auto __latency_local_0 = sourceUnits->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto sourceUnit = __latency_local_1.value_copy();
		string_t source = required_cast<string_t>(__latency_fn_source_units_source_text_by_id(sourceUnits, sourceUnit->source_unit_id));
		row->source_byte_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(row->source_byte_count) + str::byte_length(source)));
		shared_p<TokenStream> tokens = __latency_fn_phs_tokenizer_from_source_text(sourceUnit->source_unit_id, __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), source);
		row->token_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(row->token_count) + cast<int_t<>>(tokens->token_count)));
		__latency_fn_compiler_export_artifacts_append_tokens_tsv(tokenizeTsv, sourceUnit, tokens);
		shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
		shared_p<FrontendModel> model = __latency_fn_frontend_model_builder_parse_source_unit(sourceUnit, source, tokens, counters);
		row->frontend_node_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(row->frontend_node_count) + cast<int_t<>>(model->node_count)));
		row->frontend_declaration_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(row->frontend_declaration_count) + cast<int_t<>>(model->declaration_count)));
		row->parser_error_count = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(row->parser_error_count) + cast<int_t<>>(model->parser_error_count)));
		__latency_fn_compiler_export_artifacts_append_parse_tsv(parseTsv, sourceUnit, model);
		__latency_fn_project_symbol_index_append_from_model(symbols, sourceUnits, sourceUnit, source, model);
		if (static_cast<bool>(php::identical(entrySource, string_t("")))) {
			entryModel = model;
			entrySource = source;
		}
	}
	__latency_fn_compiler_export_artifacts_write_stage_text(config, string_t("tokenize.tsv"), tokenizeTsv);
	__latency_fn_compiler_export_artifacts_write_stage_text(config, string_t("parse.tsv"), parseTsv);
	row->symbol_count = symbols->symbol_count;
	row->project_symbol_sidecar_string_count = __latency_fn_structure_row_ids_uint32_from_int(((((php::count(symbols->names) + php::count(symbols->qualified_names)) + php::count(symbols->signature_shapes)) + php::count(symbols->body_shapes)) + php::count(symbols->source_unit_keys)));
	row->project_symbol_sidecar_lookup_entry_count = __latency_fn_structure_row_ids_uint32_from_int(((((php::count(symbols->name_ids_by_text) + php::count(symbols->qualified_name_ids_by_text)) + php::count(symbols->signature_shape_ids_by_text)) + php::count(symbols->body_shape_ids_by_text)) + php::count(symbols->source_unit_key_ids_by_text)));
	row->symbol_lookup_probe_count = __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	__latency_fn_compiler_export_artifacts_write_stage_text(config, string_t("parse_resolve.tsv"), __latency_fn_compiler_export_artifacts_parse_resolve_tsv(symbols));
	if (static_cast<bool>((cast<int_t<>>(row->parser_error_count) > static_cast<int_t<> >(0)))) {
		row->status_id = __latency_fn_compiler_project_runner_blocked_status_id();
		row->completed_layer_id = __latency_fn_structure_row_ids_none_kind_id();
		row->blocked_layer_id = __latency_fn_compiler_project_runner_blocked_layer_frontend_id();
		row->blocked_reason_id = __latency_fn_compiler_project_runner_blocked_reason_frontend_parse_failed_id();
		return row;
	}
	ProjectSymbolIndexRow entrySymbol = __latency_fn_project_symbol_index_function_symbol_by_name(symbols, manifest->entry_function);
	if (static_cast<bool>(php::identical(cast<int_t<>>(entrySymbol->symbol_id), static_cast<int_t<> >(0)))) {
		row->status_id = __latency_fn_compiler_project_runner_blocked_status_id();
		row->completed_layer_id = __latency_fn_structure_row_ids_none_kind_id();
		row->blocked_layer_id = __latency_fn_compiler_project_runner_blocked_layer_symbol_index_id();
		row->blocked_reason_id = __latency_fn_compiler_project_runner_blocked_reason_entry_symbol_missing_id();
		return row;
	}
	shared_p<BackendRequestAuthorizationArtifact> backendRequests = create<BackendRequestAuthorizationArtifact>();
	shared_p<LoweringPlan> plan = create<LoweringPlan>();
	shared_p<CapabilityCoverageArtifact> capabilityCoverage = create<CapabilityCoverageArtifact>();
	__latency_fn_compiler_export_artifacts_write_stage_text(config, string_t("cfg.tsv"), __latency_fn_compiler_export_artifacts_project_cfg_tsv(symbols, entryModel));
	__latency_fn_compiler_export_artifacts_write_stage_text(config, string_t("branch_dataflow.tsv"), __latency_fn_compiler_export_artifacts_branch_dataflow_tsv(symbols, entryModel, entrySource));
	__latency_fn_compiler_export_artifacts_write_stage_text(config, string_t("control_transfer.tsv"), __latency_fn_compiler_export_artifacts_control_transfer_tsv(symbols, entryModel));
	if (static_cast<bool>((__latency_fn_control_flow_dataflows_project_has_blocking_diagnostic(symbols, entryModel, entrySource) || __latency_fn_control_flow_transfers_project_has_blocking_diagnostic(symbols, entryModel)))) {
		row->status_id = __latency_fn_compiler_project_runner_blocked_status_id();
		row->completed_layer_id = __latency_fn_structure_row_ids_none_kind_id();
		row->blocked_layer_id = __latency_fn_compiler_project_runner_blocked_layer_backend_text_id();
		row->blocked_reason_id = __latency_fn_compiler_project_runner_blocked_reason_backend_text_not_ready_id();
		return row;
	}
	BackendEmissionDecisionArtifact emission = __latency_fn_compiler_entry_backend_backend_emission_for_entry(symbols, entrySymbol, entryModel, entrySource, backendRequests, plan, capabilityCoverage);
	FunctionBodyTextEmissionPreflightArtifact preflight = __latency_fn_llvm_text_from_plan_preflight_from_emission(emission);
	string_t moduleText = required_cast<string_t>(__latency_fn_backend_module_composition_module_text_for_entry(symbols, entrySymbol, entryModel, entrySource, emission, backendRequests));
	__latency_fn_compiler_export_artifacts_write_stage_text(config, string_t("lowering.tsv"), __latency_fn_compiler_export_artifacts_lowering_tsv(plan, backendRequests, emission, preflight));
	__latency_fn_compiler_export_artifacts_write_stage_text(config, string_t("llvm_text.ll"), moduleText);
	__latency_fn_output_paths_ensure_parent_and_dir(fs::dirname(config->native_out_dir), config->native_out_dir);
	shared_p<ArtifactWriteReportRecord> writeReport = __latency_fn_artifact_writes_report_from_llvm_text_preflight(config, __latency_fn_llvm_text_from_plan_preflight_by_id(preflight, __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1))), moduleText);
	row->type_ref_count = __latency_fn_structure_row_ids_uint32_from_int(cast<int_t<>>(capabilityCoverage->provider_count));
	row->capability_readiness_count = capabilityCoverage->readiness_count;
	row->backend_request_count = backendRequests->request_count;
	row->backend_request_binary_operand_count = backendRequests->binary_operand_count;
	row->backend_request_local_operand_count = backendRequests->local_operand_count;
	row->backend_request_call_argument_count = backendRequests->call_argument_count;
	row->backend_request_control_flow_operand_count = backendRequests->control_flow_operand_count;
	row->lowering_plan_count = __latency_fn_lowering_plan_publication_plan_row_count(plan);
	row->lowering_step_count = plan->step_count;
	row->lowering_work_ref_count = plan->work_ref_count;
	row->lowering_blocked_request_count = plan->blocked_request_count;
	row->lowering_binary_operand_count = plan->binary_operand_count;
	row->lowering_local_operand_count = plan->local_operand_count;
	row->lowering_call_argument_count = plan->call_argument_count;
	row->lowering_control_flow_operand_count = plan->control_flow_operand_count;
	row->backend_emission_decision_count = emission->decision_count;
	row->backend_emission_value_count = emission->value_count;
	row->backend_emission_value_row_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(emission->values));
	row->backend_emission_block_count = emission->block_count;
	row->backend_emission_binary_operand_count = emission->binary_operand_count;
	row->backend_emission_local_operand_count = emission->local_operand_count;
	row->backend_emission_call_argument_count = emission->call_argument_count;
	row->backend_emission_control_flow_operand_count = emission->control_flow_operand_count;
	row->llvm_sink_boundary_count = __latency_fn_llvm_text_from_plan_publication_sink_boundary_row_count(emission);
	row->llvm_preflight_row_count = preflight->row_count;
	row->llvm_text_blob_count = __latency_fn_llvm_text_from_plan_publication_llvm_text_nonempty_row_count(moduleText);
	row->llvm_text_byte_count = __latency_fn_structure_row_ids_uint32_from_int(str::byte_length(moduleText));
	row->artifact_write_count = __latency_fn_structure_row_ids_uint32_from_int(writeReport->row_count);
	row->reused_write_count = __latency_fn_structure_row_ids_uint32_from_int(writeReport->reused_count);
	row->skipped_write_count = __latency_fn_structure_row_ids_uint32_from_int(writeReport->skipped_count);
	if (static_cast<bool>((php::identical(moduleText, string_t("")) || php::identical(cast<int_t<>>(row->llvm_text_blob_count), static_cast<int_t<> >(0))))) {
		row->status_id = __latency_fn_compiler_project_runner_blocked_status_id();
		row->completed_layer_id = __latency_fn_structure_row_ids_none_kind_id();
		row->blocked_layer_id = __latency_fn_compiler_project_runner_blocked_layer_backend_text_id();
		row->blocked_reason_id = __latency_fn_compiler_project_runner_blocked_reason_backend_text_not_ready_id();
	}
	else {
		if (static_cast<bool>(((writeReport->failed_count > static_cast<int_t<> >(0)) || php::identical((writeReport->written_count + writeReport->reused_count), static_cast<int_t<> >(0))))) {
			row->status_id = __latency_fn_compiler_project_runner_blocked_status_id();
			row->completed_layer_id = __latency_fn_structure_row_ids_none_kind_id();
			row->blocked_layer_id = __latency_fn_compiler_project_runner_blocked_layer_artifact_writes_id();
			row->blocked_reason_id = __latency_fn_compiler_project_runner_blocked_reason_artifact_write_failed_id();
		}
	}
	return row;
}

}
