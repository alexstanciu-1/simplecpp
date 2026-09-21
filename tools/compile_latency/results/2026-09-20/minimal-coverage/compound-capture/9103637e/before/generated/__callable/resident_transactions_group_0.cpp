#include <scpp/lang/php.hpp>
#include "__types/resident_transactions.hpp"
#include "__callable/__latency_fn_resident_transactions_scenario_cold_all_new_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_transactions_scenario_warm_no_previous_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_transactions_scenario_warm_no_change_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_transactions_scenario_warm_changed_aggregate_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_transactions_status_classified_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_transactions_status_missing_previous_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_transactions_change_all_new_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_transactions_change_no_previous_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_transactions_change_no_change_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_transactions_change_changed_aggregate_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_transactions_reuse_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_transactions_reuse_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_transactions_reuse_blocked_changed_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_transactions_file_change_aggregate_new_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_transactions_file_change_aggregate_no_previous_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_transactions_file_change_aggregate_no_change_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
bool_t resident_transactions::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_transactions::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_transactions_scenario_cold_all_new_id() {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::scenario_cold_all_new_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_transactions_scenario_warm_no_previous_id() {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::scenario_warm_no_previous_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_transactions_scenario_warm_no_change_id() {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::scenario_warm_no_change_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_transactions_scenario_warm_changed_aggregate_id() {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::scenario_warm_changed_aggregate_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_transactions_status_classified_id() {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::status_classified_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_transactions_status_missing_previous_id() {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::status_missing_previous_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_transactions_change_all_new_id() {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::change_all_new_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[6]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_transactions_change_no_previous_id() {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::change_no_previous_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_transactions_change_no_change_id() {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::change_no_change_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_transactions_change_changed_aggregate_id() {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::change_changed_aggregate_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[9]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_transactions_reuse_none_id() {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::reuse_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[10]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_transactions_reuse_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::reuse_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[11]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_transactions_reuse_blocked_changed_id() {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::reuse_blocked_changed_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[12]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_transactions_file_change_aggregate_new_id() {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::file_change_aggregate_new_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[13]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_transactions_file_change_aggregate_no_previous_id() {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::file_change_aggregate_no_previous_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[14]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_transactions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_transactions_file_change_aggregate_no_change_id() {
	SCPP_CALL_DEPTH_GUARD("resident_transactions::file_change_aggregate_no_change_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_transactions.phs", __latency_lines_resident_transactions[15]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}
