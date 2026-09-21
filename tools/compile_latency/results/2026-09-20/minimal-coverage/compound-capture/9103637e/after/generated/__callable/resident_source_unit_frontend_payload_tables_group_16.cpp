#include <scpp/lang/php.hpp>
#include "__types/ResidentSourceUnitFrontendTaskPayloadSegmentHandleRequestRow.hpp"
#include "__types/ResidentSourceUnitFrontendTaskPayloadSegmentHandleReservationRow.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_payload_copy_byte_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_owned_segment_payload_missing_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_segment_order_component.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_reservation_row_from_request_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint64_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_reservation_row_from_request_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_reservation_rows_from_request_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_reservation_kind_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_reservation_reserved_handle_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_reservation_acquired_handle_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_reservation_metadata_ready_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_reservation_physical_payload_ready_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_owned_segment_payload_missing_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_reservation_payload_missing_blocker_total.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_payload_copy_byte_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentHandleRequestRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_handle_request_payload_copy_byte_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[159]);
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
ResidentSourceUnitFrontendTaskPayloadSegmentHandleReservationRow __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_reservation_row_from_request_row(ResidentSourceUnitFrontendTaskPayloadSegmentHandleRequestRow requestRow, int_t<> reservationRowId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_handle_reservation_row_from_request_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[160]);
	int_t<> orderComponent = required_cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_payload_segment_order_component(requestRow->source_unit_id, requestRow->segment_kind_id, cast<int_t<>>(requestRow->segment_ordinal)));
	ResidentSourceUnitFrontendTaskPayloadSegmentHandleReservationRow row = ResidentSourceUnitFrontendTaskPayloadSegmentHandleReservationRow{};
	row->reservation_row_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(reservationRowId);
	row->owner_run_id = requestRow->owner_run_id;
	row->source_unit_id = requestRow->source_unit_id;
	row->source_unit_key_id = requestRow->source_unit_key_id;
	row->worker_id = requestRow->worker_id;
	row->payload_table_id = requestRow->payload_table_id;
	row->arena_id = requestRow->arena_id;
	row->source_handle_row_id = requestRow->source_handle_row_id;
	row->request_row_id = requestRow->request_row_id;
	row->segment_ordinal = requestRow->segment_ordinal;
	row->reserved_handle_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1));
	row->acquired_handle_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->task_owned_handle_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int((static_cast<int_t<> >(800000) + orderComponent));
	row->merge_order_key = __latency_fn_resident_source_unit_frontend_payload_tables_uint64_from_int((static_cast<int_t<> >(7200000) + orderComponent));
	row->published_payload_segment_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->published_payload_segment_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->payload_copy_bytes = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->segment_kind_id = requestRow->segment_kind_id;
	row->metadata_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id();
	row->physical_payload_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id();
	row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_owned_segment_payload_missing_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentHandleReservationRow> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_reservation_rows_from_request_rows(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentHandleRequestRow>& requestRows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_handle_reservation_rows_from_request_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[161]);
	vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentHandleReservationRow> rows = {};
	php::vector_reserve(rows, php::count(requestRows));
	int_t<> reservationRowId = required_cast<int_t<>>(static_cast<int_t<> >(1));
	auto& __latency_local_0 = requestRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto requestRow = __latency_local_1.value_copy();
		{
		auto __latency_local_2 = __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_reservation_row_from_request_row(requestRow, reservationRowId);
		(void) rows.push_back(__latency_local_2);
		}
		reservationRowId = (reservationRowId + static_cast<int_t<> >(1));
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_reservation_kind_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentHandleReservationRow>& rows, int_t<std::uint16_t> segmentKindId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_handle_reservation_kind_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[162]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->segment_kind_id), cast<int_t<>>(segmentKindId)))) {
			total = (total + static_cast<int_t<> >(1));
		}
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_reservation_reserved_handle_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentHandleReservationRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_handle_reservation_reserved_handle_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[163]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->reserved_handle_count));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_reservation_acquired_handle_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentHandleReservationRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_handle_reservation_acquired_handle_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[164]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->acquired_handle_count));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_reservation_metadata_ready_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentHandleReservationRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_handle_reservation_metadata_ready_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[165]);
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
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_reservation_physical_payload_ready_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentHandleReservationRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_handle_reservation_physical_payload_ready_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[166]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->physical_payload_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			total = (total + static_cast<int_t<> >(1));
		}
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_reservation_payload_missing_blocker_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentHandleReservationRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_handle_reservation_payload_missing_blocker_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[167]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->blocked_reason_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_owned_segment_payload_missing_id())))) {
			total = (total + static_cast<int_t<> >(1));
		}
	}
	return total;
}

}
