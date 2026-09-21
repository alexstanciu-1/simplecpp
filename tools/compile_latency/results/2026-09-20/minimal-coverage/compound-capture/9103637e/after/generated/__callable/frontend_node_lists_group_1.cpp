#include <scpp/lang/php.hpp>
#include "__types/FrontendNodeList.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendNodeSegment.hpp"
#include "__callable/__latency_fn_frontend_node_lists_append_segment.hpp"
#include "__callable/__latency_fn_frontend_node_lists_append_segmented.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_node_lists_activate_segmented.hpp"
#include "__callable/__latency_fn_frontend_node_lists_append_segmented.hpp"
#include "__callable/__latency_fn_frontend_node_lists_ensure_defaults.hpp"
#include "__callable/__latency_fn_frontend_node_lists_reserve_segments.hpp"
#include "__callable/__latency_fn_frontend_node_lists_storage_segmented_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uses_segmented.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_activate_segmented.hpp"
#include "__callable/__latency_fn_frontend_node_lists_append.hpp"
#include "__callable/__latency_fn_frontend_node_lists_append_segmented.hpp"
#include "__callable/__latency_fn_frontend_node_lists_ensure_defaults.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uses_segmented.hpp"
#include "__callable/__latency_fn_frontend_node_lists_row_count.hpp"
#include "__callable/__latency_fn_frontend_node_lists_has_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_empty_row.hpp"
#include "__callable/__latency_fn_frontend_node_lists_has_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_row_by_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_row_by_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_frontend_node_lists_empty_row.hpp"
#include "__callable/__latency_fn_frontend_node_lists_row_by_index.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uses_segmented.hpp"
#include "__callable/__latency_fn_frontend_node_lists_has_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_update_by_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_update_by_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_frontend_node_lists_update_by_index.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uses_segmented.hpp"
namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_node_lists_append_segmented(shared_p<FrontendNodeList> list, FrontendNodeRow row) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::append_segmented", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[14]);
	int_t<> segmentCapacity = required_cast<int_t<>>(cast<int_t<>>(list->segment_capacity));
	if (static_cast<bool>(php::identical(cast<int_t<>>(list->segment_count), static_cast<int_t<> >(0)))) {
		__latency_fn_frontend_node_lists_append_segment(list);
	}
	int_t<> segmentIndex = required_cast<int_t<>>((cast<int_t<>>(list->segment_count) - static_cast<int_t<> >(1)));
	shared_p<FrontendNodeSegment> segment = list->segments[segmentIndex];
	if (static_cast<bool>(php::condition_truthy((cast<int_t<>>(segment->row_count) >= segmentCapacity)))) {
		__latency_fn_frontend_node_lists_append_segment(list);
		segmentIndex = (cast<int_t<>>(list->segment_count) - static_cast<int_t<> >(1));
		segment = list->segments[segmentIndex];
	}
	(void) segment->rows.append(row);
	segment->row_count = __latency_fn_frontend_node_lists_uint32_from_int(php::count(segment->rows));
	list->segments[segmentIndex] = segment;
	list->row_count = __latency_fn_frontend_node_lists_uint32_from_int((cast<int_t<>>(list->row_count) + static_cast<int_t<> >(1)));
	return row->node_id;
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
void __latency_fn_frontend_node_lists_activate_segmented(shared_p<FrontendNodeList> list, int_t<> reserveCapacity) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::activate_segmented", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[15]);
	__latency_fn_frontend_node_lists_ensure_defaults(list);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_frontend_node_lists_uses_segmented(list)))) {
		__latency_fn_frontend_node_lists_reserve_segments(list, reserveCapacity);
		return;
	}
	vector_t<FrontendNodeRow> existingRows = required_cast<vector_t<FrontendNodeRow>>(list->rows);
	list->storage_kind_id = __latency_fn_frontend_node_lists_storage_segmented_id();
	list->row_count = __latency_fn_structure_row_ids_none_id();
	list->segment_count = __latency_fn_structure_row_ids_none_id();
	__latency_fn_frontend_node_lists_reserve_segments(list, reserveCapacity);
	auto& __latency_local_0 = existingRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		__latency_fn_frontend_node_lists_append_segmented(list, row);
	}
	vector_t<FrontendNodeRow> emptyRows = {};
	list->rows = emptyRows;
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_node_lists_append(shared_p<FrontendNodeList> list, FrontendNodeRow row) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::append", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[16]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->node_id), static_cast<int_t<> >(0)))) {
		row->node_id = __latency_fn_frontend_node_lists_uint32_from_int((cast<int_t<>>(list->row_count) + static_cast<int_t<> >(1)));
	}
	__latency_fn_frontend_node_lists_ensure_defaults(list);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_frontend_node_lists_uses_segmented(list)))) {
		return __latency_fn_frontend_node_lists_append_segmented(list, row);
	}
	if (static_cast<bool>(php::condition_truthy(((cast<int_t<>>(list->row_count) + static_cast<int_t<> >(1)) >= cast<int_t<>>(list->segment_threshold))))) {
		__latency_fn_frontend_node_lists_activate_segmented(list, (cast<int_t<>>(list->row_count) + static_cast<int_t<> >(1)));
		return __latency_fn_frontend_node_lists_append_segmented(list, row);
	}
	(void) list->rows.append(row);
	list->row_count = __latency_fn_frontend_node_lists_uint32_from_int(php::count(list->rows));
	return row->node_id;
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_frontend_node_lists_row_count(shared_p<FrontendNodeList> list) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::row_count", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[17]);
	return list->row_count;
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
bool_t __latency_fn_frontend_node_lists_has_id(shared_p<FrontendNodeList> list, int_t<std::uint32_t> nodeId) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::has_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[18]);
	return __latency_fn_structure_row_ids_has_dense_id(nodeId, cast<int_t<>>(list->row_count));
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
FrontendNodeRow __latency_fn_frontend_node_lists_row_by_id(shared_p<FrontendNodeList> list, int_t<std::uint32_t> nodeId) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::row_by_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[19]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_frontend_node_lists_has_id(list, cast<int_t<std::uint32_t>>(nodeId))))) {
		return __latency_fn_frontend_node_lists_row_by_index(list, __latency_fn_structure_row_ids_dense_index(nodeId));
	}
	return __latency_fn_frontend_node_lists_empty_row();
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
FrontendNodeRow __latency_fn_frontend_node_lists_row_by_index(shared_p<FrontendNodeList> list, int_t<> index) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::row_by_index", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[20]);
	if (static_cast<bool>(((index < static_cast<int_t<> >(0)) || (index >= cast<int_t<>>(list->row_count))))) {
		return __latency_fn_frontend_node_lists_empty_row();
	}
	if (static_cast<bool>((!__latency_fn_frontend_node_lists_uses_segmented(list)))) {
		return list->rows[index];
	}
	int_t<> segmentCapacity = required_cast<int_t<>>(cast<int_t<>>(list->segment_capacity));
	int_t<> segmentIndex = required_cast<int_t<>>(cast<int_t<>>((index / segmentCapacity)));
	int_t<> offset = required_cast<int_t<>>((index % segmentCapacity));
	if (static_cast<bool>(((segmentIndex >= static_cast<int_t<> >(0)) && (segmentIndex < php::count(list->segments))))) {
		shared_p<FrontendNodeSegment> segment = list->segments[segmentIndex];
		if (static_cast<bool>(((offset >= static_cast<int_t<> >(0)) && (offset < cast<int_t<>>(segment->row_count))))) {
			return segment->rows[offset];
		}
	}
	return __latency_fn_frontend_node_lists_empty_row();
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
bool_t __latency_fn_frontend_node_lists_update_by_id(shared_p<FrontendNodeList> list, FrontendNodeRow row) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::update_by_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[21]);
	if (static_cast<bool>((!__latency_fn_frontend_node_lists_has_id(list, row->node_id)))) {
		return bool_t(static_cast<bool_t>(false));
	}
	return __latency_fn_frontend_node_lists_update_by_index(list, __latency_fn_structure_row_ids_dense_index(row->node_id), row);
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
bool_t __latency_fn_frontend_node_lists_update_by_index(shared_p<FrontendNodeList> list, int_t<> index, FrontendNodeRow row) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::update_by_index", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[22]);
	if (static_cast<bool>(((index < static_cast<int_t<> >(0)) || (index >= cast<int_t<>>(list->row_count))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	if (static_cast<bool>((!__latency_fn_frontend_node_lists_uses_segmented(list)))) {
		list->rows[index] = row;
		return bool_t(static_cast<bool_t>(true));
	}
	int_t<> segmentCapacity = required_cast<int_t<>>(cast<int_t<>>(list->segment_capacity));
	int_t<> segmentIndex = required_cast<int_t<>>(cast<int_t<>>((index / segmentCapacity)));
	int_t<> offset = required_cast<int_t<>>((index % segmentCapacity));
	if (static_cast<bool>(((segmentIndex >= static_cast<int_t<> >(0)) && (segmentIndex < php::count(list->segments))))) {
		shared_p<FrontendNodeSegment> segment = list->segments[segmentIndex];
		if (static_cast<bool>(((offset >= static_cast<int_t<> >(0)) && (offset < cast<int_t<>>(segment->row_count))))) {
			segment->rows[offset] = row;
			list->segments[segmentIndex] = segment;
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}
