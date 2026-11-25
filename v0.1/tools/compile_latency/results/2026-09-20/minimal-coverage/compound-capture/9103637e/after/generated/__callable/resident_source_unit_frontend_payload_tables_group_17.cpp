#include <scpp/lang/php.hpp>
#include "__types/ResidentSourceUnitFrontendTaskPayloadSegmentHandleReservationRow.hpp"
#include "__types/ResidentSourceUnitFrontendTaskPayloadSegmentMaterializationRow.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_reservation_published_payload_segment_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_reservation_payload_copy_byte_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_owned_segment_coordinator_adoption_missing_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_segment_order_component.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_materialization_row_from_reservation_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint64_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_materialization_row_from_reservation_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_materialization_rows_from_reservation_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_materialization_kind_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_materialization_reserved_handle_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_materialization_worker_acquired_handle_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_materialization_worker_payload_ready_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_materialization_coordinator_adoption_ready_total.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_reservation_published_payload_segment_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentHandleReservationRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_handle_reservation_published_payload_segment_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[168]);
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
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_reservation_payload_copy_byte_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentHandleReservationRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_handle_reservation_payload_copy_byte_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[169]);
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
ResidentSourceUnitFrontendTaskPayloadSegmentMaterializationRow __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_materialization_row_from_reservation_row(ResidentSourceUnitFrontendTaskPayloadSegmentHandleReservationRow reservationRow, int_t<> materializationRowId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_materialization_row_from_reservation_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[170]);
	int_t<> orderComponent = required_cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_payload_segment_order_component(reservationRow->source_unit_id, reservationRow->segment_kind_id, cast<int_t<>>(reservationRow->segment_ordinal)));
	ResidentSourceUnitFrontendTaskPayloadSegmentMaterializationRow row = ResidentSourceUnitFrontendTaskPayloadSegmentMaterializationRow{};
	row->materialization_row_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(materializationRowId);
	row->owner_run_id = reservationRow->owner_run_id;
	row->source_unit_id = reservationRow->source_unit_id;
	row->source_unit_key_id = reservationRow->source_unit_key_id;
	row->worker_id = reservationRow->worker_id;
	row->payload_table_id = reservationRow->payload_table_id;
	row->arena_id = reservationRow->arena_id;
	row->source_handle_row_id = reservationRow->source_handle_row_id;
	row->request_row_id = reservationRow->request_row_id;
	row->reservation_row_id = reservationRow->reservation_row_id;
	row->segment_ordinal = reservationRow->segment_ordinal;
	row->reserved_handle_count = reservationRow->reserved_handle_count;
	row->worker_acquired_handle_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1));
	row->task_owned_handle_id = reservationRow->task_owned_handle_id;
	row->worker_payload_segment_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int((static_cast<int_t<> >(910000) + orderComponent));
	row->worker_payload_segment_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1));
	row->coordinator_adopted_segment_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->merge_order_key = __latency_fn_resident_source_unit_frontend_payload_tables_uint64_from_int((static_cast<int_t<> >(7300000) + orderComponent));
	row->published_payload_segment_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->published_payload_segment_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->payload_copy_bytes = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->segment_kind_id = reservationRow->segment_kind_id;
	row->reservation_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id();
	row->worker_payload_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id();
	row->coordinator_adoption_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id();
	row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_owned_segment_coordinator_adoption_missing_id();
	return row;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentMaterializationRow> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_materialization_rows_from_reservation_rows(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentHandleReservationRow>& reservationRows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_materialization_rows_from_reservation_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[171]);
	vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentMaterializationRow> rows = {};
	php::vector_reserve(rows, php::count(reservationRows));
	int_t<> materializationRowId = required_cast<int_t<>>(static_cast<int_t<> >(1));
	auto& __latency_local_0 = reservationRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto reservationRow = __latency_local_1.value_copy();
		{
		auto __latency_local_2 = __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_materialization_row_from_reservation_row(reservationRow, materializationRowId);
		(void) rows.push_back(__latency_local_2);
		}
		materializationRowId = (materializationRowId + static_cast<int_t<> >(1));
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_materialization_kind_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentMaterializationRow>& rows, int_t<std::uint16_t> segmentKindId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_materialization_kind_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[172]);
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
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_materialization_reserved_handle_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentMaterializationRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_materialization_reserved_handle_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[173]);
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
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_materialization_worker_acquired_handle_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentMaterializationRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_materialization_worker_acquired_handle_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[174]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->worker_acquired_handle_count));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_materialization_worker_payload_ready_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentMaterializationRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_materialization_worker_payload_ready_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[175]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->worker_payload_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			total = (total + static_cast<int_t<> >(1));
		}
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_materialization_coordinator_adoption_ready_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentMaterializationRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_materialization_coordinator_adoption_ready_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[176]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->coordinator_adoption_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			total = (total + static_cast<int_t<> >(1));
		}
	}
	return total;
}

}
