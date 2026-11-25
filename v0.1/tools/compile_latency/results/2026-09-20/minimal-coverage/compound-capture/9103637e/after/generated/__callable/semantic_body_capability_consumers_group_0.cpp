#include <scpp/lang/php.hpp>
#include "__types/CapabilityConsumerPlan.hpp"
#include "__types/ScalarBodyBackendCollection.hpp"
#include "__types/semantic_body_capability_consumers.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_binary_operator.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_local_assignment.hpp"
#include "__callable/__latency_fn_capability_consumer_plans_append_local_storage.hpp"
#include "__callable/__latency_fn_semantic_body_capability_consumers_append_scalar_body_common.hpp"
#include "__callable/__latency_fn_type_capability_readiness_feature_binary_operator_plus_id.hpp"
namespace scpp { extern const int __latency_lines_semantic_body_capability_consumers[]; }
namespace scpp {
bool_t semantic_body_capability_consumers::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == semantic_body_capability_consumers::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_semantic_body_capability_consumers[]; }
namespace scpp {
void __latency_fn_semantic_body_capability_consumers_append_scalar_body_common(shared_p<CapabilityConsumerPlan>& plan, shared_p<ScalarBodyBackendCollection> body, const vector_t<int_t<std::uint32_t>>& assignmentSourceRowIds, const vector_t<int_t<std::uint32_t>>& assignmentTypeRefIds, int_t<std::uint32_t> fallbackTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("semantic_body_capability_consumers::append_scalar_body_common", "/tmp/scpp-edit-latency-20260919/app/compile/semantic/semantic_body_capability_consumers.phs", __latency_lines_semantic_body_capability_consumers[3]);
	int_t<> localIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>(((localIndex < php::count(body->local_source_row_ids)) && (localIndex < php::count(body->local_type_ref_ids))))) {
		__latency_fn_capability_consumer_plans_append_local_storage(plan, body->local_source_row_ids[localIndex], body->local_type_ref_ids[localIndex]);
		localIndex = (localIndex + static_cast<int_t<> >(1));
	}
	int_t<> assignmentIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>(((assignmentIndex < php::count(assignmentSourceRowIds)) && (assignmentIndex < php::count(assignmentTypeRefIds))))) {
		__latency_fn_capability_consumer_plans_append_local_assignment(plan, assignmentSourceRowIds.at(assignmentIndex), assignmentTypeRefIds.at(assignmentIndex));
		assignmentIndex = (assignmentIndex + static_cast<int_t<> >(1));
	}
	int_t<> binaryIndex = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((binaryIndex < php::count(body->binary_source_row_ids)))) {
		int_t<std::uint16_t> binaryFeatureId = required_cast<int_t<std::uint16_t>>(__latency_fn_type_capability_readiness_feature_binary_operator_plus_id());
		if (static_cast<bool>((binaryIndex < php::count(body->binary_feature_ids)))) {
			binaryFeatureId = body->binary_feature_ids[binaryIndex];
		}
		int_t<std::uint32_t> binaryProviderTypeRefId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(fallbackTypeRefId));
		if (static_cast<bool>((binaryIndex < php::count(body->binary_provider_type_ref_ids)))) {
			binaryProviderTypeRefId = body->binary_provider_type_ref_ids[binaryIndex];
		}
		__latency_fn_capability_consumer_plans_append_binary_operator(plan, body->binary_source_row_ids[binaryIndex], binaryProviderTypeRefId, binaryFeatureId);
		binaryIndex = (binaryIndex + static_cast<int_t<> >(1));
	}
}

}
