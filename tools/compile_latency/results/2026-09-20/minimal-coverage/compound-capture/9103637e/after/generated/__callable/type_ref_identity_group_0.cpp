#include <scpp/lang/php.hpp>
#include "__types/TypeRefRow.hpp"
#include "__types/type_ref_identity.hpp"
#include "__callable/__latency_fn_type_ref_identity_name.hpp"
#include "__callable/__latency_fn_type_refs_bool_id.hpp"
#include "__callable/__latency_fn_type_refs_double_id.hpp"
#include "__callable/__latency_fn_type_refs_float_id.hpp"
#include "__callable/__latency_fn_type_refs_generic_family_instance_source_name_by_id.hpp"
#include "__callable/__latency_fn_type_refs_int16_id.hpp"
#include "__callable/__latency_fn_type_refs_int32_id.hpp"
#include "__callable/__latency_fn_type_refs_int64_id.hpp"
#include "__callable/__latency_fn_type_refs_int8_id.hpp"
#include "__callable/__latency_fn_type_refs_int_id.hpp"
#include "__callable/__latency_fn_type_refs_provider_source_name_by_id.hpp"
#include "__callable/__latency_fn_type_refs_uint16_id.hpp"
#include "__callable/__latency_fn_type_refs_uint32_id.hpp"
#include "__callable/__latency_fn_type_refs_uint64_id.hpp"
#include "__callable/__latency_fn_type_refs_uint8_id.hpp"
#include "__callable/__latency_fn_type_refs_void_id.hpp"
#include "__callable/__latency_fn_type_ref_identity_type_kind_name.hpp"
#include "__callable/__latency_fn_type_refs_type_kind_family_instance_id.hpp"
#include "__callable/__latency_fn_type_refs_type_kind_primitive_id.hpp"
#include "__callable/__latency_fn_type_refs_type_kind_runtime_opaque_id.hpp"
#include "__callable/__latency_fn_type_refs_type_kind_void_id.hpp"
#include "__callable/__latency_fn_type_ref_identity_status_name.hpp"
#include "__callable/__latency_fn_type_refs_type_status_known_id.hpp"
#include "__callable/__latency_fn_type_ref_identity_equals.hpp"
#include "__callable/__latency_fn_type_ref_identity_debug_string.hpp"
#include "__callable/__latency_fn_type_ref_identity_name.hpp"
#include "__callable/__latency_fn_type_ref_identity_stable_hash.hpp"
namespace scpp { extern const int __latency_lines_type_ref_identity[]; }
namespace scpp {
bool_t type_ref_identity::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == type_ref_identity::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_type_ref_identity[]; }
namespace scpp {
string_t __latency_fn_type_ref_identity_name(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_ref_identity::name", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_ref_identity.phs", __latency_lines_type_ref_identity[0]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeRefId), cast<int_t<>>(__latency_fn_type_refs_int_id())))) {
		return string_t("int");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeRefId), cast<int_t<>>(__latency_fn_type_refs_int64_id())))) {
		return string_t("int64");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeRefId), cast<int_t<>>(__latency_fn_type_refs_bool_id())))) {
		return string_t("bool");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeRefId), cast<int_t<>>(__latency_fn_type_refs_int8_id())))) {
		return string_t("int8");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeRefId), cast<int_t<>>(__latency_fn_type_refs_int16_id())))) {
		return string_t("int16");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeRefId), cast<int_t<>>(__latency_fn_type_refs_int32_id())))) {
		return string_t("int32");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeRefId), cast<int_t<>>(__latency_fn_type_refs_uint8_id())))) {
		return string_t("uint8");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeRefId), cast<int_t<>>(__latency_fn_type_refs_uint16_id())))) {
		return string_t("uint16");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeRefId), cast<int_t<>>(__latency_fn_type_refs_uint32_id())))) {
		return string_t("uint32");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeRefId), cast<int_t<>>(__latency_fn_type_refs_uint64_id())))) {
		return string_t("uint64");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeRefId), cast<int_t<>>(__latency_fn_type_refs_float_id())))) {
		return string_t("float");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeRefId), cast<int_t<>>(__latency_fn_type_refs_double_id())))) {
		return string_t("double");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeRefId), cast<int_t<>>(__latency_fn_type_refs_void_id())))) {
		return string_t("void");
	}
	string_t genericFamilyName = required_cast<string_t>(__latency_fn_type_refs_generic_family_instance_source_name_by_id(typeRefId));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(genericFamilyName, string_t(""))))) {
		return genericFamilyName;
	}
	string_t providerName = required_cast<string_t>(__latency_fn_type_refs_provider_source_name_by_id(typeRefId));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(providerName, string_t(""))))) {
		return providerName;
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_type_ref_identity[]; }
namespace scpp {
string_t __latency_fn_type_ref_identity_type_kind_name(int_t<std::uint16_t> typeKindId) {
	SCPP_CALL_DEPTH_GUARD("type_ref_identity::type_kind_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_ref_identity.phs", __latency_lines_type_ref_identity[1]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeKindId), cast<int_t<>>(__latency_fn_type_refs_type_kind_primitive_id())))) {
		return string_t("primitive");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeKindId), cast<int_t<>>(__latency_fn_type_refs_type_kind_family_instance_id())))) {
		return string_t("family_instance");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeKindId), cast<int_t<>>(__latency_fn_type_refs_type_kind_runtime_opaque_id())))) {
		return string_t("runtime_opaque");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeKindId), cast<int_t<>>(__latency_fn_type_refs_type_kind_void_id())))) {
		return string_t("void");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_type_ref_identity[]; }
namespace scpp {
string_t __latency_fn_type_ref_identity_status_name(int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("type_ref_identity::status_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_ref_identity.phs", __latency_lines_type_ref_identity[2]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_type_refs_type_status_known_id())))) {
		return string_t("known");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_type_ref_identity[]; }
namespace scpp {
bool_t __latency_fn_type_ref_identity_equals(TypeRefRow left, TypeRefRow right) {
	SCPP_CALL_DEPTH_GUARD("type_ref_identity::equals", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_ref_identity.phs", __latency_lines_type_ref_identity[3]);
	return (((php::identical(cast<int_t<>>(left->type_ref_id), cast<int_t<>>(right->type_ref_id)) && php::identical(cast<int_t<>>(left->type_kind_id), cast<int_t<>>(right->type_kind_id))) && php::identical(cast<int_t<>>(left->family_id), cast<int_t<>>(right->family_id))) && php::identical(cast<int_t<>>(left->status_id), cast<int_t<>>(right->status_id)));
}

}

namespace scpp { extern const int __latency_lines_type_ref_identity[]; }
namespace scpp {
string_t __latency_fn_type_ref_identity_debug_string(TypeRefRow row) {
	SCPP_CALL_DEPTH_GUARD("type_ref_identity::debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_ref_identity.phs", __latency_lines_type_ref_identity[4]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->type_ref_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	return (string_t("type_ref:") + cast<string_t>(__latency_fn_type_ref_identity_name(row->type_ref_id)));
}

}

namespace scpp { extern const int __latency_lines_type_ref_identity[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_type_ref_identity_stable_hash(TypeRefRow row) {
	SCPP_CALL_DEPTH_GUARD("type_ref_identity::stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_ref_identity.phs", __latency_lines_type_ref_identity[5]);
	string_t identity = required_cast<string_t>((string_t("type_ref:v2:") + cast<string_t>(cast<int_t<>>(row->type_ref_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->type_kind_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->family_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id))));
	return php::stable_hash_string_u64(identity);
}

}
