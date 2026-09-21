#include <scpp/lang/php.hpp>
#include "__types/ResidentSourceUnitFrontendTaskPayloadSegmentHandleRequestRow.hpp"
#include "__types/ResidentSourceUnitFrontendTaskSegmentHandleRow.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_required_payload_segment_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_metadata_ready_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_descriptor_ready_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_physical_payload_ready_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_task_boundary_blocked_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_owned_segment_handle_missing_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_missing_handle_blocker_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_published_payload_segment_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_payload_copy_byte_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_owned_segment_handle_missing_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_payload_segment_order_component.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_row_from_task_segment_handle_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint64_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_required_payload_segment_total(vector_t<ResidentSourceUnitFrontendTaskSegmentHandleRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_segment_handle_required_payload_segment_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[141]);
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
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_metadata_ready_total(vector_t<ResidentSourceUnitFrontendTaskSegmentHandleRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_segment_handle_metadata_ready_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[142]);
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
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_descriptor_ready_total(vector_t<ResidentSourceUnitFrontendTaskSegmentHandleRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_segment_handle_descriptor_ready_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[143]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->handle_descriptor_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id())))) {
			total = (total + static_cast<int_t<> >(1));
		}
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_physical_payload_ready_total(vector_t<ResidentSourceUnitFrontendTaskSegmentHandleRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_segment_handle_physical_payload_ready_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[144]);
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
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_task_boundary_blocked_total(vector_t<ResidentSourceUnitFrontendTaskSegmentHandleRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_segment_handle_task_boundary_blocked_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[145]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->physical_payload_status_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id())))) {
			total = (total + static_cast<int_t<> >(1));
		}
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_missing_handle_blocker_total(vector_t<ResidentSourceUnitFrontendTaskSegmentHandleRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_segment_handle_missing_handle_blocker_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[146]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->blocked_reason_id), cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_owned_segment_handle_missing_id())))) {
			total = (total + static_cast<int_t<> >(1));
		}
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_published_payload_segment_total(vector_t<ResidentSourceUnitFrontendTaskSegmentHandleRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_segment_handle_published_payload_segment_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[147]);
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
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_payload_copy_byte_total(vector_t<ResidentSourceUnitFrontendTaskSegmentHandleRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_segment_handle_payload_copy_byte_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[148]);
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
ResidentSourceUnitFrontendTaskPayloadSegmentHandleRequestRow __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_row_from_task_segment_handle_row(ResidentSourceUnitFrontendTaskSegmentHandleRow handleRow, int_t<> requestRowId, int_t<std::uint16_t> segmentKindId, int_t<> segmentOrdinal) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_handle_request_row_from_task_segment_handle_row", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[149]);
	int_t<> orderComponent = required_cast<int_t<>>(__latency_fn_resident_source_unit_frontend_payload_tables_payload_segment_order_component(handleRow->source_unit_id, cast<int_t<std::uint16_t>>(segmentKindId), segmentOrdinal));
	ResidentSourceUnitFrontendTaskPayloadSegmentHandleRequestRow row = ResidentSourceUnitFrontendTaskPayloadSegmentHandleRequestRow{};
	row->request_row_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(requestRowId);
	row->owner_run_id = handleRow->owner_run_id;
	row->source_unit_id = handleRow->source_unit_id;
	row->source_unit_key_id = handleRow->source_unit_key_id;
	row->worker_id = handleRow->worker_id;
	row->payload_table_id = handleRow->payload_table_id;
	row->arena_id = handleRow->arena_id;
	row->source_handle_row_id = handleRow->handle_row_id;
	row->segment_ordinal = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(segmentOrdinal);
	row->required_handle_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(1));
	row->acquired_handle_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->task_owned_handle_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->merge_order_key = __latency_fn_resident_source_unit_frontend_payload_tables_uint64_from_int((static_cast<int_t<> >(7100000) + orderComponent));
	row->published_payload_segment_id = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->published_payload_segment_count = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->payload_copy_bytes = __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(static_cast<int_t<> >(0));
	row->segment_kind_id = segmentKindId;
	row->metadata_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id();
	row->handle_descriptor_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id();
	row->physical_payload_status_id = __latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id();
	row->blocked_reason_id = __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_owned_segment_handle_missing_id();
	return row;
}

}
