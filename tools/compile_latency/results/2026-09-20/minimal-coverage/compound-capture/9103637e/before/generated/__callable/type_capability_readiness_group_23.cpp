#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerRow.hpp"
#include "__types/CapabilityProviderRow.hpp"
#include "__types/CapabilityReadinessRow.hpp"
#include "__callable/__latency_fn_type_capability_readiness_provider_stable_hash.hpp"
#include "__callable/__latency_fn_type_capability_readiness_consumer_stable_hash.hpp"
#include "__callable/__latency_fn_type_capability_readiness_readiness_stable_hash.hpp"
namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_type_capability_readiness_provider_stable_hash(CapabilityProviderRow row) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::provider_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[197]);
	string_t identity = required_cast<string_t>((string_t("capability_provider:v2:") + cast<string_t>(cast<int_t<>>(row->capability_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->type_ref_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_type_capability_readiness_consumer_stable_hash(CapabilityConsumerRow row) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::consumer_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[198]);
	string_t identity = required_cast<string_t>((string_t("capability_consumer:v2:") + cast<string_t>(cast<int_t<>>(row->capability_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->provider_type_ref_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->feature_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_type_capability_readiness[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_type_capability_readiness_readiness_stable_hash(CapabilityReadinessRow row) {
	SCPP_CALL_DEPTH_GUARD("type_capability_readiness::readiness_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/capabilities/type_capability_readiness.phs", __latency_lines_type_capability_readiness[199]);
	string_t identity = required_cast<string_t>((string_t("capability_readiness:v2:") + cast<string_t>(cast<int_t<>>(row->capability_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->provider_type_ref_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->consumer_feature_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->blocked_reason_id))));
	return php::stable_hash_string_u64(identity);
}

}
