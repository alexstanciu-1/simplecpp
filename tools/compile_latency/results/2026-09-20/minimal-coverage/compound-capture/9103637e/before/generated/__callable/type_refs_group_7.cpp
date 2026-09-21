#include <scpp/lang/php.hpp>
#include "__types/TypeArgRow.hpp"
#include "__types/TypeRefRow.hpp"
#include "__types/TypeRefTable.hpp"
#include "__callable/__latency_fn_type_refs_has_type.hpp"
#include "__callable/__latency_fn_type_refs_row_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_refs_add_type.hpp"
#include "__callable/__latency_fn_type_refs_has_type.hpp"
#include "__callable/__latency_fn_type_refs_row_from_id.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
#include "__callable/__latency_fn_type_refs_arg_kind_type_id.hpp"
#include "__callable/__latency_fn_type_refs_has_type_arg.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_refs_add_type_arg.hpp"
#include "__callable/__latency_fn_type_refs_arg_kind_type_id.hpp"
#include "__callable/__latency_fn_type_refs_arg_status_known_id.hpp"
#include "__callable/__latency_fn_type_refs_has_type_arg.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_type_refs_add_family_instance_with_type_args.hpp"
#include "__callable/__latency_fn_type_refs_add_type.hpp"
#include "__callable/__latency_fn_type_refs_add_type_arg.hpp"
#include "__callable/__latency_fn_type_refs_family_instance_matches_type_args.hpp"
#include "__callable/__latency_fn_type_refs_family_instance_row.hpp"
#include "__callable/__latency_fn_type_refs_family_instance_type_ref_id_for_type_args.hpp"
#include "__callable/__latency_fn_type_refs_family_instance_type_ref_id_from_table.hpp"
#include "__callable/__latency_fn_type_refs_has_type.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
#include "__callable/__latency_fn_type_refs_add_family_instance.hpp"
#include "__callable/__latency_fn_type_refs_add_family_instance_with_type_args.hpp"
#include "__callable/__latency_fn_type_refs_add_family_instance.hpp"
#include "__callable/__latency_fn_type_refs_add_result_int_proof.hpp"
#include "__callable/__latency_fn_type_refs_family_result_id.hpp"
#include "__callable/__latency_fn_type_refs_int_id.hpp"
#include "__callable/__latency_fn_type_refs_add_family_instance.hpp"
#include "__callable/__latency_fn_type_refs_add_nullable_int_proof.hpp"
#include "__callable/__latency_fn_type_refs_family_nullable_id.hpp"
#include "__callable/__latency_fn_type_refs_int_id.hpp"
namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
bool_t __latency_fn_type_refs_has_type(TypeRefTable table, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_refs::has_type", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[84]);
	auto __latency_local_0 = table->types;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->type_ref_id), cast<int_t<>>(typeRefId)))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
TypeRefRow __latency_fn_type_refs_row_by_id(TypeRefTable table, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_refs::row_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[85]);
	auto __latency_local_0 = table->types;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->type_ref_id), cast<int_t<>>(typeRefId)))) {
			return row;
		}
	}
	TypeRefRow empty = TypeRefRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
void __latency_fn_type_refs_add_type(TypeRefTable& table, int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_refs::add_type", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[86]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeRefId), cast<int_t<>>(__latency_fn_type_refs_unknown_id())))) {
		return;
	}
	if (static_cast<bool>(php::condition_truthy(__latency_fn_type_refs_has_type(table, cast<int_t<std::uint32_t>>(typeRefId))))) {
		return;
	}
	{
	auto __latency_local_0 = __latency_fn_type_refs_row_from_id(cast<int_t<std::uint32_t>>(typeRefId));
	(void) table->types.append(__latency_local_0);
	}
	table->type_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(table->types));
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
bool_t __latency_fn_type_refs_has_type_arg(TypeRefTable table, int_t<std::uint32_t> parentTypeRefId, int_t<std::uint16_t> argIndex) {
	SCPP_CALL_DEPTH_GUARD("type_refs::has_type_arg", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[87]);
	auto __latency_local_0 = table->args;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(row->parent_type_ref_id), cast<int_t<>>(parentTypeRefId)) && php::identical(cast<int_t<>>(row->arg_index), cast<int_t<>>(argIndex))) && php::identical(cast<int_t<>>(row->arg_kind_id), cast<int_t<>>(__latency_fn_type_refs_arg_kind_type_id()))))) {
			return bool_t(static_cast<bool_t>(true));
		}
	}
	return bool_t(static_cast<bool_t>(false));
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
void __latency_fn_type_refs_add_type_arg(TypeRefTable& table, int_t<std::uint32_t> parentTypeRefId, int_t<std::uint16_t> argIndex, int_t<std::uint32_t> typeArgRefId) {
	SCPP_CALL_DEPTH_GUARD("type_refs::add_type_arg", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[88]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_type_refs_has_type_arg(table, cast<int_t<std::uint32_t>>(parentTypeRefId), cast<int_t<std::uint16_t>>(argIndex))))) {
		return;
	}
	TypeArgRow row = TypeArgRow{};
	row->parent_type_ref_id = parentTypeRefId;
	row->arg_index = argIndex;
	row->arg_kind_id = __latency_fn_type_refs_arg_kind_type_id();
	row->type_arg_ref_id = typeArgRefId;
	row->status_id = __latency_fn_type_refs_arg_status_known_id();
	(void) table->args.append(row);
	table->arg_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(table->args));
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_type_refs_add_family_instance_with_type_args(TypeRefTable& table, int_t<std::uint32_t> familyId, const vector_t<int_t<std::uint32_t>>& typeArgRefIds) {
	SCPP_CALL_DEPTH_GUARD("type_refs::add_family_instance_with_type_args", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[89]);
	int_t<std::uint32_t> existingTypeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_type_refs_family_instance_type_ref_id_from_table(table, cast<int_t<std::uint32_t>>(familyId), typeArgRefIds));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(cast<int_t<>>(existingTypeRefId), cast<int_t<>>(__latency_fn_type_refs_unknown_id()))))) {
		return cast<int_t<std::uint32_t>>(existingTypeRefId);
	}
	int_t<std::uint32_t> typeRefId = required_cast<int_t<std::uint32_t>>(__latency_fn_type_refs_family_instance_type_ref_id_for_type_args(cast<int_t<std::uint32_t>>(familyId), typeArgRefIds));
	if (static_cast<bool>(php::identical(cast<int_t<>>(typeRefId), cast<int_t<>>(__latency_fn_type_refs_unknown_id())))) {
		return cast<int_t<std::uint32_t>>(typeRefId);
	}
	while (static_cast<bool>((__latency_fn_type_refs_has_type(table, cast<int_t<std::uint32_t>>(typeRefId)) && (!__latency_fn_type_refs_family_instance_matches_type_args(table, cast<int_t<std::uint32_t>>(typeRefId), cast<int_t<std::uint32_t>>(familyId), typeArgRefIds))))) {
		typeRefId = __latency_fn_structure_row_ids_uint32_from_int((cast<int_t<>>(typeRefId) + static_cast<int_t<> >(1)));
	}
	if (static_cast<bool>((!__latency_fn_type_refs_has_type(table, cast<int_t<std::uint32_t>>(typeRefId))))) {
		{
		auto __latency_local_0 = __latency_fn_type_refs_family_instance_row(cast<int_t<std::uint32_t>>(typeRefId), cast<int_t<std::uint32_t>>(familyId));
		(void) table->types.append(__latency_local_0);
		}
		table->type_count = __latency_fn_structure_row_ids_uint32_from_int(php::count(table->types));
	}
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_1 = typeArgRefIds;
	for (auto __latency_local_2 : foreach_range(__latency_local_1)) {
		auto typeArgRefId = __latency_local_2.value_copy();
		__latency_fn_type_refs_add_type(table, cast<int_t<std::uint32_t>>(typeArgRefId));
		__latency_fn_type_refs_add_type_arg(table, cast<int_t<std::uint32_t>>(typeRefId), __latency_fn_structure_row_ids_uint16_from_int(index), cast<int_t<std::uint32_t>>(typeArgRefId));
		index = (index + static_cast<int_t<> >(1));
	}
	return cast<int_t<std::uint32_t>>(typeRefId);
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_type_refs_add_family_instance(TypeRefTable& table, int_t<std::uint32_t> familyId, int_t<std::uint32_t> payloadTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_refs::add_family_instance", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[90]);
	vector_t<int_t<std::uint32_t>> typeArgRefIds = {};
	(void) typeArgRefIds.push_back(payloadTypeRefId);
	return __latency_fn_type_refs_add_family_instance_with_type_args(table, cast<int_t<std::uint32_t>>(familyId), typeArgRefIds);
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
void __latency_fn_type_refs_add_result_int_proof(TypeRefTable& table) {
	SCPP_CALL_DEPTH_GUARD("type_refs::add_result_int_proof", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[91]);
	__latency_fn_type_refs_add_family_instance(table, __latency_fn_type_refs_family_result_id(), __latency_fn_type_refs_int_id());
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
void __latency_fn_type_refs_add_nullable_int_proof(TypeRefTable& table) {
	SCPP_CALL_DEPTH_GUARD("type_refs::add_nullable_int_proof", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[92]);
	__latency_fn_type_refs_add_family_instance(table, __latency_fn_type_refs_family_nullable_id(), __latency_fn_type_refs_int_id());
}

}
