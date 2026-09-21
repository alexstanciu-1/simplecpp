#include <scpp/lang/php.hpp>
#include "__types/ResidentSourceUnitFrontendPayloadAdoptionRow.hpp"
#include "__types/ResidentSourceUnitFrontendPayloadTableRow.hpp"
#include "__types/ResidentSourceUnitFrontendPayloadTransferContractRow.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_adoption_metadata_ready_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_adoption_payload_ready_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_adoption_payload_blocked_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_adoption_blocked_reason_missing_contract_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_worker_payload_arena_adopt_contract_missing_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_run_value_result_boundary_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_row_from_descriptor_install_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_transfer_policy_coordinator_value_result_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_transfer_policy_copy_safe_value_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint64_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_row_from_descriptor_install_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_rows_from_descriptor_install_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_required_payload_segment_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_descriptor_metadata_row_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_metadata_ready_total.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_adoption_metadata_ready_total(vector_t<ResidentSourceUnitFrontendPayloadAdoptionRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::adoption_metadata_ready_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[123]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->metadata_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			total = (total + static_cast<int_t<> >(1));
		}
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_adoption_payload_ready_total(vector_t<ResidentSourceUnitFrontendPayloadAdoptionRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::adoption_payload_ready_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[124]);
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
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_adoption_payload_blocked_total(vector_t<ResidentSourceUnitFrontendPayloadAdoptionRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::adoption_payload_blocked_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[125]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->payload_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id())))) {
			total = (total + static_cast<int_t<> >(1));
		}
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_adoption_blocked_reason_missing_contract_total(vector_t<ResidentSourceUnitFrontendPayloadAdoptionRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::adoption_blocked_reason_missing_contract_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[126]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->blocked_reason_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_worker_payload_arena_adopt_contract_missing_id())))) {
			total = (total + static_cast<int_t<> >(1));
		}
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
ResidentSourceUnitFrontendPayloadTransferContractRow __latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_row_from_descriptor_install_row(ResidentSourceUnitFrontendPayloadTableRow payloadTable, int_t<> transferRowId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::transfer_contract_row_from_descriptor_install_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[127]);
	int_t<> requiredSegments = required_cast<int_t<>>((cast<int_t<>>(payloadTable->token_segment_count) + cast<int_t<>>(payloadTable->frontend_node_segment_count)));
	ResidentSourceUnitFrontendPayloadTransferContractRow row = ResidentSourceUnitFrontendPayloadTransferContractRow{};
	row->transfer_row_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(transferRowId);
	row->owner_run_id = payloadTable->owner_run_id;
	row->source_unit_id = payloadTable->source_unit_id;
	row->source_unit_key_id = payloadTable->source_unit_key_id;
	row->worker_id = payloadTable->worker_id;
	row->payload_table_id = payloadTable->payload_table_id;
	row->required_payload_segment_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(requiredSegments);
	row->descriptor_metadata_row_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1));
	row->coordinator_metadata_row_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1));
	row->local_payload_segment_first_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1));
	row->local_payload_segment_count = row->required_payload_segment_count;
	row->merge_order_key = __latency_fn_resident_source_unit_frontend_payload_tables_uint64_from_int((static_cast<int_t<> >(6000000) + cast<int_t<>>(payloadTable->source_unit_id)));
	row->published_payload_segment_first_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->published_payload_segment_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->payload_copy_bytes = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->input_transfer_policy_id = __latency_fn_resident_source_unit_frontend_payload_tables_transfer_policy_copy_safe_value_id();
	row->result_transfer_policy_id = __latency_fn_resident_source_unit_frontend_payload_tables_transfer_policy_coordinator_value_result_id();
	row->metadata_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id();
	row->payload_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id();
	row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_run_value_result_boundary_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
vector_t<ResidentSourceUnitFrontendPayloadTransferContractRow> __latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_rows_from_descriptor_install_rows(vector_t<ResidentSourceUnitFrontendPayloadTableRow>& descriptorRows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::transfer_contract_rows_from_descriptor_install_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[128]);
	vector_t<ResidentSourceUnitFrontendPayloadTransferContractRow> rows = {};
	php::vector_reserve(rows, php::count(descriptorRows));
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = descriptorRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto payloadTable = __latency_local_1.value_copy();
		{
		auto __latency_local_2 = __latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_row_from_descriptor_install_row(payloadTable, (index + static_cast<int_t<> >(1)));
		(void) rows.push_back(__latency_local_2);
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_required_payload_segment_total(vector_t<ResidentSourceUnitFrontendPayloadTransferContractRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::transfer_contract_required_payload_segment_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[129]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->required_payload_segment_count));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_descriptor_metadata_row_total(vector_t<ResidentSourceUnitFrontendPayloadTransferContractRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::transfer_contract_descriptor_metadata_row_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[130]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->descriptor_metadata_row_count));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_transfer_contract_metadata_ready_total(vector_t<ResidentSourceUnitFrontendPayloadTransferContractRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::transfer_contract_metadata_ready_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[131]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->metadata_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			total = (total + static_cast<int_t<> >(1));
		}
	}
	return total;
}

}
