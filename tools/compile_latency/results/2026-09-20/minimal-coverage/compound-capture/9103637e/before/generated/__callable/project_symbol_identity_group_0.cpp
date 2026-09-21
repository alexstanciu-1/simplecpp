#include <scpp/lang/php.hpp>
#include "__types/ProjectSymbolIndex.hpp"
#include "__types/ProjectSymbolIndexRow.hpp"
#include "__types/project_symbol_identity.hpp"
#include "__callable/__latency_fn_project_symbol_identity_equals.hpp"
#include "__callable/__latency_fn_project_symbol_identity_equals_from_index.hpp"
#include "__callable/__latency_fn_project_symbol_index_name.hpp"
#include "__callable/__latency_fn_project_symbol_identity_function_debug_string.hpp"
#include "__callable/__latency_fn_project_symbol_identity_debug_string.hpp"
#include "__callable/__latency_fn_project_symbol_identity_debug_string.hpp"
#include "__callable/__latency_fn_project_symbol_identity_debug_string_from_index.hpp"
#include "__callable/__latency_fn_project_symbol_identity_function_debug_string.hpp"
#include "__callable/__latency_fn_project_symbol_index_qualified_name.hpp"
#include "__callable/__latency_fn_project_symbol_index_symbol_key.hpp"
#include "__callable/__latency_fn_project_symbol_identity_stable_hash.hpp"
#include "__callable/__latency_fn_project_symbol_identity_stable_hash_from_index.hpp"
#include "__callable/__latency_fn_project_symbol_index_signature_shape.hpp"
#include "__callable/__latency_fn_project_symbol_index_source_unit_key.hpp"
#include "__callable/__latency_fn_project_symbol_index_symbol_key.hpp"
#include "__callable/__latency_fn_project_symbol_identity_export_shape_key_from_signature_shape.hpp"
#include "__callable/__latency_fn_project_symbol_identity_body_shape_key_from_body_shape.hpp"
namespace scpp { extern const int __latency_lines_project_symbol_identity[]; }
namespace scpp {
bool_t project_symbol_identity::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == project_symbol_identity::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_project_symbol_identity[]; }
namespace scpp {
bool_t __latency_fn_project_symbol_identity_equals(ProjectSymbolIndexRow left, ProjectSymbolIndexRow right) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_identity::equals", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_identity.phs", __latency_lines_project_symbol_identity[0]);
	if (static_cast<bool>(((cast<int_t<>>(left->symbol_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(right->symbol_id) > static_cast<int_t<> >(0))))) {
		return bool_t(php::identical(cast<int_t<>>(left->symbol_id), cast<int_t<>>(right->symbol_id)));
	}
	if (static_cast<bool>((php::identical(cast<int_t<>>(left->source_unit_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(right->source_unit_id), static_cast<int_t<> >(0))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	return ((((php::identical(cast<int_t<>>(left->source_unit_id), cast<int_t<>>(right->source_unit_id)) && php::identical(cast<int_t<>>(left->source_row_id), cast<int_t<>>(right->source_row_id))) && php::identical(cast<int_t<>>(left->symbol_kind_id), cast<int_t<>>(right->symbol_kind_id))) && php::identical(cast<int_t<>>(left->scope_id), cast<int_t<>>(right->scope_id))) && php::identical(cast<int_t<>>(left->name_id), cast<int_t<>>(right->name_id)));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_identity[]; }
namespace scpp {
bool_t __latency_fn_project_symbol_identity_equals_from_index(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow left, ProjectSymbolIndexRow right) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_identity::equals_from_index", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_identity.phs", __latency_lines_project_symbol_identity[1]);
	if (static_cast<bool>(((cast<int_t<>>(left->symbol_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(right->symbol_id) > static_cast<int_t<> >(0))))) {
		return bool_t(php::identical(cast<int_t<>>(left->symbol_id), cast<int_t<>>(right->symbol_id)));
	}
	if (static_cast<bool>((php::identical(cast<int_t<>>(left->source_unit_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(right->source_unit_id), static_cast<int_t<> >(0))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	return ((((php::identical(cast<int_t<>>(left->source_unit_id), cast<int_t<>>(right->source_unit_id)) && php::identical(cast<int_t<>>(left->source_row_id), cast<int_t<>>(right->source_row_id))) && php::identical(cast<int_t<>>(left->symbol_kind_id), cast<int_t<>>(right->symbol_kind_id))) && php::identical(cast<int_t<>>(left->scope_id), cast<int_t<>>(right->scope_id))) && php::identical(__latency_fn_project_symbol_index_name(index, left), __latency_fn_project_symbol_index_name(index, right)));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_identity[]; }
namespace scpp {
string_t __latency_fn_project_symbol_identity_function_debug_string(const string_t& functionName) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_identity::function_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_identity.phs", __latency_lines_project_symbol_identity[2]);
	if (static_cast<bool>(php::identical(functionName, string_t("")))) {
		return string_t("function:unknown");
	}
	return (string_t("function:") + cast<string_t>(functionName));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_identity[]; }
namespace scpp {
string_t __latency_fn_project_symbol_identity_debug_string(ProjectSymbolIndexRow row) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_identity::debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_identity.phs", __latency_lines_project_symbol_identity[3]);
	if (static_cast<bool>((cast<int_t<>>(row->qualified_name_id) > static_cast<int_t<> >(0)))) {
		return (string_t("qualified_name_id:") + cast<string_t>(row->qualified_name_id));
	}
	if (static_cast<bool>((cast<int_t<>>(row->symbol_id) > static_cast<int_t<> >(0)))) {
		return (string_t("symbol_id:") + cast<string_t>(row->symbol_id));
	}
	return string_t("");
}

}

namespace scpp { extern const int __latency_lines_project_symbol_identity[]; }
namespace scpp {
string_t __latency_fn_project_symbol_identity_debug_string_from_index(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow row) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_identity::debug_string_from_index", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_identity.phs", __latency_lines_project_symbol_identity[4]);
	string_t symbolKey = required_cast<string_t>(__latency_fn_project_symbol_index_symbol_key(index, row));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(symbolKey, string_t(""))))) {
		return symbolKey;
	}
	string_t name = required_cast<string_t>(__latency_fn_project_symbol_index_qualified_name(index, row));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(name, string_t(""))))) {
		return __latency_fn_project_symbol_identity_function_debug_string(name);
	}
	return __latency_fn_project_symbol_identity_debug_string(row);
}

}

namespace scpp { extern const int __latency_lines_project_symbol_identity[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_project_symbol_identity_stable_hash(ProjectSymbolIndexRow row) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_identity::stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_identity.phs", __latency_lines_project_symbol_identity[5]);
	string_t identity = required_cast<string_t>((string_t("project_symbol:v2:") + cast<string_t>(cast<int_t<>>(row->source_unit_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_row_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->symbol_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->scope_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->name_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->return_type_ref_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->parameter_count))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_project_symbol_identity[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_project_symbol_identity_stable_hash_from_index(shared_p<ProjectSymbolIndex> index, ProjectSymbolIndexRow row) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_identity::stable_hash_from_index", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_identity.phs", __latency_lines_project_symbol_identity[6]);
	string_t identity = required_cast<string_t>((string_t("project_symbol:v2:") + cast<string_t>(cast<int_t<>>(row->source_unit_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->source_row_id)) + string_t(":") + cast<string_t>(__latency_fn_project_symbol_index_symbol_key(index, row)) + string_t(":") + cast<string_t>(__latency_fn_project_symbol_index_signature_shape(index, row)) + string_t(":") + cast<string_t>(__latency_fn_project_symbol_index_source_unit_key(index, row))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_project_symbol_identity[]; }
namespace scpp {
string_t __latency_fn_project_symbol_identity_export_shape_key_from_signature_shape(const string_t& signatureShape) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_identity::export_shape_key_from_signature_shape", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_identity.phs", __latency_lines_project_symbol_identity[7]);
	return (string_t("export_shape:v2:") + cast<string_t>(signatureShape));
}

}

namespace scpp { extern const int __latency_lines_project_symbol_identity[]; }
namespace scpp {
string_t __latency_fn_project_symbol_identity_body_shape_key_from_body_shape(const string_t& bodyShape) {
	SCPP_CALL_DEPTH_GUARD("project_symbol_identity::body_shape_key_from_body_shape", "/tmp/scpp-edit-latency-20260919/app/compile/model/project_symbol_identity.phs", __latency_lines_project_symbol_identity[8]);
	return (string_t("body_shape:v2:") + cast<string_t>(bodyShape));
}

}
