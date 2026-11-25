#include <scpp/lang/php.hpp>
#include "__types/TokenListRef.hpp"
#include "__types/TokenRow.hpp"
#include "__types/TokenRowList.hpp"
#include "__types/TokenRowSegment.hpp"
#include "__callable/__latency_fn_token_row_lists_append_segment.hpp"
#include "__callable/__latency_fn_token_row_lists_append_segmented.hpp"
#include "__callable/__latency_fn_token_row_lists_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_token_row_lists_activate_segmented.hpp"
#include "__callable/__latency_fn_token_row_lists_append_segmented.hpp"
#include "__callable/__latency_fn_token_row_lists_ensure_defaults.hpp"
#include "__callable/__latency_fn_token_row_lists_reserve_segments.hpp"
#include "__callable/__latency_fn_token_row_lists_storage_segmented_id.hpp"
#include "__callable/__latency_fn_token_row_lists_uses_segmented.hpp"
#include "__callable/__latency_fn_token_row_lists_activate_segmented.hpp"
#include "__callable/__latency_fn_token_row_lists_append.hpp"
#include "__callable/__latency_fn_token_row_lists_append_segmented.hpp"
#include "__callable/__latency_fn_token_row_lists_ensure_defaults.hpp"
#include "__callable/__latency_fn_token_row_lists_uint32_from_int.hpp"
#include "__callable/__latency_fn_token_row_lists_uses_segmented.hpp"
#include "__callable/__latency_fn_token_row_lists_row_count.hpp"
#include "__callable/__latency_fn_token_row_lists_has_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_token_row_lists_empty_row.hpp"
#include "__callable/__latency_fn_token_row_lists_row_by_id.hpp"
#include "__callable/__latency_fn_token_row_lists_row_by_index.hpp"
#include "__callable/__latency_fn_token_row_lists_empty_row.hpp"
#include "__callable/__latency_fn_token_row_lists_has_index.hpp"
#include "__callable/__latency_fn_token_row_lists_row_by_index.hpp"
#include "__callable/__latency_fn_token_row_lists_uses_segmented.hpp"
#include "__callable/__latency_fn_row_segment_policy_initial_list_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_token_row_lists_list_ref.hpp"
#include "__callable/__latency_fn_token_row_lists_list_ref_for_generation.hpp"
#include "__callable/__latency_fn_row_segment_policy_list_ref_ready_flag.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_token_row_lists_ensure_defaults.hpp"
#include "__callable/__latency_fn_token_row_lists_list_ref_for_generation.hpp"
#include "__callable/__latency_fn_token_row_lists_reserved_segment_bytes.hpp"
#include "__callable/__latency_fn_token_row_lists_segment_slack_bytes.hpp"
#include "__callable/__latency_fn_token_row_lists_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_token_row_lists[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_token_row_lists_append_segmented(shared_p<TokenRowList> list, TokenRow row) {
	SCPP_CALL_DEPTH_GUARD("token_row_lists::append_segmented", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_row_lists.phs", __latency_lines_token_row_lists[14]);
	int_t<> segmentCapacity = required_cast<int_t<>>(cast<int_t<>>(list->segment_capacity));
	if (static_cast<bool>(php::identical(cast<int_t<>>(list->segment_count), static_cast<int_t<> >(0)))) {
		__latency_fn_token_row_lists_append_segment(list);
	}
	int_t<> segmentIndex = required_cast<int_t<>>((cast<int_t<>>(list->segment_count) - static_cast<int_t<> >(1)));
	shared_p<TokenRowSegment> segment = list->segments[segmentIndex];
	if (static_cast<bool>(php::condition_truthy((cast<int_t<>>(segment->row_count) >= segmentCapacity)))) {
		__latency_fn_token_row_lists_append_segment(list);
		segmentIndex = (cast<int_t<>>(list->segment_count) - static_cast<int_t<> >(1));
		segment = list->segments[segmentIndex];
	}
	(void) segment->rows.append(row);
	segment->row_count = __latency_fn_token_row_lists_uint32_from_int(php::count(segment->rows));
	list->segments[segmentIndex] = segment;
	list->row_count = __latency_fn_token_row_lists_uint32_from_int((cast<int_t<>>(list->row_count) + static_cast<int_t<> >(1)));
	return list->row_count;
}

}

namespace scpp { extern const int __latency_lines_token_row_lists[]; }
namespace scpp {
void __latency_fn_token_row_lists_activate_segmented(shared_p<TokenRowList> list, int_t<> reserveCapacity) {
	SCPP_CALL_DEPTH_GUARD("token_row_lists::activate_segmented", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_row_lists.phs", __latency_lines_token_row_lists[15]);
	__latency_fn_token_row_lists_ensure_defaults(list);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_token_row_lists_uses_segmented(list)))) {
		__latency_fn_token_row_lists_reserve_segments(list, reserveCapacity);
		return;
	}
	vector_t<TokenRow> existingRows = required_cast<vector_t<TokenRow>>(list->rows);
	list->storage_kind_id = __latency_fn_token_row_lists_storage_segmented_id();
	list->row_count = __latency_fn_structure_row_ids_none_id();
	list->segment_count = __latency_fn_structure_row_ids_none_id();
	__latency_fn_token_row_lists_reserve_segments(list, reserveCapacity);
	auto& __latency_local_0 = existingRows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		__latency_fn_token_row_lists_append_segmented(list, row);
	}
	vector_t<TokenRow> emptyRows = {};
	list->rows = emptyRows;
}

}

namespace scpp { extern const int __latency_lines_token_row_lists[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_token_row_lists_append(shared_p<TokenRowList> list, TokenRow row) {
	SCPP_CALL_DEPTH_GUARD("token_row_lists::append", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_row_lists.phs", __latency_lines_token_row_lists[16]);
	__latency_fn_token_row_lists_ensure_defaults(list);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_token_row_lists_uses_segmented(list)))) {
		return __latency_fn_token_row_lists_append_segmented(list, row);
	}
	if (static_cast<bool>(php::condition_truthy(((cast<int_t<>>(list->row_count) + static_cast<int_t<> >(1)) >= cast<int_t<>>(list->segment_threshold))))) {
		__latency_fn_token_row_lists_activate_segmented(list, (cast<int_t<>>(list->row_count) + static_cast<int_t<> >(1)));
		return __latency_fn_token_row_lists_append_segmented(list, row);
	}
	(void) list->rows.append(row);
	list->row_count = __latency_fn_token_row_lists_uint32_from_int(php::count(list->rows));
	return list->row_count;
}

}

namespace scpp { extern const int __latency_lines_token_row_lists[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_token_row_lists_row_count(shared_p<TokenRowList> list) {
	SCPP_CALL_DEPTH_GUARD("token_row_lists::row_count", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_row_lists.phs", __latency_lines_token_row_lists[17]);
	return list->row_count;
}

}

namespace scpp { extern const int __latency_lines_token_row_lists[]; }
namespace scpp {
bool_t __latency_fn_token_row_lists_has_index(shared_p<TokenRowList> list, int_t<> index) {
	SCPP_CALL_DEPTH_GUARD("token_row_lists::has_index", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_row_lists.phs", __latency_lines_token_row_lists[18]);
	return ((index >= static_cast<int_t<> >(0)) && (index < cast<int_t<>>(list->row_count)));
}

}

namespace scpp { extern const int __latency_lines_token_row_lists[]; }
namespace scpp {
TokenRow __latency_fn_token_row_lists_row_by_id(shared_p<TokenRowList> list, int_t<std::uint32_t> rowId) {
	SCPP_CALL_DEPTH_GUARD("token_row_lists::row_by_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_row_lists.phs", __latency_lines_token_row_lists[19]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(rowId, cast<int_t<>>(list->row_count))))) {
		return __latency_fn_token_row_lists_row_by_index(list, __latency_fn_structure_row_ids_dense_index(rowId));
	}
	return __latency_fn_token_row_lists_empty_row();
}

}

namespace scpp { extern const int __latency_lines_token_row_lists[]; }
namespace scpp {
TokenRow __latency_fn_token_row_lists_row_by_index(shared_p<TokenRowList> list, int_t<> index) {
	SCPP_CALL_DEPTH_GUARD("token_row_lists::row_by_index", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_row_lists.phs", __latency_lines_token_row_lists[20]);
	if (static_cast<bool>((!__latency_fn_token_row_lists_has_index(list, index)))) {
		return __latency_fn_token_row_lists_empty_row();
	}
	if (static_cast<bool>((!__latency_fn_token_row_lists_uses_segmented(list)))) {
		return list->rows[index];
	}
	int_t<> segmentCapacity = required_cast<int_t<>>(cast<int_t<>>(list->segment_capacity));
	int_t<> segmentIndex = required_cast<int_t<>>(cast<int_t<>>((index / segmentCapacity)));
	int_t<> offset = required_cast<int_t<>>((index % segmentCapacity));
	if (static_cast<bool>(((segmentIndex >= static_cast<int_t<> >(0)) && (segmentIndex < php::count(list->segments))))) {
		shared_p<TokenRowSegment> segment = list->segments[segmentIndex];
		if (static_cast<bool>(((offset >= static_cast<int_t<> >(0)) && (offset < cast<int_t<>>(segment->row_count))))) {
			return segment->rows[offset];
		}
	}
	return __latency_fn_token_row_lists_empty_row();
}

}

namespace scpp { extern const int __latency_lines_token_row_lists[]; }
namespace scpp {
TokenListRef __latency_fn_token_row_lists_list_ref(shared_p<TokenRowList> list, int_t<std::uint32_t> ownerSourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("token_row_lists::list_ref", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_row_lists.phs", __latency_lines_token_row_lists[21]);
	return __latency_fn_token_row_lists_list_ref_for_generation(list, cast<int_t<std::uint32_t>>(ownerSourceUnitId), __latency_fn_row_segment_policy_initial_list_id(), __latency_fn_structure_row_ids_none_id());
}

}

namespace scpp { extern const int __latency_lines_token_row_lists[]; }
namespace scpp {
TokenListRef __latency_fn_token_row_lists_list_ref_for_generation(shared_p<TokenRowList> list, int_t<std::uint32_t> ownerSourceUnitId, int_t<std::uint32_t> listId, int_t<std::uint32_t> generationId) {
	SCPP_CALL_DEPTH_GUARD("token_row_lists::list_ref_for_generation", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/token_row_lists.phs", __latency_lines_token_row_lists[22]);
	__latency_fn_token_row_lists_ensure_defaults(list);
	TokenListRef ref = TokenListRef{};
	ref->list_id = listId;
	ref->owner_source_unit_id = ownerSourceUnitId;
	ref->generation_id = generationId;
	ref->row_count = list->row_count;
	ref->first_index = __latency_fn_structure_row_ids_none_id();
	ref->segment_capacity = list->segment_capacity;
	ref->segment_count = list->segment_count;
	ref->reserved_segment_bytes = __latency_fn_token_row_lists_uint32_from_int(__latency_fn_token_row_lists_reserved_segment_bytes(list));
	ref->segment_slack_bytes = __latency_fn_token_row_lists_uint32_from_int(__latency_fn_token_row_lists_segment_slack_bytes(list));
	ref->storage_kind_id = list->storage_kind_id;
	ref->flags = __latency_fn_row_segment_policy_list_ref_ready_flag();
	return ref;
}

}
