#include <scpp/lang/php.hpp>
#include "__types/SemanticRuntimeAbiBridgeDescriptorRow.hpp"
#include "__types/runtime_helper_effects.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_has_owned_return.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_ownership_owned_return_id.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_has_borrowed_arguments.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_ownership_borrowed_arguments_id.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_mutates_lhs.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_ownership_mutates_lhs_id.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_consumes_owned_argument.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_ownership_consumes_owned_argument_id.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_requires_explicit_cleanup.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_lifetime_explicit_cleanup_required_id.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_consumes_for_cleanup.hpp"
#include "__callable/__latency_fn_semantic_runtime_abi_bridge_lifetime_cleanup_consumes_owned_argument_id.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_consumes_owned_argument.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_effect_key.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_has_borrowed_arguments.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_has_owned_return.hpp"
#include "__callable/__latency_fn_runtime_helper_effects_mutates_lhs.hpp"
namespace scpp { extern const int __latency_lines_runtime_helper_effects[]; }
namespace scpp {
bool_t runtime_helper_effects::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == runtime_helper_effects::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_runtime_helper_effects[]; }
namespace scpp {
bool_t __latency_fn_runtime_helper_effects_has_owned_return(shared_p<SemanticRuntimeAbiBridgeDescriptorRow> row) {
	SCPP_CALL_DEPTH_GUARD("runtime_helper_effects::has_owned_return", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/runtime_helper_effects.phs", __latency_lines_runtime_helper_effects[0]);
	return bool_t(php::identical(cast<int_t<>>(row->ownership_policy_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_ownership_owned_return_id())));
}

}

namespace scpp { extern const int __latency_lines_runtime_helper_effects[]; }
namespace scpp {
bool_t __latency_fn_runtime_helper_effects_has_borrowed_arguments(shared_p<SemanticRuntimeAbiBridgeDescriptorRow> row) {
	SCPP_CALL_DEPTH_GUARD("runtime_helper_effects::has_borrowed_arguments", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/runtime_helper_effects.phs", __latency_lines_runtime_helper_effects[1]);
	return bool_t(php::identical(cast<int_t<>>(row->ownership_policy_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_ownership_borrowed_arguments_id())));
}

}

namespace scpp { extern const int __latency_lines_runtime_helper_effects[]; }
namespace scpp {
bool_t __latency_fn_runtime_helper_effects_mutates_lhs(shared_p<SemanticRuntimeAbiBridgeDescriptorRow> row) {
	SCPP_CALL_DEPTH_GUARD("runtime_helper_effects::mutates_lhs", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/runtime_helper_effects.phs", __latency_lines_runtime_helper_effects[2]);
	return bool_t(php::identical(cast<int_t<>>(row->ownership_policy_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_ownership_mutates_lhs_id())));
}

}

namespace scpp { extern const int __latency_lines_runtime_helper_effects[]; }
namespace scpp {
bool_t __latency_fn_runtime_helper_effects_consumes_owned_argument(shared_p<SemanticRuntimeAbiBridgeDescriptorRow> row) {
	SCPP_CALL_DEPTH_GUARD("runtime_helper_effects::consumes_owned_argument", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/runtime_helper_effects.phs", __latency_lines_runtime_helper_effects[3]);
	return bool_t(php::identical(cast<int_t<>>(row->ownership_policy_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_ownership_consumes_owned_argument_id())));
}

}

namespace scpp { extern const int __latency_lines_runtime_helper_effects[]; }
namespace scpp {
bool_t __latency_fn_runtime_helper_effects_requires_explicit_cleanup(shared_p<SemanticRuntimeAbiBridgeDescriptorRow> row) {
	SCPP_CALL_DEPTH_GUARD("runtime_helper_effects::requires_explicit_cleanup", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/runtime_helper_effects.phs", __latency_lines_runtime_helper_effects[4]);
	return bool_t(php::identical(cast<int_t<>>(row->lifetime_policy_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_lifetime_explicit_cleanup_required_id())));
}

}

namespace scpp { extern const int __latency_lines_runtime_helper_effects[]; }
namespace scpp {
bool_t __latency_fn_runtime_helper_effects_consumes_for_cleanup(shared_p<SemanticRuntimeAbiBridgeDescriptorRow> row) {
	SCPP_CALL_DEPTH_GUARD("runtime_helper_effects::consumes_for_cleanup", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/runtime_helper_effects.phs", __latency_lines_runtime_helper_effects[5]);
	return bool_t(php::identical(cast<int_t<>>(row->lifetime_policy_id), cast<int_t<>>(__latency_fn_semantic_runtime_abi_bridge_lifetime_cleanup_consumes_owned_argument_id())));
}

}

namespace scpp { extern const int __latency_lines_runtime_helper_effects[]; }
namespace scpp {
string_t __latency_fn_runtime_helper_effects_effect_key(shared_p<SemanticRuntimeAbiBridgeDescriptorRow> row) {
	SCPP_CALL_DEPTH_GUARD("runtime_helper_effects::effect_key", "/tmp/scpp-edit-latency-20260919/app/compile/backend/abi_bridge/runtime_helper_effects.phs", __latency_lines_runtime_helper_effects[6]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_runtime_helper_effects_has_owned_return(row)))) {
		return string_t("owned_return_cleanup_required");
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_runtime_helper_effects_consumes_owned_argument(row)))) {
		return string_t("consumes_owned_argument_cleanup");
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_runtime_helper_effects_mutates_lhs(row)))) {
		return string_t("mutates_lhs_in_place");
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_runtime_helper_effects_has_borrowed_arguments(row)))) {
		return string_t("borrowed_arguments");
	}
	return string_t("unknown");
}

}
