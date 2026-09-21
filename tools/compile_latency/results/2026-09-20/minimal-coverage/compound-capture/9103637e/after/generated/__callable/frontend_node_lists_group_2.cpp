#include <scpp/lang/php.hpp>
#include "__types/FrontendNodeList.hpp"
#include "__types/FrontendNodeListOwner.hpp"
#include "__types/FrontendNodeListRef.hpp"
#include "__types/RowListPublishResult.hpp"
#include "__callable/__latency_fn_frontend_node_lists_list_ref.hpp"
#include "__callable/__latency_fn_frontend_node_lists_list_ref_for_generation.hpp"
#include "__callable/__latency_fn_row_segment_policy_initial_list_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_ensure_defaults.hpp"
#include "__callable/__latency_fn_frontend_node_lists_list_ref_for_generation.hpp"
#include "__callable/__latency_fn_frontend_node_lists_reserved_segment_bytes.hpp"
#include "__callable/__latency_fn_frontend_node_lists_segment_slack_bytes.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uint32_from_int.hpp"
#include "__callable/__latency_fn_row_segment_policy_list_ref_ready_flag.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_owner_from_current.hpp"
#include "__callable/__latency_fn_row_segment_policy_initial_generation_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_initial_list_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_reserved_segment_bytes.hpp"
#include "__callable/__latency_fn_frontend_node_lists_retained_generation_bytes.hpp"
#include "__callable/__latency_fn_frontend_node_lists_used_row_bytes.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uses_segmented.hpp"
#include "__callable/__latency_fn_frontend_node_lists_current_list_ref.hpp"
#include "__callable/__latency_fn_frontend_node_lists_list_ref_for_generation.hpp"
#include "__callable/__latency_fn_frontend_node_lists_list_ref_for_generation.hpp"
#include "__callable/__latency_fn_frontend_node_lists_retained_list_ref.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_ensure_defaults.hpp"
#include "__callable/__latency_fn_frontend_node_lists_publish_owner_list_for_generation.hpp"
#include "__callable/__latency_fn_frontend_node_lists_retained_generation_bytes.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uint32_from_int.hpp"
#include "__callable/__latency_fn_row_segment_policy_publish_status_ok_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_publish_owner_list.hpp"
#include "__callable/__latency_fn_frontend_node_lists_publish_owner_list_for_generation.hpp"
#include "__callable/__latency_fn_row_segment_policy_next_generation_id.hpp"
#include "__callable/__latency_fn_row_segment_policy_next_list_id.hpp"
#include "__callable/__latency_fn_frontend_node_lists_cleanup_retained_owner_list.hpp"
#include "__callable/__latency_fn_frontend_node_lists_uint32_from_int.hpp"
#include "__callable/__latency_fn_row_segment_policy_cleanup_status_ok_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
FrontendNodeListRef __latency_fn_frontend_node_lists_list_ref(shared_p<FrontendNodeList> list, int_t<std::uint32_t> ownerSourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::list_ref", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[23]);
	return __latency_fn_frontend_node_lists_list_ref_for_generation(list, cast<int_t<std::uint32_t>>(ownerSourceUnitId), __latency_fn_row_segment_policy_initial_list_id(), __latency_fn_structure_row_ids_none_id());
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
FrontendNodeListRef __latency_fn_frontend_node_lists_list_ref_for_generation(shared_p<FrontendNodeList> list, int_t<std::uint32_t> ownerSourceUnitId, int_t<std::uint32_t> listId, int_t<std::uint32_t> generationId) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::list_ref_for_generation", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[24]);
	__latency_fn_frontend_node_lists_ensure_defaults(list);
	FrontendNodeListRef ref = FrontendNodeListRef{};
	ref->list_id = listId;
	ref->owner_source_unit_id = ownerSourceUnitId;
	ref->generation_id = generationId;
	ref->row_count = list->row_count;
	ref->first_index = __latency_fn_structure_row_ids_none_id();
	ref->segment_capacity = list->segment_capacity;
	ref->segment_count = list->segment_count;
	ref->reserved_segment_bytes = __latency_fn_frontend_node_lists_uint32_from_int(__latency_fn_frontend_node_lists_reserved_segment_bytes(list));
	ref->segment_slack_bytes = __latency_fn_frontend_node_lists_uint32_from_int(__latency_fn_frontend_node_lists_segment_slack_bytes(list));
	ref->storage_kind_id = list->storage_kind_id;
	ref->flags = __latency_fn_row_segment_policy_list_ref_ready_flag();
	return ref;
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
shared_p<FrontendNodeListOwner> __latency_fn_frontend_node_lists_owner_from_current(int_t<std::uint32_t> ownerSourceUnitId, shared_p<FrontendNodeList> currentRows) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::owner_from_current", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[25]);
	shared_p<FrontendNodeListOwner> owner = create<FrontendNodeListOwner>();
	owner->owner_source_unit_id = ownerSourceUnitId;
	owner->current_list_id = __latency_fn_row_segment_policy_initial_list_id();
	owner->current_generation_id = __latency_fn_row_segment_policy_initial_generation_id();
	owner->current_rows = currentRows;
	owner->retained_old_generation_bytes = __latency_fn_structure_row_ids_none_id();
	owner->publish_count = __latency_fn_structure_row_ids_none_id();
	owner->cleanup_count = __latency_fn_structure_row_ids_none_id();
	return owner;
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
int_t<> __latency_fn_frontend_node_lists_retained_generation_bytes(shared_p<FrontendNodeList> list) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::retained_generation_bytes", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[26]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_frontend_node_lists_uses_segmented(list)))) {
		return __latency_fn_frontend_node_lists_reserved_segment_bytes(list);
	}
	return __latency_fn_frontend_node_lists_used_row_bytes(list);
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
FrontendNodeListRef __latency_fn_frontend_node_lists_current_list_ref(shared_p<FrontendNodeListOwner> owner) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::current_list_ref", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[27]);
	return __latency_fn_frontend_node_lists_list_ref_for_generation(owner->current_rows, owner->owner_source_unit_id, owner->current_list_id, owner->current_generation_id);
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
FrontendNodeListRef __latency_fn_frontend_node_lists_retained_list_ref(shared_p<FrontendNodeListOwner> owner) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::retained_list_ref", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[28]);
	int_t<std::uint32_t> previousListId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	if (static_cast<bool>((cast<int_t<>>(owner->current_list_id) > static_cast<int_t<> >(1)))) {
		previousListId = __latency_fn_frontend_node_lists_uint32_from_int((cast<int_t<>>(owner->current_list_id) - static_cast<int_t<> >(1)));
	}
	int_t<std::uint32_t> previousGenerationId = required_cast<int_t<std::uint32_t>>(__latency_fn_structure_row_ids_none_id());
	if (static_cast<bool>((cast<int_t<>>(owner->current_generation_id) > static_cast<int_t<> >(1)))) {
		previousGenerationId = __latency_fn_frontend_node_lists_uint32_from_int((cast<int_t<>>(owner->current_generation_id) - static_cast<int_t<> >(1)));
	}
	return __latency_fn_frontend_node_lists_list_ref_for_generation(owner->retained_rows, owner->owner_source_unit_id, cast<int_t<std::uint32_t>>(previousListId), cast<int_t<std::uint32_t>>(previousGenerationId));
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
RowListPublishResult __latency_fn_frontend_node_lists_publish_owner_list_for_generation(shared_p<FrontendNodeListOwner> owner, shared_p<FrontendNodeList> replacementRows, int_t<std::uint32_t> publishedListId, int_t<std::uint32_t> publishedGenerationId) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::publish_owner_list_for_generation", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[29]);
	__latency_fn_frontend_node_lists_ensure_defaults(owner->current_rows);
	__latency_fn_frontend_node_lists_ensure_defaults(replacementRows);
	RowListPublishResult result = RowListPublishResult{};
	result->owner_source_unit_id = owner->owner_source_unit_id;
	result->previous_list_id = owner->current_list_id;
	result->previous_generation_id = owner->current_generation_id;
	result->previous_row_count = owner->current_rows->row_count;
	result->previous_segment_count = owner->current_rows->segment_count;
	result->retained_old_generation_bytes = __latency_fn_frontend_node_lists_uint32_from_int(__latency_fn_frontend_node_lists_retained_generation_bytes(owner->current_rows));
	owner->retained_rows = owner->current_rows;
	owner->retained_old_generation_bytes = result->retained_old_generation_bytes;
	owner->current_rows = replacementRows;
	owner->current_list_id = publishedListId;
	owner->current_generation_id = publishedGenerationId;
	owner->publish_count = __latency_fn_frontend_node_lists_uint32_from_int((cast<int_t<>>(owner->publish_count) + static_cast<int_t<> >(1)));
	result->published_list_id = owner->current_list_id;
	result->published_generation_id = owner->current_generation_id;
	result->published_row_count = owner->current_rows->row_count;
	result->published_segment_count = owner->current_rows->segment_count;
	result->publish_count = owner->publish_count;
	result->cleanup_count = owner->cleanup_count;
	result->status_id = __latency_fn_row_segment_policy_publish_status_ok_id();
	return result;
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
RowListPublishResult __latency_fn_frontend_node_lists_publish_owner_list(shared_p<FrontendNodeListOwner> owner, shared_p<FrontendNodeList> replacementRows) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::publish_owner_list", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[30]);
	return __latency_fn_frontend_node_lists_publish_owner_list_for_generation(owner, replacementRows, __latency_fn_row_segment_policy_next_list_id(owner->current_list_id), __latency_fn_row_segment_policy_next_generation_id(owner->current_generation_id));
}

}

namespace scpp { extern const int __latency_lines_frontend_node_lists[]; }
namespace scpp {
RowListPublishResult __latency_fn_frontend_node_lists_cleanup_retained_owner_list(shared_p<FrontendNodeListOwner> owner) {
	SCPP_CALL_DEPTH_GUARD("frontend_node_lists::cleanup_retained_owner_list", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/frontend_node_lists.phs", __latency_lines_frontend_node_lists[31]);
	RowListPublishResult result = RowListPublishResult{};
	result->owner_source_unit_id = owner->owner_source_unit_id;
	result->published_list_id = owner->current_list_id;
	result->published_generation_id = owner->current_generation_id;
	result->published_row_count = owner->current_rows->row_count;
	result->published_segment_count = owner->current_rows->segment_count;
	result->cleanup_released_bytes = owner->retained_old_generation_bytes;
	owner->retained_rows = create<FrontendNodeList>();
	owner->retained_old_generation_bytes = __latency_fn_structure_row_ids_none_id();
	owner->cleanup_count = __latency_fn_frontend_node_lists_uint32_from_int((cast<int_t<>>(owner->cleanup_count) + static_cast<int_t<> >(1)));
	result->publish_count = owner->publish_count;
	result->cleanup_count = owner->cleanup_count;
	result->status_id = __latency_fn_row_segment_policy_cleanup_status_ok_id();
	return result;
}

}
