#include <scpp/lang/php.hpp>
#include "__types/BackendRequestAuthorizationRow.hpp"
#include "__types/BackendRequestListRef.hpp"
#include "__types/BackendRequestRowList.hpp"
#include "__types/BackendRequestRowSpan.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_empty_row.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_has_id.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_row_by_id.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_row_by_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_empty_row.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_row_by_index.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_ensure_defaults.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_list_ref_for_generation.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_reserved_segment_bytes.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_segment_slack_bytes.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_uint32_from_int.hpp"
#include "__callable/__latency_fn_row_segment_policy_list_ref_ready_flag.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_empty_span.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_ensure_defaults.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_first_span.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_uint32_from_int.hpp"
#include "__callable/__latency_fn_row_segment_policy_list_ref_ready_flag.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_empty_span.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_next_span.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_empty_span.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_span_is_empty.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_empty_row.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_row_by_index.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_span_request_at.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_reserved_segment_bytes.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_row_size_bytes.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_used_row_bytes.hpp"
#include "__callable/__latency_fn_row_segment_policy_used_row_bytes.hpp"
#include "__callable/__latency_fn_backend_request_row_lists_segment_slack_bytes.hpp"
namespace scpp { extern const int __latency_lines_backend_request_row_lists[]; }
namespace scpp {
BackendRequestAuthorizationRow __latency_fn_backend_request_row_lists_row_by_id(shared_p<BackendRequestRowList> list, int_t<std::uint32_t> rowId) {
	SCPP_CALL_DEPTH_GUARD("backend_request_row_lists::row_by_id", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/backend_request_row_lists.phs", __latency_lines_backend_request_row_lists[14]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_backend_request_row_lists_has_id(list, cast<int_t<std::uint32_t>>(rowId))))) {
		return __latency_fn_backend_request_row_lists_row_by_index(list, __latency_fn_structure_row_ids_dense_index(rowId));
	}
	return __latency_fn_backend_request_row_lists_empty_row();
}

}

namespace scpp { extern const int __latency_lines_backend_request_row_lists[]; }
namespace scpp {
BackendRequestAuthorizationRow __latency_fn_backend_request_row_lists_row_by_index(shared_p<BackendRequestRowList> list, int_t<> index) {
	SCPP_CALL_DEPTH_GUARD("backend_request_row_lists::row_by_index", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/backend_request_row_lists.phs", __latency_lines_backend_request_row_lists[15]);
	if (static_cast<bool>(((index < static_cast<int_t<> >(0)) || (index >= cast<int_t<>>(list->row_count))))) {
		return __latency_fn_backend_request_row_lists_empty_row();
	}
	return list->rows[index];
}

}

namespace scpp { extern const int __latency_lines_backend_request_row_lists[]; }
namespace scpp {
BackendRequestListRef __latency_fn_backend_request_row_lists_list_ref_for_generation(shared_p<BackendRequestRowList> list, int_t<std::uint32_t> ownerSourceUnitId, int_t<std::uint32_t> ownerSymbolId, int_t<std::uint32_t> listId, int_t<std::uint32_t> generationId) {
	SCPP_CALL_DEPTH_GUARD("backend_request_row_lists::list_ref_for_generation", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/backend_request_row_lists.phs", __latency_lines_backend_request_row_lists[16]);
	__latency_fn_backend_request_row_lists_ensure_defaults(list);
	BackendRequestListRef ref = BackendRequestListRef{};
	ref->list_id = listId;
	ref->owner_source_unit_id = ownerSourceUnitId;
	ref->owner_symbol_id = ownerSymbolId;
	ref->generation_id = generationId;
	ref->row_count = list->row_count;
	ref->first_index = __latency_fn_structure_row_ids_none_id();
	ref->segment_capacity = list->segment_capacity;
	ref->segment_count = list->segment_count;
	ref->reserved_segment_bytes = __latency_fn_backend_request_row_lists_uint32_from_int(__latency_fn_backend_request_row_lists_reserved_segment_bytes(list));
	ref->segment_slack_bytes = __latency_fn_backend_request_row_lists_uint32_from_int(__latency_fn_backend_request_row_lists_segment_slack_bytes(list));
	ref->storage_kind_id = list->storage_kind_id;
	ref->flags = __latency_fn_row_segment_policy_list_ref_ready_flag();
	return ref;
}

}

namespace scpp { extern const int __latency_lines_backend_request_row_lists[]; }
namespace scpp {
BackendRequestRowSpan __latency_fn_backend_request_row_lists_first_span(shared_p<BackendRequestRowList> list) {
	SCPP_CALL_DEPTH_GUARD("backend_request_row_lists::first_span", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/backend_request_row_lists.phs", __latency_lines_backend_request_row_lists[17]);
	__latency_fn_backend_request_row_lists_ensure_defaults(list);
	int_t<> rowCount = required_cast<int_t<>>(cast<int_t<>>(list->row_count));
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return __latency_fn_backend_request_row_lists_empty_span();
	}
	BackendRequestRowSpan span = BackendRequestRowSpan{};
	span->start_index = __latency_fn_structure_row_ids_none_id();
	span->count = __latency_fn_backend_request_row_lists_uint32_from_int(rowCount);
	span->next_index = __latency_fn_backend_request_row_lists_uint32_from_int(rowCount);
	span->storage_kind_id = list->storage_kind_id;
	span->flags = __latency_fn_row_segment_policy_list_ref_ready_flag();
	return span;
}

}

namespace scpp { extern const int __latency_lines_backend_request_row_lists[]; }
namespace scpp {
BackendRequestRowSpan __latency_fn_backend_request_row_lists_next_span(shared_p<BackendRequestRowList> list, BackendRequestRowSpan span) {
	SCPP_CALL_DEPTH_GUARD("backend_request_row_lists::next_span", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/backend_request_row_lists.phs", __latency_lines_backend_request_row_lists[18]);
	return __latency_fn_backend_request_row_lists_empty_span();
}

}

namespace scpp { extern const int __latency_lines_backend_request_row_lists[]; }
namespace scpp {
BackendRequestRowSpan __latency_fn_backend_request_row_lists_empty_span() {
	SCPP_CALL_DEPTH_GUARD("backend_request_row_lists::empty_span", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/backend_request_row_lists.phs", __latency_lines_backend_request_row_lists[19]);
	BackendRequestRowSpan span = BackendRequestRowSpan{};
	return span;
}

}

namespace scpp { extern const int __latency_lines_backend_request_row_lists[]; }
namespace scpp {
bool_t __latency_fn_backend_request_row_lists_span_is_empty(BackendRequestRowSpan span) {
	SCPP_CALL_DEPTH_GUARD("backend_request_row_lists::span_is_empty", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/backend_request_row_lists.phs", __latency_lines_backend_request_row_lists[20]);
	return bool_t(php::identical(cast<int_t<>>(span->count), static_cast<int_t<> >(0)));
}

}

namespace scpp { extern const int __latency_lines_backend_request_row_lists[]; }
namespace scpp {
BackendRequestAuthorizationRow __latency_fn_backend_request_row_lists_span_request_at(shared_p<BackendRequestRowList> list, BackendRequestRowSpan span, int_t<> offset) {
	SCPP_CALL_DEPTH_GUARD("backend_request_row_lists::span_request_at", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/backend_request_row_lists.phs", __latency_lines_backend_request_row_lists[21]);
	if (static_cast<bool>(((offset < static_cast<int_t<> >(0)) || (offset >= cast<int_t<>>(span->count))))) {
		return __latency_fn_backend_request_row_lists_empty_row();
	}
	int_t<> index = required_cast<int_t<>>((cast<int_t<>>(span->start_index) + offset));
	return __latency_fn_backend_request_row_lists_row_by_index(list, index);
}

}

namespace scpp { extern const int __latency_lines_backend_request_row_lists[]; }
namespace scpp {
int_t<> __latency_fn_backend_request_row_lists_reserved_segment_bytes(shared_p<BackendRequestRowList> list) {
	SCPP_CALL_DEPTH_GUARD("backend_request_row_lists::reserved_segment_bytes", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/backend_request_row_lists.phs", __latency_lines_backend_request_row_lists[22]);
	return static_cast<int_t<> >(0);
}

}

namespace scpp { extern const int __latency_lines_backend_request_row_lists[]; }
namespace scpp {
int_t<> __latency_fn_backend_request_row_lists_used_row_bytes(shared_p<BackendRequestRowList> list) {
	SCPP_CALL_DEPTH_GUARD("backend_request_row_lists::used_row_bytes", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/backend_request_row_lists.phs", __latency_lines_backend_request_row_lists[23]);
	return __latency_fn_row_segment_policy_used_row_bytes(list->row_count, __latency_fn_backend_request_row_lists_row_size_bytes());
}

}

namespace scpp { extern const int __latency_lines_backend_request_row_lists[]; }
namespace scpp {
int_t<> __latency_fn_backend_request_row_lists_segment_slack_bytes(shared_p<BackendRequestRowList> list) {
	SCPP_CALL_DEPTH_GUARD("backend_request_row_lists::segment_slack_bytes", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/backend_request_row_lists.phs", __latency_lines_backend_request_row_lists[24]);
	return static_cast<int_t<> >(0);
}

}
