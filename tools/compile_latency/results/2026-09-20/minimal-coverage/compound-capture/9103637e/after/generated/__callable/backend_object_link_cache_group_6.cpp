#include <scpp/lang/php.hpp>
#include "__types/BackendLinkCacheDecisionRow.hpp"
#include "__types/BackendObjectCacheDecisionRow.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_link_debug_string.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_object_stable_hash.hpp"
#include "__callable/__latency_fn_backend_object_link_cache_link_stable_hash.hpp"
namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
string_t __latency_fn_backend_object_link_cache_link_debug_string(BackendLinkCacheDecisionRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::link_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[56]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->cache_link_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	return (string_t("backend_link_cache:partitions:") + cast<string_t>(cast<int_t<>>(row->object_partition_count)) + string_t(":relink_deferred"));
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_backend_object_link_cache_object_stable_hash(BackendObjectCacheDecisionRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::object_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[57]);
	string_t identity = required_cast<string_t>((string_t("backend_object_cache_row:v2:") + cast<string_t>(cast<int_t<>>(row->cache_object_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->partition_execution_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->owner_symbol_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_unit_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->cache_status_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->object_action_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_backend_object_link_cache[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_backend_object_link_cache_link_stable_hash(BackendLinkCacheDecisionRow row) {
	SCPP_CALL_DEPTH_GUARD("backend_object_link_cache::link_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_object_link_cache.phs", __latency_lines_backend_object_link_cache[58]);
	string_t identity = required_cast<string_t>((string_t("backend_link_cache_row:v2:") + cast<string_t>(cast<int_t<>>(row->cache_link_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->link_execution_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->program_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->object_partition_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->ready_partition_count)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->relink_reason_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->link_action_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id))));
	return php::stable_hash_string_u64(identity);
}

}
