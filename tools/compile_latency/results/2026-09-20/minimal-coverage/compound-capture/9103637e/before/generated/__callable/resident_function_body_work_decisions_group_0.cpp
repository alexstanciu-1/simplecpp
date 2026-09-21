#include <scpp/lang/php.hpp>
#include "__types/resident_function_body_work_decisions.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_change_new_body_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_change_no_change_reuse_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_change_body_changed_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_change_public_surface_changed_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_change_deleted_body_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_reuse_previous_body_rows_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_parse_replacement_body_rows_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_publish_public_surface_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_cleanup_deleted_body_rows_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_decision_build_new_body_rows_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_action_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_action_reuse_previous_body_rows_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_action_parse_replacement_body_rows_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_action_publish_public_surface_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_work_decisions_action_cleanup_deleted_body_rows_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
bool_t resident_function_body_work_decisions::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_function_body_work_decisions::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_work_decisions_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[0]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_work_decisions_change_new_body_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::change_new_body_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_work_decisions_change_no_change_reuse_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::change_no_change_reuse_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_work_decisions_change_body_changed_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::change_body_changed_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_work_decisions_change_public_surface_changed_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::change_public_surface_changed_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_work_decisions_change_deleted_body_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::change_deleted_body_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_work_decisions_decision_reuse_previous_body_rows_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::decision_reuse_previous_body_rows_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[6]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_work_decisions_decision_parse_replacement_body_rows_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::decision_parse_replacement_body_rows_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_work_decisions_decision_publish_public_surface_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::decision_publish_public_surface_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_work_decisions_decision_cleanup_deleted_body_rows_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::decision_cleanup_deleted_body_rows_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[9]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_work_decisions_decision_build_new_body_rows_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::decision_build_new_body_rows_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[10]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_work_decisions_action_none_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::action_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[11]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_work_decisions_action_reuse_previous_body_rows_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::action_reuse_previous_body_rows_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[12]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_work_decisions_action_parse_replacement_body_rows_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::action_parse_replacement_body_rows_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[13]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_work_decisions_action_publish_public_surface_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::action_publish_public_surface_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[14]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_work_decisions[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_work_decisions_action_cleanup_deleted_body_rows_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_work_decisions::action_cleanup_deleted_body_rows_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_work_decisions.phs", __latency_lines_resident_function_body_work_decisions[15]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}
