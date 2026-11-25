#include <scpp/lang/php.hpp>
#include "__types/ResidentSourceUnitFrontendPayloadTableRow.hpp"
#include "__types/ResidentSourceUnitFrontendPayloadTransferContractRow.hpp"
#include "__types/ResidentSourceUnitFrontendTaskSegmentHandleRow.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_descriptor_only_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_worker_arena_ready_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_worker_arena_ready_segment_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_payload_blocked_segment_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_run_value_result_boundary_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_task_value_boundary_blocked_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_published_payload_segment_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_payload_copy_byte_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_arena_id_for_payload_table.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_owned_segment_handle_missing_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_row_from_descriptor_install_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint64_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_row_from_descriptor_install_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_rows_from_descriptor_install_rows.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_descriptor_only_total(vector_t<ResidentSourceUnitFrontendPayloadTransferContractRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::transfer_contract_descriptor_only_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[132]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->metadata_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())) && php::identical(cast<int_t<>>(row->payload_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id()))))) {
			total = (total + static_cast<int_t<> >(1));
		}
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_worker_arena_ready_total(vector_t<ResidentSourceUnitFrontendPayloadTransferContractRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::transfer_contract_worker_arena_ready_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[133]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->payload_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			total = (total + static_cast<int_t<> >(1));
		}
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_worker_arena_ready_segment_total(vector_t<ResidentSourceUnitFrontendPayloadTransferContractRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::transfer_contract_worker_arena_ready_segment_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[134]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->payload_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			total = (total + cast<int_t<>>(row->required_payload_segment_count));
		}
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_payload_blocked_segment_total(vector_t<ResidentSourceUnitFrontendPayloadTransferContractRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::transfer_contract_payload_blocked_segment_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[135]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->payload_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id())))) {
			total = (total + cast<int_t<>>(row->required_payload_segment_count));
		}
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_task_value_boundary_blocked_total(vector_t<ResidentSourceUnitFrontendPayloadTransferContractRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::transfer_contract_task_value_boundary_blocked_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[136]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->blocked_reason_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_run_value_result_boundary_id())))) {
			total = (total + static_cast<int_t<> >(1));
		}
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_published_payload_segment_total(vector_t<ResidentSourceUnitFrontendPayloadTransferContractRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::transfer_contract_published_payload_segment_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[137]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->published_payload_segment_count));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_payload_copy_byte_total(vector_t<ResidentSourceUnitFrontendPayloadTransferContractRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::transfer_contract_payload_copy_byte_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[138]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->payload_copy_bytes));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
ResidentSourceUnitFrontendTaskSegmentHandleRow __latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_row_from_descriptor_install_row(ResidentSourceUnitFrontendPayloadTableRow payloadTable, int_t<> handleRowId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_segment_handle_row_from_descriptor_install_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[139]);
	int_t<> requiredSegments = required_cast<int_t<>>((cast<int_t<>>(payloadTable->token_segment_count) + cast<int_t<>>(payloadTable->frontend_node_segment_count)));
	ResidentSourceUnitFrontendTaskSegmentHandleRow row = ResidentSourceUnitFrontendTaskSegmentHandleRow{};
	row->handle_row_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(handleRowId);
	row->owner_run_id = payloadTable->owner_run_id;
	row->source_unit_id = payloadTable->source_unit_id;
	row->source_unit_key_id = payloadTable->source_unit_key_id;
	row->worker_id = payloadTable->worker_id;
	row->payload_table_id = payloadTable->payload_table_id;
	row->arena_id = __latency_fn_resident_source_unit_frontend_payload_tables_arena_id_for_payload_table(payloadTable);
	row->token_segment_count = payloadTable->token_segment_count;
	row->frontend_node_segment_count = payloadTable->frontend_node_segment_count;
	row->required_payload_segment_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(requiredSegments);
	row->descriptor_metadata_row_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1));
	row->task_owned_handle_first_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->task_owned_handle_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->merge_order_key = __latency_fn_resident_source_unit_frontend_payload_tables_uint64_from_int((static_cast<int_t<> >(7000000) + cast<int_t<>>(payloadTable->source_unit_id)));
	row->published_payload_segment_first_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->published_payload_segment_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->payload_copy_bytes = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->metadata_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id();
	row->handle_descriptor_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id();
	row->physical_payload_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id();
	row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_owned_segment_handle_missing_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
vector_t<ResidentSourceUnitFrontendTaskSegmentHandleRow> __latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_rows_from_descriptor_install_rows(vector_t<ResidentSourceUnitFrontendPayloadTableRow>& descriptorRows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_segment_handle_rows_from_descriptor_install_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[140]);
	vector_t<ResidentSourceUnitFrontendTaskSegmentHandleRow> rows = {};
	php::vector_reserve(rows, php::count(descriptorRows));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = descriptorRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto payloadTable = __latency_local_1.value_copy();
		{
		auto __latency_local_2 = __latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_row_from_descriptor_install_row(payloadTable, (index + static_cast<int_t<> >(1)));
		(void) rows.push_back(__latency_local_2);
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return rows;
}

}
