#include <scpp/lang/php.hpp>
#include "__types/FrontendNodeList.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendNodeSegment.hpp"
#include "__types/frontend_node_lists.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_node_lists_storage_vector_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_storage_vector_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_storage_segmented_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_storage_segmented_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_default_segment_capacity.hpp"
#include "__callable/__latency_fn_row_segment_policy_default_segment_capacity.hpp"
#include "__callable/__latency_fn_frontend_node_lists_segmented_threshold.hpp"
#include "__callable/__latency_fn_row_segment_policy_default_segment_threshold.hpp"
#include "__callable/__latency_fn_frontend_node_lists_row_size_bytes.hpp"
#include "__callable/__latency_fn_frontend_node_lists_empty_row.hpp"
#include "__callable/__latency_fn_frontend_node_lists_default_segment_capacity.hpp"
#include "__callable/__latency_fn_frontend_node_lists_ensure_defaults.hpp"
#include "__callable/__latency_fn_frontend_node_lists_segmented_threshold.hpp"
#include "__callable/__latency_fn_frontend_node_lists_storage_vector_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_storage_segmented_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uses_segmented.hpp"
#include "__callable/__latency_fn_frontend_node_lists_segment_count_for_rows.hpp"
#include "__callable/__latency_fn_row_segment_policy_segment_count_for_rows.hpp"
#include "__callable/__latency_fn_frontend_node_lists_activate_segmented.hpp"
#include "__callable/__latency_fn_frontend_node_lists_ensure_defaults.hpp"
#include "__callable/__latency_fn_frontend_node_lists_reserve.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uses_segmented.hpp"
#include "__callable/__latency_fn_frontend_node_lists_reserve_segments.hpp"
#include "__callable/__latency_fn_frontend_node_lists_segment_count_for_rows.hpp"
#include "__callable/__latency_fn_frontend_node_lists_append_segment.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
bool_t frontend_node_lists::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == frontend_node_lists::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_node_lists_uint16_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::uint16_from_int", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[0]);
	return cast<int_t<std::uint16_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_node_lists_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[1]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_node_lists_storage_vector_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::storage_vector_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[2]);
	return __latency_fn_row_segment_policy_storage_vector_id();
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_node_lists_storage_segmented_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::storage_segmented_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[3]);
	return __latency_fn_row_segment_policy_storage_segmented_id();
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_node_lists_default_segment_capacity() {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::default_segment_capacity", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[4]);
	return __latency_fn_row_segment_policy_default_segment_capacity();
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_node_lists_segmented_threshold() {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::segmented_threshold", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[5]);
	return __latency_fn_row_segment_policy_default_segment_threshold();
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
int_t<> __latency_fn_frontend_node_lists_row_size_bytes() {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::row_size_bytes", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[6]);
	return static_cast<int_t<> >(sizeof(FrontendNodeRow));
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
FrontendNodeRow __latency_fn_frontend_node_lists_empty_row() {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::empty_row", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[7]);
	FrontendNodeRow empty = FrontendNodeRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
void __latency_fn_frontend_node_lists_ensure_defaults(shared_p<FrontendNodeList> list) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::ensure_defaults", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[8]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(list->storage_kind_id), static_cast<int_t<> >(0)))) {
		list->storage_kind_id = __latency_fn_frontend_node_lists_storage_vector_id();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(list->segment_capacity), static_cast<int_t<> >(0)))) {
		list->segment_capacity = __latency_fn_frontend_node_lists_default_segment_capacity();
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(list->segment_threshold), static_cast<int_t<> >(0)))) {
		list->segment_threshold = __latency_fn_frontend_node_lists_segmented_threshold();
	}
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
bool_t __latency_fn_frontend_node_lists_uses_segmented(shared_p<FrontendNodeList> list) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::uses_segmented", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[9]);
	return bool_t(php::identical(cast<int_t<>>(list->storage_kind_id), cast<int_t<>>(__latency_fn_frontend_node_lists_storage_segmented_id())));
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
int_t<> __latency_fn_frontend_node_lists_segment_count_for_rows(int_t<> rowCount, int_t<> segmentCapacity) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::segment_count_for_rows", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[10]);
	return __latency_fn_row_segment_policy_segment_count_for_rows(rowCount, segmentCapacity);
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
void __latency_fn_frontend_node_lists_reserve(shared_p<FrontendNodeList> list, int_t<> capacity) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::reserve", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[11]);
	__latency_fn_frontend_node_lists_ensure_defaults(list);
	if (static_cast<bool>((capacity > cast<int_t<>>(list->reserved_capacity)))) {
		list->reserved_capacity = __latency_fn_frontend_node_lists_uint32_from_int(capacity);
	}
	if (static_cast<bool>(php::condition_truthy((capacity >= cast<int_t<>>(list->segment_threshold))))) {
		__latency_fn_frontend_node_lists_activate_segmented(list, capacity);
		return;
	}
	if (static_cast<bool>((!__latency_fn_frontend_node_lists_uses_segmented(list)))) {
		php::vector_reserve(list->rows, capacity);
	}
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
void __latency_fn_frontend_node_lists_reserve_segments(shared_p<FrontendNodeList> list, int_t<> rowCapacity) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::reserve_segments", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[12]);
	int_t<> segmentCapacity = required_cast<int_t<>>(cast<int_t<>>(list->segment_capacity));
	int_t<> segmentCount = required_cast<int_t<>>(__latency_fn_frontend_node_lists_segment_count_for_rows(rowCapacity, segmentCapacity));
	if (static_cast<bool>((segmentCount > static_cast<int_t<> >(0)))) {
		php::vector_reserve(list->segments, segmentCount);
	}
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
void __latency_fn_frontend_node_lists_append_segment(shared_p<FrontendNodeList> list) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::append_segment", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[13]);
	shared_p<FrontendNodeSegment> segment = create<FrontendNodeSegment>();
	segment->segment_id = __latency_fn_frontend_node_lists_uint32_from_int((php::count(list->segments) + static_cast<int_t<> >(1)));
	php::vector_reserve(segment->rows, cast<int_t<>>(list->segment_capacity));
	(void) list->segments.append(segment);
	list->segment_count = __latency_fn_frontend_node_lists_uint32_from_int(php::count(list->segments));
}

}
