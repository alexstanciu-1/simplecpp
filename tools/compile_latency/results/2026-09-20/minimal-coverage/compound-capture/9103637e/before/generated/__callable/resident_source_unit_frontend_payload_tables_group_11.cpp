#include <scpp/lang/php.hpp>
#include "__types/ResidentSourceUnitFrontendPayloadAdoptionRow.hpp"
#include "__types/ResidentSourceUnitFrontendPayloadArenaContractRow.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_adopted_payload_segment_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_coordinator_handle_input_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_metadata_ready_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_payload_ready_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_payload_blocked_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_blocked_reason_missing_contract_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_blocked_reason_worker_payload_arena_adopt_contract_missing_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_payload_copy_byte_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_adoption_required_payload_segment_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_adoption_adopted_payload_segment_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_adoption_payload_blocked_segment_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_status_blocked_id.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_adoption_coordinator_handle_input_total.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_adopted_payload_segment_total(vector_t<ResidentSourceUnitFrontendPayloadArenaContractRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::arena_contract_adopted_payload_segment_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[112]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->adopted_payload_segment_count));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_coordinator_handle_input_total(vector_t<ResidentSourceUnitFrontendPayloadArenaContractRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::arena_contract_coordinator_handle_input_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[113]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->coordinator_handle_input_count));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_metadata_ready_total(vector_t<ResidentSourceUnitFrontendPayloadArenaContractRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::arena_contract_metadata_ready_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[114]);
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
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_payload_ready_total(vector_t<ResidentSourceUnitFrontendPayloadArenaContractRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::arena_contract_payload_ready_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[115]);
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
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_payload_blocked_total(vector_t<ResidentSourceUnitFrontendPayloadArenaContractRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::arena_contract_payload_blocked_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[116]);
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
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_blocked_reason_missing_contract_total(vector_t<ResidentSourceUnitFrontendPayloadArenaContractRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::arena_contract_blocked_reason_missing_contract_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[117]);
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
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_arena_contract_payload_copy_byte_total(vector_t<ResidentSourceUnitFrontendPayloadArenaContractRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::arena_contract_payload_copy_byte_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[118]);
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
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_adoption_required_payload_segment_total(vector_t<ResidentSourceUnitFrontendPayloadAdoptionRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::adoption_required_payload_segment_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[119]);
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
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_adoption_adopted_payload_segment_total(vector_t<ResidentSourceUnitFrontendPayloadAdoptionRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::adoption_adopted_payload_segment_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[120]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->adopted_payload_segment_count));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_adoption_payload_blocked_segment_total(vector_t<ResidentSourceUnitFrontendPayloadAdoptionRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::adoption_payload_blocked_segment_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[121]);
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
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_adoption_coordinator_handle_input_total(vector_t<ResidentSourceUnitFrontendPayloadAdoptionRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::adoption_coordinator_handle_input_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[122]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->coordinator_handle_input_count));
	}
	return total;
}

}
