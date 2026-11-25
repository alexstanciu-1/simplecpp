#include <scpp/lang/php.hpp>
#include "__types/FrontendDeclarationPayloadRow.hpp"
#include "__types/frontend_model_builder.hpp"
#include "__callable/__latency_fn_frontend_model_builder_declaration_kind_function_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_builder_declaration_kind_namespace_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_builder_declaration_kind_use_function_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_builder_synthetic_script_entry_name.hpp"
#include "__callable/__latency_fn_frontend_model_builder_declaration_flag_synthetic_script_entry_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_builder_declaration_flag_synthetic_script_entry_id.hpp"
#include "__callable/__latency_fn_frontend_model_builder_declaration_is_synthetic_script_entry.hpp"
#include "__callable/__latency_fn_frontend_model_builder_name_role_declaration_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_builder_name_role_type_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_builder_name_role_callee_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_builder_name_role_variable_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_builder_name_role_constant_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_builder_name_role_namespace_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_builder_statement_flag_const_binding_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_builder_statement_flag_append_target_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_builder_expression_flag_by_reference_parameter_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_model_builder_literal_kind_integer_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
bool_t frontend_model_builder::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == frontend_model_builder::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_model_builder_declaration_kind_function_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::declaration_kind_function_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_model_builder_declaration_kind_namespace_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::declaration_kind_namespace_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_model_builder_declaration_kind_use_function_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::declaration_kind_use_function_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
string_t __latency_fn_frontend_model_builder_synthetic_script_entry_name() {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::synthetic_script_entry_name", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[3]);
	return string_t("run");
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_model_builder_declaration_flag_synthetic_script_entry_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::declaration_flag_synthetic_script_entry_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
bool_t __latency_fn_frontend_model_builder_declaration_is_synthetic_script_entry(FrontendDeclarationPayloadRow declaration) {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::declaration_is_synthetic_script_entry", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[5]);
	return bool_t(php::identical(cast<int_t<>>(declaration->flags), cast<int_t<>>(__latency_fn_frontend_model_builder_declaration_flag_synthetic_script_entry_id())));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_model_builder_name_role_declaration_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::name_role_declaration_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[6]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_model_builder_name_role_type_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::name_role_type_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_model_builder_name_role_callee_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::name_role_callee_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_model_builder_name_role_variable_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::name_role_variable_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[9]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_model_builder_name_role_constant_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::name_role_constant_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[10]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_model_builder_name_role_namespace_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::name_role_namespace_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[11]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(6));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_model_builder_statement_flag_const_binding_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::statement_flag_const_binding_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[12]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_model_builder_statement_flag_append_target_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::statement_flag_append_target_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[13]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_model_builder_expression_flag_by_reference_parameter_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::expression_flag_by_reference_parameter_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[14]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_frontend_model_builder[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_model_builder_literal_kind_integer_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_model_builder::literal_kind_integer_id", "/tmp/scpp-edit-latency-20260919/app/compile/frontend_adapter/phs/frontend_model_builder.phs", __latency_lines_frontend_model_builder[15]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}
