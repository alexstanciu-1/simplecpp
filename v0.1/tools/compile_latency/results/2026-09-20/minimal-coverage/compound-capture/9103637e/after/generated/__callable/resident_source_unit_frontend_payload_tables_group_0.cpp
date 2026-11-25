#include <scpp/lang/php.hpp>
#include "__types/resident_source_unit_frontend_payload_tables.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_uint64_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_owner_kind_worker_local_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_worker_payload_arena_adopt_contract_missing_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_run_value_result_boundary_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_o3_measurement_required_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_owned_segment_handle_missing_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_owned_segment_payload_missing_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_owned_segment_coordinator_adoption_missing_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_replacement_missing_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_materializer_missing_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_carrier_missing_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
bool_t resident_source_unit_frontend_payload_tables::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_source_unit_frontend_payload_tables::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_source_unit_frontend_payload_tables_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[0]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_frontend_payload_tables_uint16_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::uint16_from_int", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[1]);
	return cast<int_t<std::uint16_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_resident_source_unit_frontend_payload_tables_uint64_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::uint64_from_int", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[2]);
	return cast<int_t<std::uint64_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_frontend_payload_tables_owner_kind_worker_local_id() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::owner_kind_worker_local_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::status_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[6]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_worker_payload_arena_adopt_contract_missing_id() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::blocked_reason_worker_payload_arena_adopt_contract_missing_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_run_value_result_boundary_id() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::blocked_reason_task_run_value_result_boundary_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_o3_measurement_required_id() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::blocked_reason_o3_measurement_required_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[9]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_owned_segment_handle_missing_id() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::blocked_reason_task_owned_segment_handle_missing_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[10]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_owned_segment_payload_missing_id() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::blocked_reason_task_owned_segment_payload_missing_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[11]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(6));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_task_owned_segment_coordinator_adoption_missing_id() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::blocked_reason_task_owned_segment_coordinator_adoption_missing_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[12]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(7));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_replacement_missing_id() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::blocked_reason_production_payload_replacement_missing_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[13]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(8));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_materializer_missing_id() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::blocked_reason_production_payload_materializer_missing_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[14]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(9));
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_production_payload_carrier_missing_id() {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::blocked_reason_production_payload_carrier_missing_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[15]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(10));
}

}
