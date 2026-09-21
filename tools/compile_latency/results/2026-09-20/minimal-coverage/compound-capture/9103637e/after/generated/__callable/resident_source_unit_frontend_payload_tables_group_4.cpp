#include <scpp/lang/php.hpp>
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/FrontendExpressionPayloadRow.hpp"
#include "__types/FrontendLiteralPayloadRow.hpp"
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/FrontendNamePayloadRow.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendStatementPayloadRow.hpp"
#include "__types/FrontendTypeSyntaxPayloadRow.hpp"
#include "__types/LineStartRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/SourceRangeExtRow.hpp"
#include "__types/SourceRangeRow.hpp"
#include "__types/SourceUnitFrontendWorkerBuildResult.hpp"
#include "__types/SourceUnitFrontendWorkerFrontendPayloadCarrier.hpp"
#include "__types/SourceUnitFrontendWorkerPayloadInput.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__types/TokenExtendedLengthRow.hpp"
#include "__types/TokenRow.hpp"
#include "__types/TokenStream.hpp"
#include "__types/source_units.hpp"
#include "__counter/create.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_shadow_source_unit.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_shadow_source_unit_from_input.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_source_unit.hpp"
#include "__callable/__latency_fn_phs_tokenizer_from_source_text.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_source_text_for_worker_input.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_for_input.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_frontend_node_scale.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_token_row_scale.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_shadow_source_unit_from_input.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_estimated_frontend_result_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_project_symbol_index_sidecar_lookup_entry_count.hpp"
#include "__callable/__latency_fn_project_symbol_index_sidecar_string_byte_count.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_estimated_symbol_result_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_segment_count.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_segment_reserved_bytes.hpp"
#include "__callable/__latency_fn_frontend_model_tables_node_segment_slack_bytes.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_payload_carrier_from_model.hpp"
#include "__callable/__latency_fn_token_tables_reserved_segment_bytes.hpp"
#include "__callable/__latency_fn_token_tables_segment_count.hpp"
#include "__callable/__latency_fn_token_tables_segment_slack_bytes.hpp"
#include "__callable/__latency_fn_compiler_profile_events_elapsed_us_since.hpp"
#include "__callable/__latency_fn_compiler_profile_events_now_us.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_source_unit.hpp"
#include "__callable/__latency_fn_phs_tokenizer_from_source_text.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_from_declaration_sidecar.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_from_model_by_node_scan.hpp"
#include "__callable/__latency_fn_project_symbol_index_new_index.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_estimated_frontend_result_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_estimated_symbol_result_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_source_text_for_worker_input.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_source_unit_frontend_worker_local_symbol_publication_enabled.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_source_unit_frontend_worker_retain_token_result_enabled.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_build_result_for_input.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_payload_carrier_from_model.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_result_full_model_debug_retention_enabled.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_shadow_source_unit_from_input.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
SourceUnitTableRow __latency_fn_resident_source_unit_frontend_payload_tables_worker_shadow_source_unit(SourceUnitTableRow sourceUnit, const string_t& sourceText) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_shadow_source_unit", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[54]);
	SourceUnitTableRow row = SourceUnitTableRow{};
	row->source_unit_id = sourceUnit->source_unit_id;
	row->language_id = sourceUnit->language_id;
	row->status_id = sourceUnit->status_id;
	row->dirty_signal_id = sourceUnit->dirty_signal_id;
	row->reuse_signal_id = sourceUnit->reuse_signal_id;
	row->partition_id = sourceUnit->partition_id;
	row->source_unit_key_id = sourceUnit->source_unit_key_id;
	row->relative_path_id = sourceUnit->relative_path_id;
	row->path_id = sourceUnit->path_id;
	row->source_length = sourceUnit->source_length;
	row->line_count = sourceUnit->line_count;
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
SourceUnitTableRow __latency_fn_resident_source_unit_frontend_payload_tables_worker_shadow_source_unit_from_input(shared_p<SourceUnitFrontendWorkerPayloadInput> input) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_shadow_source_unit_from_input", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[55]);
	SourceUnitTableRow row = SourceUnitTableRow{};
	row->source_unit_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(input->source_unit_id);
	row->language_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint16_from_int(input->language_id);
	row->status_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint16_from_int(input->status_id);
	row->dirty_signal_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint16_from_int(input->dirty_signal_id);
	row->reuse_signal_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint16_from_int(input->reuse_signal_id);
	row->partition_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint16_from_int(input->partition_id);
	row->source_unit_key_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(input->source_unit_key_id);
	row->relative_path_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(input->relative_path_id);
	row->path_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(input->path_id);
	row->source_length = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(input->source_length);
	row->line_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(input->line_count);
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_for_input(shared_p<SourceUnitFrontendWorkerPayloadInput> input) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_payload_descriptor_for_input", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[56]);
	string_t sourceText = required_cast<string_t>(__latency_fn_resident_source_unit_frontend_payload_tables_source_text_for_worker_input(input));
	shared_p<TokenStream> tokens = __latency_fn_phs_tokenizer_from_source_text(__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(input->source_unit_id), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)), sourceText);
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	SourceUnitTableRow sourceUnit = __latency_fn_resident_source_unit_frontend_payload_tables_worker_shadow_source_unit_from_input(input);
	shared_p<FrontendModel> model = __latency_fn_frontend_model_builder_parse_source_unit(sourceUnit, sourceText, tokens, counters);
	return (((cast<int_t<>>(tokens->token_count) * __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_token_row_scale()) + (cast<int_t<>>(model->node_count) * __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_frontend_node_scale())) + cast<int_t<>>(model->parser_error_count));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_estimated_frontend_result_payload_copy_bytes(shared_p<TokenStream> tokens, shared_p<FrontendModel> model) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::estimated_frontend_result_payload_copy_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[57]);
	return (((((((((((((cast<int_t<>>(tokens->token_count) * static_cast<int_t<> >(sizeof(TokenRow))) + (cast<int_t<>>(php::count(tokens->extended_lengths)) * static_cast<int_t<> >(sizeof(TokenExtendedLengthRow)))) + (cast<int_t<>>(model->node_count) * static_cast<int_t<> >(sizeof(FrontendNodeRow)))) + (cast<int_t<>>(php::count(model->source_ranges)) * static_cast<int_t<> >(sizeof(SourceRangeRow)))) + (cast<int_t<>>(php::count(model->source_range_exts)) * static_cast<int_t<> >(sizeof(SourceRangeExtRow)))) + (cast<int_t<>>(php::count(model->line_starts)) * static_cast<int_t<> >(sizeof(LineStartRow)))) + (cast<int_t<>>(php::count(model->declaration_node_ids)) * static_cast<int_t<> >(4))) + (cast<int_t<>>(php::count(model->declarations)) * static_cast<int_t<> >(sizeof(FrontendDeclarationPayloadRow)))) + (cast<int_t<>>(php::count(model->statements)) * static_cast<int_t<> >(sizeof(FrontendStatementPayloadRow)))) + (cast<int_t<>>(php::count(model->expressions)) * static_cast<int_t<> >(sizeof(FrontendExpressionPayloadRow)))) + (cast<int_t<>>(php::count(model->type_syntaxes)) * static_cast<int_t<> >(sizeof(FrontendTypeSyntaxPayloadRow)))) + (cast<int_t<>>(php::count(model->literals)) * static_cast<int_t<> >(sizeof(FrontendLiteralPayloadRow)))) + (cast<int_t<>>(php::count(model->names)) * static_cast<int_t<> >(sizeof(FrontendNamePayloadRow))));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_estimated_symbol_result_payload_copy_bytes(shared_p<ProjectSymbolIndex> symbols) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::estimated_symbol_result_payload_copy_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[58]);
	return (((cast<int_t<>>(symbols->symbol_count) * static_cast<int_t<> >(sizeof(ProjectSymbolIndexRow))) + cast<int_t<>>(__latency_fn_project_symbol_index_sidecar_string_byte_count(symbols))) + (cast<int_t<>>(__latency_fn_project_symbol_index_sidecar_lookup_entry_count(symbols)) * static_cast<int_t<> >(4)));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
shared_p<SourceUnitFrontendWorkerFrontendPayloadCarrier> __latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_payload_carrier_from_model(int_t<> sourceUnitId, shared_p<TokenStream> tokens, shared_p<FrontendModel> model) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_frontend_payload_carrier_from_model", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[59]);
	shared_p<SourceUnitFrontendWorkerFrontendPayloadCarrier> carrier = create<SourceUnitFrontendWorkerFrontendPayloadCarrier>();
	carrier->source_unit_id = sourceUnitId;
	carrier->token_count = cast<int_t<>>(tokens->token_count);
	carrier->token_segment_count = cast<int_t<>>(__latency_fn_token_tables_segment_count(tokens));
	carrier->token_reserved_segment_bytes = cast<int_t<>>(__latency_fn_token_tables_reserved_segment_bytes(tokens));
	carrier->token_segment_slack_bytes = cast<int_t<>>(__latency_fn_token_tables_segment_slack_bytes(tokens));
	carrier->frontend_node_count = cast<int_t<>>(model->node_count);
	carrier->frontend_node_segment_count = cast<int_t<>>(__latency_fn_frontend_model_tables_node_segment_count(model));
	carrier->frontend_node_reserved_segment_bytes = cast<int_t<>>(__latency_fn_frontend_model_tables_node_segment_reserved_bytes(model));
	carrier->frontend_node_segment_slack_bytes = cast<int_t<>>(__latency_fn_frontend_model_tables_node_segment_slack_bytes(model));
	carrier->declaration_count = cast<int_t<>>(model->declaration_count);
	carrier->statement_count = cast<int_t<>>(model->statement_count);
	carrier->expression_count = cast<int_t<>>(model->expression_count);
	carrier->parser_error_count = cast<int_t<>>(model->parser_error_count);
	return carrier;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
shared_p<SourceUnitFrontendWorkerBuildResult> __latency_fn_resident_source_unit_frontend_payload_tables_worker_build_result_for_input(shared_p<SourceUnitFrontendWorkerPayloadInput> input) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_build_result_for_input", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[60]);
	shared_p<SourceUnitFrontendWorkerBuildResult> result = create<SourceUnitFrontendWorkerBuildResult>();
	result->source_unit_id = input->source_unit_id;
	string_t sourceText = required_cast<string_t>(__latency_fn_resident_source_unit_frontend_payload_tables_source_text_for_worker_input(input));
	int_t<std::uint64_t> tokenizerStarted = required_cast<int_t<std::uint64_t>>(__latency_fn_compiler_profile_events_now_us());
	result->tokens = __latency_fn_phs_tokenizer_from_source_text(__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(input->source_unit_id), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)), sourceText);
	result->tokenizer_elapsed_us = cast<int_t<>>(__latency_fn_compiler_profile_events_elapsed_us_since(tokenizerStarted));
	int_t<std::uint64_t> parserStarted = required_cast<int_t<std::uint64_t>>(__latency_fn_compiler_profile_events_now_us());
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	SourceUnitTableRow sourceUnit = __latency_fn_resident_source_unit_frontend_payload_tables_worker_shadow_source_unit_from_input(input);
	shared_p<FrontendModel> model = __latency_fn_frontend_model_builder_parse_source_unit(sourceUnit, sourceText, result->tokens, counters);
	result->parser_elapsed_us = cast<int_t<>>(__latency_fn_compiler_profile_events_elapsed_us_since(parserStarted));
	result->frontend_carrier = __latency_fn_resident_source_unit_frontend_payload_tables_worker_frontend_payload_carrier_from_model(input->source_unit_id, result->tokens, model);
	bool_t localSymbolPublicationEnabled = required_cast<bool_t>(__latency_fn_resident_source_unit_frontend_payload_tables_source_unit_frontend_worker_local_symbol_publication_enabled());
	if (static_cast<bool>(php::condition_truthy(localSymbolPublicationEnabled))) {
		int_t<std::uint64_t> symbolStarted = required_cast<int_t<std::uint64_t>>(__latency_fn_compiler_profile_events_now_us());
		result->symbols = __latency_fn_project_symbol_index_new_index(cast<int_t<>>(result->frontend_carrier->declaration_count));
		if (static_cast<bool>(php::condition_truthy(__latency_fn_project_symbol_index_append_from_declaration_sidecar(result->symbols, input->source_units, sourceUnit, sourceText, model)))) {
			result->symbol_declaration_sidecar_selected = static_cast<int_t<> >(1);
		}
		else {
			result->symbol_model_fallback_selected = static_cast<int_t<> >(1);
			__latency_fn_project_symbol_index_append_from_model_by_node_scan(result->symbols, input->source_units, sourceUnit, sourceText, model);
		}
		result->symbol_worker_elapsed_us = cast<int_t<>>(__latency_fn_compiler_profile_events_elapsed_us_since(symbolStarted));
		result->symbol_count = cast<int_t<>>(result->symbols->symbol_count);
		result->symbol_result_ready = static_cast<int_t<> >(1);
	}
	if (static_cast<bool>((!__latency_fn_resident_source_unit_frontend_payload_tables_source_unit_frontend_worker_retain_token_result_enabled()))) {
		result->tokens = create<TokenStream>();
	}
	if (static_cast<bool>(((!localSymbolPublicationEnabled) || __latency_fn_resident_source_unit_frontend_payload_tables_worker_result_full_model_debug_retention_enabled()))) {
		result->model = model;
	}
	result->result_payload_copy_bytes = __latency_fn_resident_source_unit_frontend_payload_tables_estimated_frontend_result_payload_copy_bytes(result->tokens, result->model);
	if (static_cast<bool>(php::condition_truthy(localSymbolPublicationEnabled))) {
		result->result_payload_copy_bytes = (result->result_payload_copy_bytes + __latency_fn_resident_source_unit_frontend_payload_tables_estimated_symbol_result_payload_copy_bytes(result->symbols));
	}
	return result;
}

}
