#include <scpp/lang/php.hpp>
#include "__types/ProjectReferenceResolution.hpp"
#include "__types/ProjectReferenceResolutionRow.hpp"
#include "__types/reference_identity.hpp"
#include "__callable/__latency_fn_reference_identity_debug_string_from_parts.hpp"
#include "__callable/__latency_fn_project_symbol_identity_function_debug_string.hpp"
#include "__callable/__latency_fn_reference_identity_target_symbol_debug_string.hpp"
#include "__callable/__latency_fn_reference_identity_debug_string.hpp"
#include "__callable/__latency_fn_project_reference_resolution_reference_key.hpp"
#include "__callable/__latency_fn_reference_identity_debug_string.hpp"
#include "__callable/__latency_fn_reference_identity_debug_string_from_artifact.hpp"
#include "__callable/__latency_fn_reference_identity_equals.hpp"
#include "__callable/__latency_fn_project_reference_resolution_call_expression_node_key.hpp"
#include "__callable/__latency_fn_project_reference_resolution_callee_name.hpp"
#include "__callable/__latency_fn_project_reference_resolution_from_symbol_key.hpp"
#include "__callable/__latency_fn_project_reference_resolution_resolved_symbol_key.hpp"
#include "__callable/__latency_fn_reference_identity_stable_hash_from_artifact.hpp"
namespace scpp { extern const int __latency_lines_reference_identity[]; }
namespace scpp {
bool_t reference_identity::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == reference_identity::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_reference_identity[]; }
namespace scpp {
string_t __latency_fn_reference_identity_debug_string_from_parts(const string_t& fromSymbolKey, const string_t& calleeName, int_t<std::uint32_t> referenceId) {
	SCPP_CALL_DEPTH_GUARD("reference_identity::debug_string_from_parts", "/tmp/scpp-edit-latency-20260919/app/compile/model/reference_identity.phs", __latency_lines_reference_identity[0]);
	return (string_t("reference:") + cast<string_t>(fromSymbolKey) + string_t("->") + cast<string_t>(calleeName) + string_t("#") + cast<string_t>(cast<int_t<>>(referenceId)));
}

}

namespace scpp { extern const int __latency_lines_reference_identity[]; }
namespace scpp {
string_t __latency_fn_reference_identity_target_symbol_debug_string(const string_t& calleeName) {
	SCPP_CALL_DEPTH_GUARD("reference_identity::target_symbol_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/model/reference_identity.phs", __latency_lines_reference_identity[1]);
	return __latency_fn_project_symbol_identity_function_debug_string(calleeName);
}

}

namespace scpp { extern const int __latency_lines_reference_identity[]; }
namespace scpp {
string_t __latency_fn_reference_identity_debug_string(ProjectReferenceResolutionRow row) {
	SCPP_CALL_DEPTH_GUARD("reference_identity::debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/model/reference_identity.phs", __latency_lines_reference_identity[2]);
	if (static_cast<bool>((cast<int_t<>>(row->reference_id) > static_cast<int_t<> >(0)))) {
		return (string_t("reference_id:") + cast<string_t>(row->reference_id));
	}
	return string_t("");
}

}

namespace scpp { extern const int __latency_lines_reference_identity[]; }
namespace scpp {
string_t __latency_fn_reference_identity_debug_string_from_artifact(shared_p<ProjectReferenceResolution> artifact, ProjectReferenceResolutionRow row) {
	SCPP_CALL_DEPTH_GUARD("reference_identity::debug_string_from_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/model/reference_identity.phs", __latency_lines_reference_identity[3]);
	string_t referenceKey = required_cast<string_t>(__latency_fn_project_reference_resolution_reference_key(artifact, row));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(referenceKey, string_t(""))))) {
		return referenceKey;
	}
	return __latency_fn_reference_identity_debug_string(row);
}

}

namespace scpp { extern const int __latency_lines_reference_identity[]; }
namespace scpp {
bool_t __latency_fn_reference_identity_equals(ProjectReferenceResolutionRow left, ProjectReferenceResolutionRow right) {
	SCPP_CALL_DEPTH_GUARD("reference_identity::equals", "/tmp/scpp-edit-latency-20260919/app/compile/model/reference_identity.phs", __latency_lines_reference_identity[4]);
	if (static_cast<bool>(((cast<int_t<>>(left->reference_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(right->reference_id) > static_cast<int_t<> >(0))))) {
		return bool_t(php::identical(cast<int_t<>>(left->reference_id), cast<int_t<>>(right->reference_id)));
	}
	if (static_cast<bool>((php::identical(cast<int_t<>>(left->from_symbol_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(right->from_symbol_id), static_cast<int_t<> >(0))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	return ((php::identical(cast<int_t<>>(left->from_symbol_id), cast<int_t<>>(right->from_symbol_id)) && php::identical(cast<int_t<>>(left->call_expression_node_id), cast<int_t<>>(right->call_expression_node_id))) && php::identical(cast<int_t<>>(left->callee_name_id), cast<int_t<>>(right->callee_name_id)));
}

}

namespace scpp { extern const int __latency_lines_reference_identity[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_reference_identity_stable_hash_from_artifact(shared_p<ProjectReferenceResolution> artifact, ProjectReferenceResolutionRow row) {
	SCPP_CALL_DEPTH_GUARD("reference_identity::stable_hash_from_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/model/reference_identity.phs", __latency_lines_reference_identity[5]);
	string_t identity = required_cast<string_t>((string_t("project_reference:v2:") + cast<string_t>(__latency_fn_project_reference_resolution_from_symbol_key(artifact, row)) + string_t(":") + cast<string_t>(__latency_fn_project_reference_resolution_callee_name(artifact, row)) + string_t(":") + cast<string_t>(__latency_fn_project_reference_resolution_call_expression_node_key(artifact, row)) + string_t(":") + cast<string_t>(__latency_fn_project_reference_resolution_resolved_symbol_key(artifact, row)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id))));
	return php::stable_hash_string_u64(identity);
}

}
