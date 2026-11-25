#include <scpp/lang/php.hpp>
#include "__types/FrontendModel.hpp"
#include "__types/FrontendModelKernelCounters.hpp"
#include "__types/PartitionReadinessArtifact.hpp"
#include "__types/PartitionReadinessRow.hpp"
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/SourceUnitFrontendWorkerPayloadInput.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__types/TokenStream.hpp"
#include "__counter/create.hpp"
#include "__callable/__latency_fn_partition_readiness_new_artifact.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_append_symbol_fact_publication_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_publication_artifact.hpp"
#include "__callable/__latency_fn_partition_readiness_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_publication_published_row_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_symbol_scale.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_declaration_scale.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_source_unit_table_for_worker_input.hpp"
#include "__callable/__latency_fn_source_units_reserve_table.hpp"
#include "__callable/__latency_fn_source_units_scope_project_manifest_sources_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_parse_source_unit.hpp"
#include "__callable/__latency_fn_phs_tokenizer_from_source_text.hpp"
#include "__callable/__latency_fn_project_symbol_index_append_from_model.hpp"
#include "__callable/__latency_fn_project_symbol_index_new_index.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_source_text_for_worker_input.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_shadow_source_unit_from_input.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_source_unit_table_for_worker_input.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_declaration_scale.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_for_input.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_symbol_scale.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_symbol_scale.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_symbols.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_declaration_scale.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_declarations.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_symbol_scale.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_declaration_scale.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_parser_errors.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_input_from_source_units.hpp"
#include "__callable/__latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_inputs.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
shared_p<PartitionReadinessArtifact> __latency_fn_resident_source_unit_symbol_states_symbol_fact_publication_artifact(shared_p<SourceUnitTable> sourceUnits, int_t<std::uint32_t> ownerRunId, const vector_t<int_t<std::uint32_t>>& firstSymbolIds, const vector_t<int_t<std::uint32_t>>& symbolCounts, bool_t simulatedOrder) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::symbol_fact_publication_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[21]);
	shared_p<PartitionReadinessArtifact> artifact = __latency_fn_partition_readiness_new_artifact(cast<int_t<>>(sourceUnits->source_unit_count));
	if (static_cast<bool>(php::condition_truthy(simulatedOrder))) {
		int_t<> index = required_cast<int_t<>>((php::count(sourceUnits->rows) - static_cast<int_t<> >(1)));
		while (static_cast<bool>(php::condition_truthy((index >= static_cast<int_t<> >(0))))) {
			__latency_fn_resident_source_unit_symbol_states_append_symbol_fact_publication_row(artifact, sourceUnits->rows[index], cast<int_t<std::uint32_t>>(ownerRunId), firstSymbolIds, symbolCounts);
			index = (index - static_cast<int_t<> >(1));
		}
		return artifact;
	}
	auto __latency_local_0 = sourceUnits->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto sourceUnit = __latency_local_1.value_copy();
		__latency_fn_resident_source_unit_symbol_states_append_symbol_fact_publication_row(artifact, sourceUnit, cast<int_t<std::uint32_t>>(ownerRunId), firstSymbolIds, symbolCounts);
	}
	return artifact;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_source_unit_symbol_states_symbol_fact_publication_published_row_total(shared_p<PartitionReadinessArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::symbol_fact_publication_published_row_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[22]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = artifact->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_partition_readiness_status_ready_id())))) {
			total = (total + cast<int_t<>>(row->published_row_count));
		}
	}
	return __latency_fn_resident_source_unit_symbol_states_uint32_from_int(total);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_symbol_scale() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::symbol_fact_worker_descriptor_symbol_scale", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[23]);
	return static_cast<int_t<> >(1000000000);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_declaration_scale() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::symbol_fact_worker_descriptor_declaration_scale", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[24]);
	return static_cast<int_t<> >(100);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
shared_p<SourceUnitTable> __latency_fn_resident_source_unit_symbol_states_source_unit_table_for_worker_input(shared_p<SourceUnitFrontendWorkerPayloadInput> input, SourceUnitTableRow sourceUnit, const string_t& sourceText) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::source_unit_table_for_worker_input", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[25]);
	shared_p<SourceUnitTable> table = create<SourceUnitTable>();
	table->scope_id = __latency_fn_source_units_scope_project_manifest_sources_id();
	__latency_fn_source_units_reserve_table(table, static_cast<int_t<> >(1));
	table->entry_source_unit_key = (string_t("source_unit_key_id:") + cast<string_t>(input->source_unit_key_id));
	{
	auto __latency_local_0 = table->entry_source_unit_key;
	(void) table->source_unit_keys.append(__latency_local_0);
	}
	{
	auto __latency_local_1 = (string_t("relative_path_id:") + cast<string_t>(input->relative_path_id));
	(void) table->relative_paths.append(__latency_local_1);
	}
	{
	auto __latency_local_2 = (string_t("path_id:") + cast<string_t>(input->path_id));
	(void) table->paths.append(__latency_local_2);
	}
	(void) table->source_texts.append(sourceText);
	(void) table->rows.append(sourceUnit);
	table->source_unit_count = static_cast<int_t<> >(1);
	return table;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_for_input(shared_p<SourceUnitFrontendWorkerPayloadInput> input) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::symbol_fact_worker_descriptor_for_input", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[26]);
	string_t sourceText = required_cast<string_t>(__latency_fn_resident_source_unit_frontend_payload_tables_source_text_for_worker_input(input));
	shared_p<TokenStream> tokens = __latency_fn_phs_tokenizer_from_source_text(__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(input->source_unit_id), __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1)), sourceText);
	shared_p<FrontendModelKernelCounters> counters = __latency_counter_create();
	SourceUnitTableRow sourceUnit = __latency_fn_resident_source_unit_frontend_payload_tables_worker_shadow_source_unit_from_input(input);
	shared_p<FrontendModel> model = __latency_fn_frontend_model_builder_parse_source_unit(sourceUnit, sourceText, tokens, counters);
	shared_p<SourceUnitTable> sourceUnits = __latency_fn_resident_source_unit_symbol_states_source_unit_table_for_worker_input(input, sourceUnit, sourceText);
	shared_p<ProjectSymbolIndex> symbols = __latency_fn_project_symbol_index_new_index(cast<int_t<>>(model->declaration_count));
	__latency_fn_project_symbol_index_append_from_model(symbols, sourceUnits, sourceUnit, sourceText, model);
	return (((cast<int_t<>>(symbols->symbol_count) * __latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_symbol_scale()) + (cast<int_t<>>(model->declaration_count) * __latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_declaration_scale())) + cast<int_t<>>(model->parser_error_count));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_symbols(int_t<> descriptor) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::symbol_fact_worker_descriptor_symbols", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[27]);
	return cast<int_t<>>((descriptor / __latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_symbol_scale()));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_declarations(int_t<> descriptor) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::symbol_fact_worker_descriptor_declarations", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[28]);
	return cast<int_t<>>(((descriptor % __latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_symbol_scale()) / __latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_declaration_scale()));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_parser_errors(int_t<> descriptor) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::symbol_fact_worker_descriptor_parser_errors", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[29]);
	return (descriptor % __latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_descriptor_declaration_scale());
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_symbol_states[]; }
namespace scpp {
vector_t<shared_p<SourceUnitFrontendWorkerPayloadInput>> __latency_fn_resident_source_unit_symbol_states_symbol_fact_worker_inputs(shared_p<SourceUnitTable> sourceUnits) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_symbol_states::symbol_fact_worker_inputs", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_symbol_states.phs", __latency_lines_resident_source_unit_symbol_states[30]);
	vector_t<shared_p<SourceUnitFrontendWorkerPayloadInput>> inputs = {};
	php::vector_reserve(inputs, cast<int_t<>>(sourceUnits->source_unit_count));
	auto __latency_local_0 = sourceUnits->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto sourceUnit = __latency_local_1.value_copy();
		{
		auto __latency_local_2 = __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_input_from_source_units(sourceUnits, sourceUnit);
		(void) inputs.push_back(__latency_local_2);
		}
	}
	return inputs;
}

}
