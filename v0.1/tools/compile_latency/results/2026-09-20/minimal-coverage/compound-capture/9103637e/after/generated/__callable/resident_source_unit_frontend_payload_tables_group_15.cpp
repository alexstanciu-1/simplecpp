#include <scpp/lang/php.hpp>
#include "__types/ResidentSourceUnitFrontendTaskPayloadSegmentHandleRequestRow.hpp"
#include "__types/ResidentSourceUnitFrontendTaskSegmentHandleRow.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_segment_kind_frontend_node_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_segment_kind_token_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_row_from_task_segment_handle_row.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_rows_from_task_segment_handle_rows.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_required_payload_segment_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_kind_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_required_handle_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_acquired_handle_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_metadata_ready_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_descriptor_ready_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_physical_payload_ready_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_owned_segment_handle_missing_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_missing_handle_blocker_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_published_payload_segment_total.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentHandleRequestRow> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_rows_from_task_segment_handle_rows(vector_t<ResidentSourceUnitFrontendTaskSegmentHandleRow>& handleRows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_handle_request_rows_from_task_segment_handle_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[150]);
	vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentHandleRequestRow> rows = {};
	php::vector_reserve(rows, __latency_fn_resident_source_unit_frontend_payload_tables_task_segment_handle_required_payload_segment_total(handleRows));
	int_t<> requestRowId = required_cast<int_t<>>(static_cast<int_t<> >(1));
	auto& __latency_local_0 = handleRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto handleRow = __latency_local_1.value_copy();
		int_t<> tokenIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
		while (static_cast<bool>((tokenIndex < cast<int_t<>>(handleRow->token_segment_count)))) {
			{
			auto __latency_local_2 = __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_row_from_task_segment_handle_row(handleRow, requestRowId, __latency_fn_resident_source_unit_frontend_payload_tables_segment_kind_token_id(), (tokenIndex + static_cast<int_t<> >(1)));
			(void) rows.push_back(__latency_local_2);
			}
			requestRowId = (requestRowId + static_cast<int_t<> >(1));
			tokenIndex = (tokenIndex + static_cast<int_t<> >(1));
		}
		int_t<> frontendIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
		while (static_cast<bool>((frontendIndex < cast<int_t<>>(handleRow->frontend_node_segment_count)))) {
			{
			auto __latency_local_3 = __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_row_from_task_segment_handle_row(handleRow, requestRowId, __latency_fn_resident_source_unit_frontend_payload_tables_segment_kind_frontend_node_id(), (frontendIndex + static_cast<int_t<> >(1)));
			(void) rows.push_back(__latency_local_3);
			}
			requestRowId = (requestRowId + static_cast<int_t<> >(1));
			frontendIndex = (frontendIndex + static_cast<int_t<> >(1));
		}
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_kind_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentHandleRequestRow>& rows, int_t<std::uint16_t> segmentKindId) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_handle_request_kind_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[151]);
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
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_required_handle_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentHandleRequestRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_handle_request_required_handle_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[152]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->required_handle_count));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_acquired_handle_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentHandleRequestRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_handle_request_acquired_handle_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[153]);
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
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_metadata_ready_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentHandleRequestRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_handle_request_metadata_ready_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[154]);
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
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_descriptor_ready_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentHandleRequestRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_handle_request_descriptor_ready_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[155]);
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
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_physical_payload_ready_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentHandleRequestRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_handle_request_physical_payload_ready_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[156]);
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
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_missing_handle_blocker_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentHandleRequestRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_handle_request_missing_handle_blocker_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[157]);
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
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_task_payload_segment_handle_request_published_payload_segment_total(vector_t<ResidentSourceUnitFrontendTaskPayloadSegmentHandleRequestRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::task_payload_segment_handle_request_published_payload_segment_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[158]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->published_payload_segment_count));
	}
	return total;
}

}
