#include <scpp/lang/php.hpp>
#include "__types/row_segment_policy.hpp"
#include "__callable/__latency_fn_row_segment_policy_uint16_from_int.hpp"
#include "__callable/__latency_fn_row_segment_policy_uint32_from_int.hpp"
#include "__callable/__latency_fn_row_segment_policy_storage_vector_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_uint16_from_int.hpp"
#include "__callable/__latency_fn_row_segment_policy_storage_segmented_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_uint16_from_int.hpp"
#include "__callable/__latency_fn_row_segment_policy_default_segment_capacity.hpp"
#include "__callable/__latency_fn_row_segment_policy_uint32_from_int.hpp"
#include "__callable/__latency_fn_row_segment_policy_default_segment_threshold.hpp"
#include "__callable/__latency_fn_row_segment_policy_uint32_from_int.hpp"
#include "__callable/__latency_fn_row_segment_policy_initial_list_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_uint32_from_int.hpp"
#include "__callable/__latency_fn_row_segment_policy_initial_generation_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_uint32_from_int.hpp"
#include "__callable/__latency_fn_row_segment_policy_next_list_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_uint32_from_int.hpp"
#include "__callable/__latency_fn_row_segment_policy_next_generation_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_uint32_from_int.hpp"
#include "__callable/__latency_fn_row_segment_policy_list_ref_ready_flag.hpp"
#include "__callable/__latency_fn_row_segment_policy_uint16_from_int.hpp"
#include "__callable/__latency_fn_row_segment_policy_publish_status_ok_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_uint16_from_int.hpp"
#include "__callable/__latency_fn_row_segment_policy_cleanup_status_ok_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_uint16_from_int.hpp"
#include "__callable/__latency_fn_row_segment_policy_segment_count_for_rows.hpp"
#include "__callable/__latency_fn_row_segment_policy_reserved_segment_bytes.hpp"
#include "__callable/__latency_fn_row_segment_policy_used_row_bytes.hpp"
namespace scpp { extern const int __latency_lines_row_segment_policy[]; }
namespace scpp {
bool_t row_segment_policy::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == row_segment_policy::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_row_segment_policy[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_row_segment_policy_uint16_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("row_segment_policy::uint16_from_int", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_segment_policy.phs", __latency_lines_row_segment_policy[0]);
	return cast<int_t<std::uint16_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_row_segment_policy[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_row_segment_policy_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("row_segment_policy::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_segment_policy.phs", __latency_lines_row_segment_policy[1]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_row_segment_policy[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_row_segment_policy_storage_vector_id() {
	SCPP_CALL_DEPTH_GUARD("row_segment_policy::storage_vector_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_segment_policy.phs", __latency_lines_row_segment_policy[2]);
	return __latency_fn_row_segment_policy_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_row_segment_policy[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_row_segment_policy_storage_segmented_id() {
	SCPP_CALL_DEPTH_GUARD("row_segment_policy::storage_segmented_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_segment_policy.phs", __latency_lines_row_segment_policy[3]);
	return __latency_fn_row_segment_policy_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_row_segment_policy[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_row_segment_policy_default_segment_capacity() {
	SCPP_CALL_DEPTH_GUARD("row_segment_policy::default_segment_capacity", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_segment_policy.phs", __latency_lines_row_segment_policy[4]);
	return __latency_fn_row_segment_policy_uint32_from_int(static_cast<int_t<> >(65536));
}

}

namespace scpp { extern const int __latency_lines_row_segment_policy[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_row_segment_policy_default_segment_threshold() {
	SCPP_CALL_DEPTH_GUARD("row_segment_policy::default_segment_threshold", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_segment_policy.phs", __latency_lines_row_segment_policy[5]);
	return __latency_fn_row_segment_policy_uint32_from_int(static_cast<int_t<> >(16384));
}

}

namespace scpp { extern const int __latency_lines_row_segment_policy[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_row_segment_policy_initial_list_id() {
	SCPP_CALL_DEPTH_GUARD("row_segment_policy::initial_list_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_segment_policy.phs", __latency_lines_row_segment_policy[6]);
	return __latency_fn_row_segment_policy_uint32_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_row_segment_policy[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_row_segment_policy_initial_generation_id() {
	SCPP_CALL_DEPTH_GUARD("row_segment_policy::initial_generation_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_segment_policy.phs", __latency_lines_row_segment_policy[7]);
	return __latency_fn_row_segment_policy_uint32_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_row_segment_policy[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_row_segment_policy_next_list_id(int_t<std::uint32_t> currentListId) {
	SCPP_CALL_DEPTH_GUARD("row_segment_policy::next_list_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_segment_policy.phs", __latency_lines_row_segment_policy[8]);
	return __latency_fn_row_segment_policy_uint32_from_int((cast<int_t<>>(currentListId) + static_cast<int_t<> >(1)));
}

}

namespace scpp { extern const int __latency_lines_row_segment_policy[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_row_segment_policy_next_generation_id(int_t<std::uint32_t> currentGenerationId) {
	SCPP_CALL_DEPTH_GUARD("row_segment_policy::next_generation_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_segment_policy.phs", __latency_lines_row_segment_policy[9]);
	return __latency_fn_row_segment_policy_uint32_from_int((cast<int_t<>>(currentGenerationId) + static_cast<int_t<> >(1)));
}

}

namespace scpp { extern const int __latency_lines_row_segment_policy[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_row_segment_policy_list_ref_ready_flag() {
	SCPP_CALL_DEPTH_GUARD("row_segment_policy::list_ref_ready_flag", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_segment_policy.phs", __latency_lines_row_segment_policy[10]);
	return __latency_fn_row_segment_policy_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_row_segment_policy[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_row_segment_policy_publish_status_ok_id() {
	SCPP_CALL_DEPTH_GUARD("row_segment_policy::publish_status_ok_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_segment_policy.phs", __latency_lines_row_segment_policy[11]);
	return __latency_fn_row_segment_policy_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_row_segment_policy[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_row_segment_policy_cleanup_status_ok_id() {
	SCPP_CALL_DEPTH_GUARD("row_segment_policy::cleanup_status_ok_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_segment_policy.phs", __latency_lines_row_segment_policy[12]);
	return __latency_fn_row_segment_policy_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_row_segment_policy[]; }
namespace scpp {
int_t<> __latency_fn_row_segment_policy_segment_count_for_rows(int_t<> rowCount, int_t<> segmentCapacity) {
	SCPP_CALL_DEPTH_GUARD("row_segment_policy::segment_count_for_rows", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_segment_policy.phs", __latency_lines_row_segment_policy[13]);
	if (static_cast<bool>(((rowCount <= static_cast<int_t<> >(0)) || (segmentCapacity <= static_cast<int_t<> >(0))))) {
		return static_cast<int_t<> >(0);
	}
	return (cast<int_t<>>(((rowCount - static_cast<int_t<> >(1)) / segmentCapacity)) + static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_row_segment_policy[]; }
namespace scpp {
int_t<> __latency_fn_row_segment_policy_reserved_segment_bytes(int_t<std::uint32_t> segmentCount, int_t<std::uint32_t> segmentCapacity, int_t<> rowSizeBytes) {
	SCPP_CALL_DEPTH_GUARD("row_segment_policy::reserved_segment_bytes", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_segment_policy.phs", __latency_lines_row_segment_policy[14]);
	return ((cast<int_t<>>(segmentCount) * cast<int_t<>>(segmentCapacity)) * rowSizeBytes);
}

}

namespace scpp { extern const int __latency_lines_row_segment_policy[]; }
namespace scpp {
int_t<> __latency_fn_row_segment_policy_used_row_bytes(int_t<std::uint32_t> rowCount, int_t<> rowSizeBytes) {
	SCPP_CALL_DEPTH_GUARD("row_segment_policy::used_row_bytes", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_segment_policy.phs", __latency_lines_row_segment_policy[15]);
	return (cast<int_t<>>(rowCount) * rowSizeBytes);
}

}
