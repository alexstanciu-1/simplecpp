#include <scpp/lang/php.hpp>
#include "__types/RuntimeAbiBridgeArtifact.hpp"
#include "__callable/__latency_fn_runtime_abi_bridge_stable_hash.hpp"
namespace scpp { extern const int __latency_lines_runtime_abi_bridge[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_runtime_abi_bridge_stable_hash(shared_p<RuntimeAbiBridgeArtifact> artifact) {
	SCPP_CALL_DEPTH_GUARD("runtime_abi_bridge::stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/runtime_abi_bridge.phs", __latency_lines_runtime_abi_bridge[15]);
	string_t identity = required_cast<string_t>(string_t("runtime_abi_bridge:v1:"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(artifact->row_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(artifact->declaration_ready_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(artifact->call_lowering_ready_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(artifact->call_lowering_blocked_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(artifact->source_consumption_ready_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(artifact->source_consumption_blocked_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(artifact->opaque_call_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(artifact->owned_return_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(artifact->consumes_owned_argument_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(artifact->text_coercion_family_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(artifact->text_coercion_family_member_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(cast<int_t<>>(artifact->text_coercion_family_deferred_member_count)) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(artifact->policy_state) + string_t(":"));
	identity = (cast<string_t>(identity) + cast<string_t>(artifact->authority_identity_hash));
	return php::stable_hash_string_u64(identity);
}

}
