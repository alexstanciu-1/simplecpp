#include <scpp/lang/php.hpp>
#include "__types/ResidentSourceUnitFrontendPayloadArenaContractRow.hpp"
#include "__types/ResidentSourceUnitFrontendPayloadTableRow.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_table_row_from_descriptor.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_table_rows_from_descriptors.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_descriptor_install_payload_table_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_descriptor_install_payload_table_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_owner_kind_worker_local_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_row_from_source_descriptor.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_id_for_source_unit.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_frontend_node_segments.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_frontend_nodes.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_parser_errors.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_token_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_token_segments.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_row_from_source_descriptor.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_table_rows_from_source_units_and_descriptors.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_row_payload_metadata_matches.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_metadata_matches.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_row_payload_metadata_matches.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_row_from_descriptor_install_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_arena_id_for_payload_table.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_row_from_descriptor_install_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_rows_from_descriptor_install_rows.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
vector_t<ResidentSourceUnitFrontendPayloadTableRow> __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_table_rows_from_descriptors(vector_t<ResidentSourceUnitFrontendPayloadTableRow>& payloadTables, const vector_t<int_t<>>& descriptors) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_payload_table_rows_from_descriptors", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[96]);
	vector_t<ResidentSourceUnitFrontendPayloadTableRow> rows = {};
	php::vector_reserve(rows, php::count(payloadTables));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = payloadTables;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto payloadTable = __latency_local_1.value_copy();
		int_t<> descriptor = required_cast<int_t<>>(static_cast<int_t<> >(0));
		if (static_cast<bool>((index < php::count(descriptors)))) {
			descriptor = cast<int_t<>>(descriptors.at(index));
		}
		{
		auto __latency_local_2 = __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_table_row_from_descriptor(payloadTable, descriptor);
		(void) rows.push_back(__latency_local_2);
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_source_unit_frontend_payload_tables_descriptor_install_payload_table_id(SourceUnitTableRow sourceUnit) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::descriptor_install_payload_table_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[97]);
	return __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int((static_cast<int_t<> >(8000) + cast<int_t<>>(sourceUnit->source_unit_id)));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
ResidentSourceUnitFrontendPayloadTableRow __latency_fn_resident_source_unit_frontend_payload_tables_row_from_source_descriptor(SourceUnitTableRow sourceUnit, int_t<> descriptor, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::row_from_source_descriptor", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[98]);
	int_t<std::uint32_t> payloadTableId = required_cast<int_t<std::uint32_t>>(__latency_fn_resident_source_unit_frontend_payload_tables_descriptor_install_payload_table_id(sourceUnit));
	ResidentSourceUnitFrontendPayloadTableRow row = ResidentSourceUnitFrontendPayloadTableRow{};
	row->payload_table_id = payloadTableId;
	row->owner_run_id = ownerRunId;
	row->source_unit_id = sourceUnit->source_unit_id;
	row->source_unit_key_id = sourceUnit->source_unit_key_id;
	row->worker_id = __latency_fn_resident_source_unit_frontend_payload_tables_worker_id_for_source_unit(sourceUnit->source_unit_id);
	row->input_snapshot_generation = ownerRunId;
	row->token_list_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(((cast<int_t<>>(payloadTableId) * static_cast<int_t<> >(10)) + static_cast<int_t<> >(1)));
	row->token_row_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_token_rows(descriptor));
	row->token_segment_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_token_segments(descriptor));
	row->token_reserved_segment_bytes = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->token_segment_slack_bytes = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->frontend_node_list_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(((cast<int_t<>>(payloadTableId) * static_cast<int_t<> >(10)) + static_cast<int_t<> >(2)));
	row->frontend_node_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_frontend_nodes(descriptor));
	row->frontend_node_segment_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_frontend_node_segments(descriptor));
	row->frontend_node_reserved_segment_bytes = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->frontend_node_segment_slack_bytes = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->parser_error_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(__latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_descriptor_parser_errors(descriptor));
	row->payload_owner_kind_id = __latency_fn_resident_source_unit_frontend_payload_tables_owner_kind_worker_local_id();
	row->status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
vector_t<ResidentSourceUnitFrontendPayloadTableRow> __latency_fn_resident_source_unit_frontend_payload_tables_worker_payload_table_rows_from_source_units_and_descriptors(shared_p<SourceUnitTable> sourceUnits, const vector_t<int_t<>>& descriptors, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::worker_payload_table_rows_from_source_units_and_descriptors", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[99]);
	vector_t<ResidentSourceUnitFrontendPayloadTableRow> rows = {};
	php::vector_reserve(rows, php::count(sourceUnits->rows));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = sourceUnits->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto sourceUnit = __latency_local_1.value_copy();
		int_t<> descriptor = required_cast<int_t<>>(static_cast<int_t<> >(0));
		if (static_cast<bool>((index < php::count(descriptors)))) {
			descriptor = cast<int_t<>>(descriptors.at(index));
		}
		{
		auto __latency_local_2 = __latency_fn_resident_source_unit_frontend_payload_tables_row_from_source_descriptor(sourceUnit, descriptor, cast<int_t<std::uint32_t>>(ownerRunId));
		(void) rows.push_back(__latency_local_2);
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
bool_t __latency_fn_resident_source_unit_frontend_payload_tables_row_payload_metadata_matches(ResidentSourceUnitFrontendPayloadTableRow left, ResidentSourceUnitFrontendPayloadTableRow right) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::row_payload_metadata_matches", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[100]);
	return ((((((((((((php::identical(cast<int_t<>>(left->owner_run_id), cast<int_t<>>(right->owner_run_id)) && php::identical(cast<int_t<>>(left->source_unit_id), cast<int_t<>>(right->source_unit_id))) && php::identical(cast<int_t<>>(left->source_unit_key_id), cast<int_t<>>(right->source_unit_key_id))) && php::identical(cast<int_t<>>(left->worker_id), cast<int_t<>>(right->worker_id))) && php::identical(cast<int_t<>>(left->input_snapshot_generation), cast<int_t<>>(right->input_snapshot_generation))) && php::identical(cast<int_t<>>(left->token_row_count), cast<int_t<>>(right->token_row_count))) && php::identical(cast<int_t<>>(left->token_segment_count), cast<int_t<>>(right->token_segment_count))) && php::identical(cast<int_t<>>(left->frontend_node_count), cast<int_t<>>(right->frontend_node_count))) && php::identical(cast<int_t<>>(left->frontend_node_segment_count), cast<int_t<>>(right->frontend_node_segment_count))) && php::identical(cast<int_t<>>(left->parser_error_count), cast<int_t<>>(right->parser_error_count))) && php::identical(cast<int_t<>>(left->payload_owner_kind_id), cast<int_t<>>(right->payload_owner_kind_id))) && php::identical(cast<int_t<>>(left->status_id), cast<int_t<>>(right->status_id))) && php::identical(cast<int_t<>>(left->blocked_reason_id), cast<int_t<>>(right->blocked_reason_id)));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
bool_t __latency_fn_resident_source_unit_frontend_payload_tables_payload_metadata_matches(vector_t<ResidentSourceUnitFrontendPayloadTableRow>& leftRows, vector_t<ResidentSourceUnitFrontendPayloadTableRow>& rightRows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::payload_metadata_matches", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[101]);
	if (static_cast<bool>(php::condition_truthy(php::not_identical(php::count(leftRows), php::count(rightRows))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((index < php::count(leftRows)))) {
		if (static_cast<bool>((!__latency_fn_resident_source_unit_frontend_payload_tables_row_payload_metadata_matches(leftRows.at(index), rightRows.at(index))))) {
			return bool_t(static_cast<bool_t>(false));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return bool_t(static_cast<bool_t>(true));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
ResidentSourceUnitFrontendPayloadArenaContractRow __latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_row_from_descriptor_install_row(ResidentSourceUnitFrontendPayloadTableRow payloadTable, int_t<> contractRowId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::arena_contract_row_from_descriptor_install_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[102]);
	int_t<> requiredSegments = required_cast<int_t<>>((cast<int_t<>>(payloadTable->token_segment_count) + cast<int_t<>>(payloadTable->frontend_node_segment_count)));
	ResidentSourceUnitFrontendPayloadArenaContractRow row = ResidentSourceUnitFrontendPayloadArenaContractRow{};
	row->contract_row_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(contractRowId);
	row->owner_run_id = payloadTable->owner_run_id;
	row->source_unit_id = payloadTable->source_unit_id;
	row->source_unit_key_id = payloadTable->source_unit_key_id;
	row->worker_id = payloadTable->worker_id;
	row->payload_table_id = payloadTable->payload_table_id;
	row->arena_id = __latency_fn_resident_source_unit_frontend_payload_tables_arena_id_for_payload_table(payloadTable);
	row->arena_generation = payloadTable->input_snapshot_generation;
	row->token_segment_first_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1));
	row->token_segment_count = payloadTable->token_segment_count;
	row->frontend_node_segment_first_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int((static_cast<int_t<> >(1) + cast<int_t<>>(payloadTable->token_segment_count)));
	row->frontend_node_segment_count = payloadTable->frontend_node_segment_count;
	row->required_payload_segment_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(requiredSegments);
	row->adoptable_payload_segment_count = row->required_payload_segment_count;
	row->adopted_payload_segment_count = row->required_payload_segment_count;
	row->coordinator_handle_input_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->payload_copy_bytes = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->metadata_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id();
	row->payload_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id();
	row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_none_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
vector_t<ResidentSourceUnitFrontendPayloadArenaContractRow> __latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_rows_from_descriptor_install_rows(vector_t<ResidentSourceUnitFrontendPayloadTableRow>& descriptorRows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::arena_contract_rows_from_descriptor_install_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[103]);
	vector_t<ResidentSourceUnitFrontendPayloadArenaContractRow> rows = {};
	php::vector_reserve(rows, php::count(descriptorRows));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = descriptorRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto payloadTable = __latency_local_1.value_copy();
		{
		auto __latency_local_2 = __latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_row_from_descriptor_install_row(payloadTable, (index + static_cast<int_t<> >(1)));
		(void) rows.push_back(__latency_local_2);
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return rows;
}

}
