#include <scpp/lang/php.hpp>
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/ProjectCallableContractArtifact.hpp"
#include "__types/ProjectCallableContractRow.hpp"
#include "__types/ProjectReferenceResolution.hpp"
#include "__types/ProjectReferenceResolutionRow.hpp"
#include "__types/ProjectSymbolFunctionImportRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/ReferenceContractWorkerInput.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__types/TokenStream.hpp"
#include "__counter/create.hpp"
#include "__callable/__latency_fn_project_reference_resolution_append_worker_function_import_snapshot.hpp"
#include "__callable/__latency_fn_project_reference_resolution_append_worker_symbol_snapshot.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_input.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reserve_reference_contract_worker_input.hpp"
#include "__callable/__latency_fn_source_units_row_by_id.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_input.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_inputs.hpp"
#include "__callable/__latency_fn_project_reference_resolution_source_unit_from_reference_contract_worker_input.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_project_reference_resolution_project_symbols_from_reference_contract_worker_input.hpp"
#include "__callable/__latency_fn_project_reference_resolution_snapshot_int_at.hpp"
#include "__callable/__latency_fn_project_reference_resolution_snapshot_string_at.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_function_import_by_names.hpp"
#include "__callable/__latency_fn_project_symbol_index_intern_body_shape.hpp"
#include "__callable/__latency_fn_project_symbol_index_intern_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_intern_qualified_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_intern_signature_shape.hpp"
#include "__callable/__latency_fn_project_symbol_index_intern_source_unit_key.hpp"
#include "__callable/__latency_fn_project_symbol_index_new_index.hpp"
#include "__callable/__latency_fn_project_symbol_index_record_function_name_symbol.hpp"
#include "__callable/__latency_fn_project_symbol_index_type_ref_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_update_lookup_policy.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_source_unit.hpp"
#include "__callable/__latency_fn_phs_tokenizer_from_source_text.hpp"
#include "__callable/__latency_fn_project_callable_contracts_append_from_reference.hpp"
#include "__callable/__latency_fn_project_callable_contracts_new_artifact.hpp"
#include "__callable/__latency_fn_project_reference_resolution_append_direct_call_from_frontend.hpp"
#include "__callable/__latency_fn_project_reference_resolution_new_artifact.hpp"
#include "__callable/__latency_fn_project_reference_resolution_project_symbols_from_reference_contract_worker_input.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_descriptor_for_input.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_contract_worker_descriptor_parser_error_scale.hpp"
#include "__callable/__latency_fn_project_reference_resolution_semantic_hash_for_reference_contracts.hpp"
#include "__callable/__latency_fn_project_reference_resolution_source_unit_from_reference_contract_worker_input.hpp"
#include "__callable/__latency_fn_project_reference_resolution_status_resolved_id.hpp"
#include "__callable/__latency_fn_project_symbol_index_row_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
shared_p<ReferenceContractWorkerInput> __latency_fn_project_reference_resolution_reference_contract_worker_input(shared_p<SourceUnitTable> sourceUnits, shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow entrySymbol, const string_t& entryText) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::reference_contract_worker_input", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[66]);
	SourceUnitTableRow sourceUnit = __latency_fn_source_units_row_by_id(sourceUnits, entrySymbol->source_unit_id);
	shared_p<ReferenceContractWorkerInput> input = create<ReferenceContractWorkerInput>();
	input->source_unit_id = cast<int_t<>>(sourceUnit->source_unit_id);
	input->language_id = cast<int_t<>>(sourceUnit->language_id);
	input->status_id = cast<int_t<>>(sourceUnit->status_id);
	input->dirty_signal_id = cast<int_t<>>(sourceUnit->dirty_signal_id);
	input->reuse_signal_id = cast<int_t<>>(sourceUnit->reuse_signal_id);
	input->partition_id = cast<int_t<>>(sourceUnit->partition_id);
	input->source_unit_key_id = cast<int_t<>>(sourceUnit->source_unit_key_id);
	input->relative_path_id = cast<int_t<>>(sourceUnit->relative_path_id);
	input->path_id = cast<int_t<>>(sourceUnit->path_id);
	input->source_length = cast<int_t<>>(sourceUnit->source_length);
	input->line_count = cast<int_t<>>(sourceUnit->line_count);
	input->entry_symbol_id = cast<int_t<>>(entrySymbol->symbol_id);
	input->source_text = entryText;
	__latency_fn_project_reference_resolution_reserve_reference_contract_worker_input(input, cast<int_t<>>(symbols->symbol_count));
	auto __latency_local_0 = symbols->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		__latency_fn_project_reference_resolution_append_worker_symbol_snapshot(input, symbols, row);
	}
	auto __latency_local_2 = symbols->function_import_rows;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto importRow = __latency_local_3.value_copy();
		__latency_fn_project_reference_resolution_append_worker_function_import_snapshot(input, symbols, importRow);
	}
	return input;
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
vector_t<shared_p<ReferenceContractWorkerInput>> __latency_fn_project_reference_resolution_reference_contract_worker_inputs(shared_p<SourceUnitTable> sourceUnits, shared_p<ProjectSymbolIndex> symbols, ProjectSymbolIndexRow entrySymbol, const string_t& entryText) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::reference_contract_worker_inputs", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[67]);
	vector_t<shared_p<ReferenceContractWorkerInput>> inputs = {};
	php::vector_reserve(inputs, static_cast<int_t<> >(1));
	{
	auto __latency_local_0 = __latency_fn_project_reference_resolution_reference_contract_worker_input(sourceUnits, symbols, entrySymbol, entryText);
	(void) inputs.push_back(__latency_local_0);
	}
	return inputs;
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
SourceUnitTableRow __latency_fn_project_reference_resolution_source_unit_from_reference_contract_worker_input(shared_p<ReferenceContractWorkerInput> input) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::source_unit_from_reference_contract_worker_input", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[68]);
	SourceUnitTableRow row = SourceUnitTableRow{};
	row->source_unit_id = __latency_fn_structure_row_ids_uint32_from_int(input->source_unit_id);
	row->language_id = __latency_fn_structure_row_ids_uint16_from_int(input->language_id);
	row->status_id = __latency_fn_structure_row_ids_uint16_from_int(input->status_id);
	row->dirty_signal_id = __latency_fn_structure_row_ids_uint16_from_int(input->dirty_signal_id);
	row->reuse_signal_id = __latency_fn_structure_row_ids_uint16_from_int(input->reuse_signal_id);
	row->partition_id = __latency_fn_structure_row_ids_uint16_from_int(input->partition_id);
	row->source_unit_key_id = __latency_fn_structure_row_ids_uint32_from_int(input->source_unit_key_id);
	row->relative_path_id = __latency_fn_structure_row_ids_uint32_from_int(input->relative_path_id);
	row->path_id = __latency_fn_structure_row_ids_uint32_from_int(input->path_id);
	row->source_length = __latency_fn_structure_row_ids_uint32_from_int(input->source_length);
	row->line_count = __latency_fn_structure_row_ids_uint32_from_int(input->line_count);
	return row;
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
shared_p<ProjectSymbolIndex> __latency_fn_project_reference_resolution_project_symbols_from_reference_contract_worker_input(shared_p<ReferenceContractWorkerInput> input) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::project_symbols_from_reference_contract_worker_input", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[69]);
	shared_p<ProjectSymbolIndex> symbols = __latency_fn_project_symbol_index_new_index(php::count(input->symbol_ids));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < php::count(input->symbol_ids)))) {
		string_t name = required_cast<string_t>(__latency_fn_project_reference_resolution_snapshot_string_at(input->symbol_names, index));
		string_t qualifiedName = required_cast<string_t>(__latency_fn_project_reference_resolution_snapshot_string_at(input->symbol_qualified_names, index));
		if (static_cast<bool>(php::identical(qualifiedName, string_t("")))) {
			qualifiedName = name;
		}
		string_t sourceUnitKey = required_cast<string_t>(__latency_fn_project_reference_resolution_snapshot_string_at(input->symbol_source_unit_keys, index));
		if (static_cast<bool>(php::identical(sourceUnitKey, string_t("")))) {
			sourceUnitKey = (string_t("source_unit_id:") + cast<string_t>(__latency_fn_project_reference_resolution_snapshot_int_at(input->symbol_source_unit_ids, index)));
		}
		int_t<> returnTypeRefId = required_cast<int_t<>>(__latency_fn_project_reference_resolution_snapshot_int_at(input->symbol_return_type_ref_ids, index));
		string_t signatureShape = required_cast<string_t>(__latency_fn_project_reference_resolution_snapshot_string_at(input->symbol_signature_shapes, index));
		if (static_cast<bool>(php::identical(signatureShape, string_t("")))) {
			signatureShape = (string_t("function ") + cast<string_t>(name) + string_t("():") + cast<string_t>(__latency_fn_project_symbol_index_type_ref_name(__latency_fn_structure_row_ids_uint32_from_int(returnTypeRefId))));
		}
		ProjectSymbolIndexRow row = ProjectSymbolIndexRow{};
		row->symbol_id = __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_project_reference_resolution_snapshot_int_at(input->symbol_ids, index));
		row->source_unit_id = __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_project_reference_resolution_snapshot_int_at(input->symbol_source_unit_ids, index));
		row->source_row_id = __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_project_reference_resolution_snapshot_int_at(input->symbol_source_row_ids, index));
		row->symbol_kind_id = __latency_fn_structure_row_ids_uint16_from_int(__latency_fn_project_reference_resolution_snapshot_int_at(input->symbol_kind_ids, index));
		row->scope_id = __latency_fn_structure_row_ids_uint16_from_int(__latency_fn_project_reference_resolution_snapshot_int_at(input->symbol_scope_ids, index));
		row->status_id = __latency_fn_structure_row_ids_uint16_from_int(__latency_fn_project_reference_resolution_snapshot_int_at(input->symbol_status_ids, index));
		row->name_id = __latency_fn_project_symbol_index_intern_name(symbols, name);
		row->qualified_name_id = __latency_fn_project_symbol_index_intern_qualified_name(symbols, qualifiedName);
		row->return_type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(returnTypeRefId);
		row->signature_shape_id = __latency_fn_project_symbol_index_intern_signature_shape(symbols, signatureShape);
		row->body_shape_id = __latency_fn_project_symbol_index_intern_body_shape(symbols, __latency_fn_project_reference_resolution_snapshot_string_at(input->symbol_body_shapes, index));
		row->source_unit_key_id = __latency_fn_project_symbol_index_intern_source_unit_key(symbols, sourceUnitKey);
		row->parameter_count = __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_project_reference_resolution_snapshot_int_at(input->symbol_parameter_counts, index));
		row->first_parameter_type_ref_id = __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_project_reference_resolution_snapshot_int_at(input->symbol_first_parameter_type_ref_ids, index));
		__latency_fn_project_symbol_index_record_function_name_symbol(symbols, row);
		(void) symbols->rows.append(row);
		index = (index + static_cast<int_t<> >(1));
	}
	int_t<> importIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((importIndex < php::count(input->function_import_target_qualified_names)))) {
		int_t<> sourceUnitId = required_cast<int_t<>>(__latency_fn_project_reference_resolution_snapshot_int_at(input->function_import_source_unit_ids, importIndex));
		if (static_cast<bool>(php::identical(sourceUnitId, static_cast<int_t<> >(0)))) {
			sourceUnitId = input->source_unit_id;
		}
		__latency_fn_project_symbol_index_append_function_import_by_names(symbols, __latency_fn_structure_row_ids_uint32_from_int(sourceUnitId), __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_project_reference_resolution_snapshot_int_at(input->function_import_source_row_ids, importIndex)), __latency_fn_project_reference_resolution_snapshot_string_at(input->function_import_namespaces, importIndex), __latency_fn_project_reference_resolution_snapshot_string_at(input->function_import_alias_names, importIndex), __latency_fn_project_reference_resolution_snapshot_string_at(input->function_import_target_qualified_names, importIndex));
		importIndex = (importIndex + static_cast<int_t<> >(1));
	}
	symbols->symbol_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(symbols->rows));
	__latency_fn_project_symbol_index_update_lookup_policy(symbols);
	return symbols;
}

}

namespace scpp { extern const int __latency_lines_project_reference_resolution[]; }
namespace scpp {
int_t<> __latency_fn_project_reference_resolution_reference_contract_worker_descriptor_for_input(shared_p<ReferenceContractWorkerInput> input) {
	SCPP_CALL_DEPTH_GUARD("project_reference_resolution::reference_contract_worker_descriptor_for_input", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_reference_resolution.phs", __latency_lines_project_reference_resolution[70]);
	shared_p<ProjectSymbolIndex> symbols = __latency_fn_project_reference_resolution_project_symbols_from_reference_contract_worker_input(input);
	ProjectSymbolIndexRow entrySymbol = __latency_fn_project_symbol_index_row_by_id(symbols, __latency_fn_structure_row_ids_uint32_from_int(input->entry_symbol_id));
	shared_p<ProjectReferenceResolution> references = __latency_fn_project_reference_resolution_new_artifact(static_cast<int_t<> >(1));
	shared_p<ProjectCallableContractArtifact> contracts = __latency_fn_project_callable_contracts_new_artifact(static_cast<int_t<> >(1));
	int_t<> parserErrors = required_cast<int_t<>>(static_cast<int_t<> >(0));
	string_t fallbackSourceText = required_cast<string_t>(input->source_text);
	shared_p<TokenStream> tokens = __latency_fn_phs_tokenizer_from_source_text(__latency_fn_structure_row_ids_uint32_from_int(input->source_unit_id), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)), fallbackSourceText);
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	SourceUnitTableRow sourceUnit = __latency_fn_project_reference_resolution_source_unit_from_reference_contract_worker_input(input);
	shared_p<FrontendModel> model = __latency_fn_frontend_model_builder_parse_source_unit(sourceUnit, fallbackSourceText, tokens, counters);
	ProjectReferenceResolutionRow reference = __latency_fn_project_reference_resolution_append_direct_call_from_frontend(references, symbols, model, fallbackSourceText, entrySymbol);
	parserErrors = cast<int_t<>>(model->parser_error_count);
	if (static_cast<bool>(php::identical(cast<int_t<>>(reference->status_id), cast<int_t<>>(__latency_fn_project_reference_resolution_status_resolved_id())))) {
		__latency_fn_project_callable_contracts_append_from_reference(contracts, reference, references, symbols);
	}
	if (static_cast<bool>(php::condition_truthy((parserErrors >= __latency_fn_project_reference_resolution_reference_contract_worker_descriptor_parser_error_scale())))) {
		parserErrors = (__latency_fn_project_reference_resolution_reference_contract_worker_descriptor_parser_error_scale() - static_cast<int_t<> >(1));
	}
	return ((cast<int_t<>>(__latency_fn_project_reference_resolution_semantic_hash_for_reference_contracts(references, contracts)) * __latency_fn_project_reference_resolution_reference_contract_worker_descriptor_parser_error_scale()) + parserErrors);
}

}
