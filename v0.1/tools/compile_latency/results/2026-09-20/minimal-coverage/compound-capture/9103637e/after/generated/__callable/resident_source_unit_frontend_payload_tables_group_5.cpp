#include <scpp/lang/php.hpp>
#include "__types/ResidentSourceUnitFrontendPayloadTableRow.hpp"
#include "__types/SourceUnitFrontendWorkerPayloadInput.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__types/source_units.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_token_row_scale.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_frontend_node_scale.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_token_row_scale.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_token_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_frontend_node_scale.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_frontend_nodes.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_token_row_scale.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_segment_count_for_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_shadow_physical_segment_capacity.hpp"
#include "__callable/__latency_fn_row_segment_policy_segment_count_for_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_segment_count_for_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_token_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_token_segments.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_frontend_node_segments.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_frontend_nodes.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_segment_count_for_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_frontend_node_scale.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_parser_errors.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_input.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_input.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_input_from_source_units.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_source_text_for_worker_input.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_input_from_source_units.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_inputs_for_payload_tables.hpp"
#include "__callable/__latency_fn_source_units_row_by_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_input_from_source_units.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_inputs_for_source_units.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_token_row_scale() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_payload_descriptor_token_row_scale", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[61]);
	return static_cast<int_t<> >(1000000000);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_frontend_node_scale() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_payload_descriptor_frontend_node_scale", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[62]);
	return static_cast<int_t<> >(100);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_token_rows(int_t<> descriptor) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_payload_descriptor_token_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[63]);
	return cast<int_t<>>((descriptor / __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_token_row_scale()));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_frontend_nodes(int_t<> descriptor) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_payload_descriptor_frontend_nodes", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[64]);
	return cast<int_t<>>(((descriptor % __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_token_row_scale()) / __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_frontend_node_scale()));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_segment_count_for_rows(int_t<> rowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_payload_descriptor_segment_count_for_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[65]);
	return __latency_fn_row_segment_policy_segment_count_for_rows(rowCount, cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_worker_shadow_physical_segment_capacity()));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_token_segments(int_t<> descriptor) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_payload_descriptor_token_segments", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[66]);
	return __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_segment_count_for_rows(__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_token_rows(descriptor));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_frontend_node_segments(int_t<> descriptor) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_payload_descriptor_frontend_node_segments", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[67]);
	return __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_segment_count_for_rows(__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_frontend_nodes(descriptor));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_parser_errors(int_t<> descriptor) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_payload_descriptor_parser_errors", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[68]);
	return (descriptor % __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_frontend_node_scale());
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
shared_p<SourceUnitFrontendWorkerPayloadInput> __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_input(SourceUnitTableRow sourceUnit, const string_t& sourceText) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_payload_input", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[69]);
	shared_p<SourceUnitFrontendWorkerPayloadInput> input = create<SourceUnitFrontendWorkerPayloadInput>();
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
	input->source_text = sourceText;
	return input;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
shared_p<SourceUnitFrontendWorkerPayloadInput> __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_input_from_source_units(shared_p<SourceUnitTable> sourceUnits, SourceUnitTableRow sourceUnit) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_payload_input_from_source_units", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[70]);
	shared_p<SourceUnitFrontendWorkerPayloadInput> input = __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_input(sourceUnit, string_t(""));
	input->source_units = sourceUnits;
	return input;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
string_t __latency_fn_resident_source_unit_frontend_payload_tables_source_text_for_worker_input(shared_p<SourceUnitFrontendWorkerPayloadInput> input) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::source_text_for_worker_input", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[71]);
	if (static_cast<bool>((cast<int_t<>>(input->source_units->source_unit_count) > static_cast<int_t<> >(0)))) {
		int_t<std::uint32_t> sourceUnitId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(input->source_unit_id));
		if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sourceUnitId, php::count(input->source_units->source_texts))))) {
			return input->source_units->source_texts[__latency_fn_structure_row_ids_dense_index(sourceUnitId)];
		}
		return string_t("");
	}
	return input->source_text;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
vector_t<shared_p<SourceUnitFrontendWorkerPayloadInput>> __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_inputs_for_payload_tables(shared_p<SourceUnitTable> sourceUnits, const vector_t<ResidentSourceUnitFrontendPayloadTableRow>& payloadTables) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_payload_inputs_for_payload_tables", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[72]);
	vector_t<shared_p<SourceUnitFrontendWorkerPayloadInput>> inputs = {};
	php::vector_reserve(inputs, php::count(payloadTables));
	auto& __latency_local_0 = payloadTables;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto payloadTable = __latency_local_1.value_copy();
		SourceUnitTableRow sourceUnit = __latency_fn_source_units_row_by_id(sourceUnits, payloadTable->source_unit_id);
		{
		auto __latency_local_2 = __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_input_from_source_units(sourceUnits, sourceUnit);
		(void) inputs.push_back(__latency_local_2);
		}
	}
	return inputs;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
vector_t<shared_p<SourceUnitFrontendWorkerPayloadInput>> __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_inputs_for_source_units(shared_p<SourceUnitTable> sourceUnits) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_payload_inputs_for_source_units", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[73]);
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
