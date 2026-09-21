#include <scpp/lang/php.hpp>
#include "__types/ResidentSourceUnitFrontendPayloadTableRow.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_token_row_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_frontend_node_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_token_segment_total.hpp"
#include "__callable/__latency_fn_resident_source_unit_frontend_payload_tables_frontend_node_segment_total.hpp"
namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_token_row_total(vector_t<ResidentSourceUnitFrontendPayloadTableRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::token_row_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[238]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->token_row_count));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_frontend_node_total(vector_t<ResidentSourceUnitFrontendPayloadTableRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::frontend_node_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[239]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->frontend_node_count));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_token_segment_total(vector_t<ResidentSourceUnitFrontendPayloadTableRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::token_segment_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[240]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->token_segment_count));
	}
	return total;
}

}

namespace scpp { extern const int __latency_lines_resident_source_unit_frontend_payload_tables[]; }
namespace scpp {
int_t<> __latency_fn_resident_source_unit_frontend_payload_tables_frontend_node_segment_total(vector_t<ResidentSourceUnitFrontendPayloadTableRow>& rows) {
	SCPP_CALL_DEPTH_GUARD("resident_source_unit_frontend_payload_tables::frontend_node_segment_total", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_source_unit_frontend_payload_tables.phs", __latency_lines_resident_source_unit_frontend_payload_tables[241]);
	int_t<> total = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		total = (total + cast<int_t<>>(row->frontend_node_segment_count));
	}
	return total;
}

}
