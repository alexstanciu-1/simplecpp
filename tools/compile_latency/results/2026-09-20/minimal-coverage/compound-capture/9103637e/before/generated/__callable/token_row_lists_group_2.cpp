#include <scpp/lang/php.hpp>
#include "__types/TokenRow.hpp"
#include "__types/TokenRowList.hpp"
#include "__types/TokenRowSegment.hpp"
#include "__types/TokenRowSpan.hpp"
#include "__callable/__latency_fn_token_row_lists_empty_span.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_token_row_lists_empty_span.hpp"
#include "__callable/__latency_fn_token_row_lists_ensure_defaults.hpp"
#include "__callable/__latency_fn_token_row_lists_first_span.hpp"
#include "__callable/__latency_fn_token_row_lists_uint16_from_int.hpp"
#include "__callable/__latency_fn_token_row_lists_uint32_from_int.hpp"
#include "__callable/__latency_fn_token_row_lists_uses_segmented.hpp"
#include "__callable/__latency_fn_token_row_lists_empty_span.hpp"
#include "__callable/__latency_fn_token_row_lists_next_span.hpp"
#include "__callable/__latency_fn_token_row_lists_uint16_from_int.hpp"
#include "__callable/__latency_fn_token_row_lists_uint32_from_int.hpp"
#include "__callable/__latency_fn_token_row_lists_uses_segmented.hpp"
#include "__callable/__latency_fn_row_segment_policy_reserved_segment_bytes.hpp"
#include "__callable/__latency_fn_token_row_lists_reserved_segment_bytes.hpp"
#include "__callable/__latency_fn_token_row_lists_row_size_bytes.hpp"
#include "__callable/__latency_fn_token_row_lists_uses_segmented.hpp"
#include "__callable/__latency_fn_row_segment_policy_used_row_bytes.hpp"
#include "__callable/__latency_fn_token_row_lists_row_size_bytes.hpp"
#include "__callable/__latency_fn_token_row_lists_used_row_bytes.hpp"
#include "__callable/__latency_fn_row_segment_policy_segment_slack_bytes.hpp"
#include "__callable/__latency_fn_token_row_lists_row_size_bytes.hpp"
#include "__callable/__latency_fn_token_row_lists_segment_slack_bytes.hpp"
#include "__callable/__latency_fn_token_row_lists_uses_segmented.hpp"
#include "__callable/__latency_fn_token_row_lists_retained_old_generation_bytes.hpp"
#include "__callable/__latency_fn_token_row_lists_span_is_empty.hpp"
#include "__callable/__latency_fn_token_row_lists_empty_row.hpp"
#include "__callable/__latency_fn_token_row_lists_row_by_index.hpp"
#include "__callable/__latency_fn_token_row_lists_span_token_at.hpp"
#include "__callable/__latency_fn_token_row_lists_uses_segmented.hpp"
namespace scpp { extern const int __latency_lines_token_row_lists[]; }
namespace scpp {
TokenRowSpan __latency_fn_token_row_lists_empty_span() {
	SCPP_CALL_DEPTH_GUARD("token_row_lists::empty_span", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_row_lists.phs", __latency_lines_token_row_lists[23]);
	TokenRowSpan span = TokenRowSpan{};
	return span;
}

}

namespace scpp { extern const int __latency_lines_token_row_lists[]; }
namespace scpp {
TokenRowSpan __latency_fn_token_row_lists_first_span(shared_p<TokenRowList> list) {
	SCPP_CALL_DEPTH_GUARD("token_row_lists::first_span", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_row_lists.phs", __latency_lines_token_row_lists[24]);
	__latency_fn_token_row_lists_ensure_defaults(list);
	int_t<> rowCount = required_cast<int_t<>>(cast<int_t<>>(list->row_count));
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return __latency_fn_token_row_lists_empty_span();
	}
	TokenRowSpan span = TokenRowSpan{};
	span->start_index = __latency_fn_structure_row_ids_none_id();
	span->storage_kind_id = list->storage_kind_id;
	if (static_cast<bool>(php::condition_truthy(__latency_fn_token_row_lists_uses_segmented(list)))) {
		shared_p<TokenRowSegment> segment = list->segments[static_cast<int_t<> >(0)];
		span->count = segment->row_count;
		span->next_index = segment->row_count;
		span->segment_index = __latency_fn_structure_row_ids_none_id();
		span->segment_offset = __latency_fn_structure_row_ids_none_id();
	}
	else {
		span->count = __latency_fn_token_row_lists_uint32_from_int(rowCount);
		span->next_index = __latency_fn_token_row_lists_uint32_from_int(rowCount);
	}
	span->flags = __latency_fn_token_row_lists_uint16_from_int(static_cast<int_t<> >(1));
	return span;
}

}

namespace scpp { extern const int __latency_lines_token_row_lists[]; }
namespace scpp {
TokenRowSpan __latency_fn_token_row_lists_next_span(shared_p<TokenRowList> list, TokenRowSpan span) {
	SCPP_CALL_DEPTH_GUARD("token_row_lists::next_span", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_row_lists.phs", __latency_lines_token_row_lists[25]);
	if (static_cast<bool>((!__latency_fn_token_row_lists_uses_segmented(list)))) {
		return __latency_fn_token_row_lists_empty_span();
	}
	int_t<> nextIndex = required_cast<int_t<>>(cast<int_t<>>(span->next_index));
	if (static_cast<bool>(php::condition_truthy((nextIndex >= cast<int_t<>>(list->row_count))))) {
		return __latency_fn_token_row_lists_empty_span();
	}
	int_t<> segmentCapacity = required_cast<int_t<>>(cast<int_t<>>(list->segment_capacity));
	int_t<> segmentIndex = required_cast<int_t<>>(cast<int_t<>>((nextIndex / segmentCapacity)));
	int_t<> segmentOffset = required_cast<int_t<>>((nextIndex % segmentCapacity));
	if (static_cast<bool>(((segmentIndex < static_cast<int_t<> >(0)) || (segmentIndex >= php::count(list->segments))))) {
		return __latency_fn_token_row_lists_empty_span();
	}
	shared_p<TokenRowSegment> segment = list->segments[segmentIndex];
	int_t<> available = required_cast<int_t<>>((cast<int_t<>>(segment->row_count) - segmentOffset));
	if (static_cast<bool>((available <= static_cast<int_t<> >(0)))) {
		return __latency_fn_token_row_lists_empty_span();
	}
	TokenRowSpan nextSpan = TokenRowSpan{};
	nextSpan->start_index = __latency_fn_token_row_lists_uint32_from_int(nextIndex);
	nextSpan->count = __latency_fn_token_row_lists_uint32_from_int(available);
	nextSpan->next_index = __latency_fn_token_row_lists_uint32_from_int((nextIndex + available));
	nextSpan->segment_index = __latency_fn_token_row_lists_uint32_from_int(segmentIndex);
	nextSpan->segment_offset = __latency_fn_token_row_lists_uint32_from_int(segmentOffset);
	nextSpan->storage_kind_id = list->storage_kind_id;
	nextSpan->flags = __latency_fn_token_row_lists_uint16_from_int(static_cast<int_t<> >(1));
	return nextSpan;
}

}

namespace scpp { extern const int __latency_lines_token_row_lists[]; }
namespace scpp {
int_t<> __latency_fn_token_row_lists_reserved_segment_bytes(shared_p<TokenRowList> list) {
	SCPP_CALL_DEPTH_GUARD("token_row_lists::reserved_segment_bytes", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_row_lists.phs", __latency_lines_token_row_lists[26]);
	if (static_cast<bool>((!__latency_fn_token_row_lists_uses_segmented(list)))) {
		return static_cast<int_t<> >(0);
	}
	return __latency_fn_row_segment_policy_reserved_segment_bytes(list->segment_count, list->segment_capacity, __latency_fn_token_row_lists_row_size_bytes());
}

}

namespace scpp { extern const int __latency_lines_token_row_lists[]; }
namespace scpp {
int_t<> __latency_fn_token_row_lists_used_row_bytes(shared_p<TokenRowList> list) {
	SCPP_CALL_DEPTH_GUARD("token_row_lists::used_row_bytes", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_row_lists.phs", __latency_lines_token_row_lists[27]);
	return __latency_fn_row_segment_policy_used_row_bytes(list->row_count, __latency_fn_token_row_lists_row_size_bytes());
}

}

namespace scpp { extern const int __latency_lines_token_row_lists[]; }
namespace scpp {
int_t<> __latency_fn_token_row_lists_segment_slack_bytes(shared_p<TokenRowList> list) {
	SCPP_CALL_DEPTH_GUARD("token_row_lists::segment_slack_bytes", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_row_lists.phs", __latency_lines_token_row_lists[28]);
	if (static_cast<bool>((!__latency_fn_token_row_lists_uses_segmented(list)))) {
		return static_cast<int_t<> >(0);
	}
	return __latency_fn_row_segment_policy_segment_slack_bytes(list->row_count, list->segment_count, list->segment_capacity, __latency_fn_token_row_lists_row_size_bytes());
}

}

namespace scpp { extern const int __latency_lines_token_row_lists[]; }
namespace scpp {
int_t<> __latency_fn_token_row_lists_retained_old_generation_bytes(shared_p<TokenRowList> list) {
	SCPP_CALL_DEPTH_GUARD("token_row_lists::retained_old_generation_bytes", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_row_lists.phs", __latency_lines_token_row_lists[29]);
	return static_cast<int_t<> >(0);
}

}

namespace scpp { extern const int __latency_lines_token_row_lists[]; }
namespace scpp {
bool_t __latency_fn_token_row_lists_span_is_empty(TokenRowSpan span) {
	SCPP_CALL_DEPTH_GUARD("token_row_lists::span_is_empty", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_row_lists.phs", __latency_lines_token_row_lists[30]);
	return bool_t(php::identical(cast<int_t<>>(span->count), static_cast<int_t<> >(0)));
}

}

namespace scpp { extern const int __latency_lines_token_row_lists[]; }
namespace scpp {
TokenRow __latency_fn_token_row_lists_span_token_at(shared_p<TokenRowList> list, TokenRowSpan span, int_t<> offset) {
	SCPP_CALL_DEPTH_GUARD("token_row_lists::span_token_at", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_row_lists.phs", __latency_lines_token_row_lists[31]);
	if (static_cast<bool>(((offset < static_cast<int_t<> >(0)) || (offset >= cast<int_t<>>(span->count))))) {
		return __latency_fn_token_row_lists_empty_row();
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_token_row_lists_uses_segmented(list)))) {
		int_t<> segmentIndex = required_cast<int_t<>>(cast<int_t<>>(span->segment_index));
		int_t<> segmentOffset = required_cast<int_t<>>((cast<int_t<>>(span->segment_offset) + offset));
		if (static_cast<bool>(((segmentIndex >= static_cast<int_t<> >(0)) && (segmentIndex < php::count(list->segments))))) {
			shared_p<TokenRowSegment> segment = list->segments[segmentIndex];
			if (static_cast<bool>(((segmentOffset >= static_cast<int_t<> >(0)) && (segmentOffset < cast<int_t<>>(segment->row_count))))) {
				return segment->rows[segmentOffset];
			}
		}
		return __latency_fn_token_row_lists_empty_row();
	}
	int_t<> index = required_cast<int_t<>>((cast<int_t<>>(span->start_index) + offset));
	return __latency_fn_token_row_lists_row_by_index(list, index);
}

}
