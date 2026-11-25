#include <scpp/lang/php.hpp>
#include "__types/FrontendNodeList.hpp"
#include "__types/FrontendNodeListOwner.hpp"
#include "__types/FrontendNodeRow.hpp"
#include "__types/FrontendNodeSegment.hpp"
#include "__types/FrontendNodeSpan.hpp"
#include "__callable/__latency_fn_frontend_node_lists_retained_node_by_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_row_by_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_empty_span.hpp"
#include "__callable/__latency_fn_frontend_node_lists_empty_span.hpp"
#include "__callable/__latency_fn_frontend_node_lists_ensure_defaults.hpp"
#include "__callable/__latency_fn_frontend_node_lists_first_span.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uses_segmented.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_empty_span.hpp"
#include "__callable/__latency_fn_frontend_node_lists_next_span.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uint32_from_int.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uses_segmented.hpp"
#include "__callable/__latency_fn_frontend_node_lists_reserved_segment_bytes.hpp"
#include "__callable/__latency_fn_frontend_node_lists_row_size_bytes.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uses_segmented.hpp"
#include "__callable/__latency_fn_row_segment_policy_reserved_segment_bytes.hpp"
#include "__callable/__latency_fn_frontend_node_lists_row_size_bytes.hpp"
#include "__callable/__latency_fn_frontend_node_lists_used_row_bytes.hpp"
#include "__callable/__latency_fn_row_segment_policy_used_row_bytes.hpp"
#include "__callable/__latency_fn_frontend_node_lists_row_size_bytes.hpp"
#include "__callable/__latency_fn_frontend_node_lists_segment_slack_bytes.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uses_segmented.hpp"
#include "__callable/__latency_fn_row_segment_policy_segment_slack_bytes.hpp"
#include "__callable/__latency_fn_frontend_node_lists_retained_old_generation_bytes.hpp"
#include "__callable/__latency_fn_frontend_node_lists_span_is_empty.hpp"
#include "__callable/__latency_fn_frontend_node_lists_empty_row.hpp"
#include "__callable/__latency_fn_frontend_node_lists_row_by_index.hpp"
#include "__callable/__latency_fn_frontend_node_lists_span_node_at.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uses_segmented.hpp"
namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
FrontendNodeRow __latency_fn_frontend_node_lists_retained_node_by_id(shared_p<FrontendNodeListOwner> owner, int_t<std::uint32_t> nodeId) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::retained_node_by_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[32]);
	return __latency_fn_frontend_node_lists_row_by_id(owner->retained_rows, cast<int_t<std::uint32_t>>(nodeId));
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
FrontendNodeSpan __latency_fn_frontend_node_lists_empty_span() {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::empty_span", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[33]);
	FrontendNodeSpan span = FrontendNodeSpan{};
	return span;
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
FrontendNodeSpan __latency_fn_frontend_node_lists_first_span(shared_p<FrontendNodeList> list) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::first_span", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[34]);
	__latency_fn_frontend_node_lists_ensure_defaults(list);
	int_t<> rowCount = required_cast<int_t<>>(cast<int_t<>>(list->row_count));
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return __latency_fn_frontend_node_lists_empty_span();
	}
	FrontendNodeSpan span = FrontendNodeSpan{};
	span->start_index = __latency_fn_structure_row_ids_none_id();
	span->storage_kind_id = list->storage_kind_id;
	if (static_cast<bool>(php::condition_truthy(__latency_fn_frontend_node_lists_uses_segmented(list)))) {
		shared_p<FrontendNodeSegment> segment = list->segments[static_cast<int_t<> >(0)];
		span->count = segment->row_count;
		span->next_index = segment->row_count;
		span->segment_index = __latency_fn_structure_row_ids_none_id();
		span->segment_offset = __latency_fn_structure_row_ids_none_id();
	}
	else {
		span->count = __latency_fn_frontend_node_lists_uint32_from_int(rowCount);
		span->next_index = __latency_fn_frontend_node_lists_uint32_from_int(rowCount);
	}
	span->flags = __latency_fn_frontend_node_lists_uint16_from_int(static_cast<int_t<> >(1));
	return span;
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
FrontendNodeSpan __latency_fn_frontend_node_lists_next_span(shared_p<FrontendNodeList> list, FrontendNodeSpan span) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::next_span", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[35]);
	if (static_cast<bool>((!__latency_fn_frontend_node_lists_uses_segmented(list)))) {
		return __latency_fn_frontend_node_lists_empty_span();
	}
	int_t<> nextIndex = required_cast<int_t<>>(cast<int_t<>>(span->next_index));
	if (static_cast<bool>(php::condition_truthy((nextIndex >= cast<int_t<>>(list->row_count))))) {
		return __latency_fn_frontend_node_lists_empty_span();
	}
	int_t<> segmentCapacity = required_cast<int_t<>>(cast<int_t<>>(list->segment_capacity));
	int_t<> segmentIndex = required_cast<int_t<>>(cast<int_t<>>((nextIndex / segmentCapacity)));
	int_t<> segmentOffset = required_cast<int_t<>>((nextIndex % segmentCapacity));
	if (static_cast<bool>(((segmentIndex < static_cast<int_t<> >(0)) || (segmentIndex >= php::count(list->segments))))) {
		return __latency_fn_frontend_node_lists_empty_span();
	}
	shared_p<FrontendNodeSegment> segment = list->segments[segmentIndex];
	int_t<> available = required_cast<int_t<>>((cast<int_t<>>(segment->row_count) - segmentOffset));
	if (static_cast<bool>((available <= static_cast<int_t<> >(0)))) {
		return __latency_fn_frontend_node_lists_empty_span();
	}
	FrontendNodeSpan nextSpan = FrontendNodeSpan{};
	nextSpan->start_index = __latency_fn_frontend_node_lists_uint32_from_int(nextIndex);
	nextSpan->count = __latency_fn_frontend_node_lists_uint32_from_int(available);
	nextSpan->next_index = __latency_fn_frontend_node_lists_uint32_from_int((nextIndex + available));
	nextSpan->segment_index = __latency_fn_frontend_node_lists_uint32_from_int(segmentIndex);
	nextSpan->segment_offset = __latency_fn_frontend_node_lists_uint32_from_int(segmentOffset);
	nextSpan->storage_kind_id = list->storage_kind_id;
	nextSpan->flags = __latency_fn_frontend_node_lists_uint16_from_int(static_cast<int_t<> >(1));
	return nextSpan;
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
int_t<> __latency_fn_frontend_node_lists_reserved_segment_bytes(shared_p<FrontendNodeList> list) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::reserved_segment_bytes", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[36]);
	if (static_cast<bool>((!__latency_fn_frontend_node_lists_uses_segmented(list)))) {
		return static_cast<int_t<> >(0);
	}
	return __latency_fn_row_segment_policy_reserved_segment_bytes(list->segment_count, list->segment_capacity, __latency_fn_frontend_node_lists_row_size_bytes());
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
int_t<> __latency_fn_frontend_node_lists_used_row_bytes(shared_p<FrontendNodeList> list) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::used_row_bytes", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[37]);
	return __latency_fn_row_segment_policy_used_row_bytes(list->row_count, __latency_fn_frontend_node_lists_row_size_bytes());
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
int_t<> __latency_fn_frontend_node_lists_segment_slack_bytes(shared_p<FrontendNodeList> list) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::segment_slack_bytes", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[38]);
	if (static_cast<bool>((!__latency_fn_frontend_node_lists_uses_segmented(list)))) {
		return static_cast<int_t<> >(0);
	}
	return __latency_fn_row_segment_policy_segment_slack_bytes(list->row_count, list->segment_count, list->segment_capacity, __latency_fn_frontend_node_lists_row_size_bytes());
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
int_t<> __latency_fn_frontend_node_lists_retained_old_generation_bytes(shared_p<FrontendNodeList> list) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::retained_old_generation_bytes", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[39]);
	return static_cast<int_t<> >(0);
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
bool_t __latency_fn_frontend_node_lists_span_is_empty(FrontendNodeSpan span) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::span_is_empty", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[40]);
	return bool_t(php::identical(cast<int_t<>>(span->count), static_cast<int_t<> >(0)));
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
FrontendNodeRow __latency_fn_frontend_node_lists_span_node_at(shared_p<FrontendNodeList> list, FrontendNodeSpan span, int_t<> offset) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::span_node_at", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[41]);
	if (static_cast<bool>(((offset < static_cast<int_t<> >(0)) || (offset >= cast<int_t<>>(span->count))))) {
		return __latency_fn_frontend_node_lists_empty_row();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_frontend_node_lists_uses_segmented(list)))) {
		int_t<> segmentIndex = required_cast<int_t<>>(cast<int_t<>>(span->segment_index));
		int_t<> segmentOffset = required_cast<int_t<>>((cast<int_t<>>(span->segment_offset) + offset));
		if (static_cast<bool>(((segmentIndex >= static_cast<int_t<> >(0)) && (segmentIndex < php::count(list->segments))))) {
			shared_p<FrontendNodeSegment> segment = list->segments[segmentIndex];
			if (static_cast<bool>(((segmentOffset >= static_cast<int_t<> >(0)) && (segmentOffset < cast<int_t<>>(segment->row_count))))) {
				return segment->rows[segmentOffset];
			}
		}
		return __latency_fn_frontend_node_lists_empty_row();
	}
	int_t<> index = required_cast<int_t<>>((cast<int_t<>>(span->start_index) + offset));
	return __latency_fn_frontend_node_lists_row_by_index(list, index);
}

}
