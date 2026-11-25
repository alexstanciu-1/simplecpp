#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ResidentFunctionBodyPublishRepointPreflightRow.hpp"
#include "__types/resident_function_body_publish_repoint_preflights.hpp"
#include "__types/resident_function_body_stable_node_remap_proofs.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_stable_id_window_stride.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_max_uint32_int.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_blocked_reason_preflight_not_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_blocked_reason_empty_local_node_range_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_blocked_reason_missing_owner_identity_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_blocked_reason_stable_window_overflow_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_identity_namespace_body_owner_symbol_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_remap_strategy_stable_window_by_local_ordinal_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_has_publish_repoint_preflights.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_stable_id_window_stride.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_stable_window_start_for_symbol.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_max_uint32_int.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_stable_window_fits.hpp"
#include "__callable/__latency_fn_resident_function_body_stable_node_remap_proofs_stable_window_start_for_symbol.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_stable_node_remap_proofs[]; }
namespace scpp {
bool_t resident_function_body_stable_node_remap_proofs::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == resident_function_body_stable_node_remap_proofs::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_stable_node_remap_proofs[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_resident_function_body_stable_node_remap_proofs_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_stable_node_remap_proofs::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_stable_node_remap_proofs.phs", __latency_lines_resident_function_body_stable_node_remap_proofs[0]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_stable_node_remap_proofs[]; }
namespace scpp {
int_t<> __latency_fn_resident_function_body_stable_node_remap_proofs_stable_id_window_stride() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_stable_node_remap_proofs::stable_id_window_stride", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_stable_node_remap_proofs.phs", __latency_lines_resident_function_body_stable_node_remap_proofs[1]);
	return static_cast<int_t<> >(1048576);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_stable_node_remap_proofs[]; }
namespace scpp {
int_t<> __latency_fn_resident_function_body_stable_node_remap_proofs_max_uint32_int() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_stable_node_remap_proofs::max_uint32_int", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_stable_node_remap_proofs.phs", __latency_lines_resident_function_body_stable_node_remap_proofs[2]);
	return static_cast<int_t<> >(4294967295);
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_stable_node_remap_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_stable_node_remap_proofs_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_stable_node_remap_proofs::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_stable_node_remap_proofs.phs", __latency_lines_resident_function_body_stable_node_remap_proofs[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_stable_node_remap_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_stable_node_remap_proofs_status_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_stable_node_remap_proofs::status_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_stable_node_remap_proofs.phs", __latency_lines_resident_function_body_stable_node_remap_proofs[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_stable_node_remap_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_stable_node_remap_proofs_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_stable_node_remap_proofs::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_stable_node_remap_proofs.phs", __latency_lines_resident_function_body_stable_node_remap_proofs[5]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_stable_node_remap_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_stable_node_remap_proofs_blocked_reason_preflight_not_ready_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_stable_node_remap_proofs::blocked_reason_preflight_not_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_stable_node_remap_proofs.phs", __latency_lines_resident_function_body_stable_node_remap_proofs[6]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_stable_node_remap_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_stable_node_remap_proofs_blocked_reason_empty_local_node_range_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_stable_node_remap_proofs::blocked_reason_empty_local_node_range_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_stable_node_remap_proofs.phs", __latency_lines_resident_function_body_stable_node_remap_proofs[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_stable_node_remap_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_stable_node_remap_proofs_blocked_reason_missing_owner_identity_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_stable_node_remap_proofs::blocked_reason_missing_owner_identity_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_stable_node_remap_proofs.phs", __latency_lines_resident_function_body_stable_node_remap_proofs[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_stable_node_remap_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_stable_node_remap_proofs_blocked_reason_stable_window_overflow_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_stable_node_remap_proofs::blocked_reason_stable_window_overflow_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_stable_node_remap_proofs.phs", __latency_lines_resident_function_body_stable_node_remap_proofs[9]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_stable_node_remap_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_stable_node_remap_proofs_identity_namespace_body_owner_symbol_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_stable_node_remap_proofs::identity_namespace_body_owner_symbol_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_stable_node_remap_proofs.phs", __latency_lines_resident_function_body_stable_node_remap_proofs[10]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_stable_node_remap_proofs[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_resident_function_body_stable_node_remap_proofs_remap_strategy_stable_window_by_local_ordinal_id() {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_stable_node_remap_proofs::remap_strategy_stable_window_by_local_ordinal_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_stable_node_remap_proofs.phs", __latency_lines_resident_function_body_stable_node_remap_proofs[11]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_stable_node_remap_proofs[]; }
namespace scpp {
bool_t __latency_fn_resident_function_body_stable_node_remap_proofs_has_publish_repoint_preflights(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_stable_node_remap_proofs::has_publish_repoint_preflights", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_stable_node_remap_proofs.phs", __latency_lines_resident_function_body_stable_node_remap_proofs[12]);
	auto __latency_local_0 = report->resident_function_body_publish_repoint_preflights;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto preflight = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(preflight->owner_run_id), cast<int_t<>>(ownerRunId)))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_stable_node_remap_proofs[]; }
namespace scpp {
int_t<> __latency_fn_resident_function_body_stable_node_remap_proofs_stable_window_start_for_symbol(int_t<std::uint32_t> symbolId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_stable_node_remap_proofs::stable_window_start_for_symbol", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_stable_node_remap_proofs.phs", __latency_lines_resident_function_body_stable_node_remap_proofs[13]);
	return ((cast<int_t<>>(symbolId) * __latency_fn_resident_function_body_stable_node_remap_proofs_stable_id_window_stride()) + static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_stable_node_remap_proofs[]; }
namespace scpp {
bool_t __latency_fn_resident_function_body_stable_node_remap_proofs_stable_window_fits(int_t<std::uint32_t> symbolId, int_t<std::uint32_t> nodeCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_stable_node_remap_proofs::stable_window_fits", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_stable_node_remap_proofs.phs", __latency_lines_resident_function_body_stable_node_remap_proofs[14]);
	if (static_cast<bool>((php::identical(cast<int_t<>>(symbolId), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(nodeCount), static_cast<int_t<> >(0))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	int_t<> start = required_cast<int_t<>>(__latency_fn_resident_function_body_stable_node_remap_proofs_stable_window_start_for_symbol(cast<int_t<std::uint32_t>>(symbolId)));
	int_t<> last = required_cast<int_t<>>(((start + cast<int_t<>>(nodeCount)) - static_cast<int_t<> >(1)));
	return bool_t((last <= __latency_fn_resident_function_body_stable_node_remap_proofs_max_uint32_int()));
}

}
