#include <scpp/lang/php.hpp>
#include "__types/PrimitiveTypeRefSpellingDescriptorRow.hpp"
#include "__types/TypeArgRow.hpp"
#include "__types/TypeRefRow.hpp"
#include "__types/TypeRefTable.hpp"
#include "__callable/__latency_fn_type_refs_generic_family_instance_row_by_id.hpp"
#include "__callable/__latency_fn_type_refs_generic_family_instance_rows.hpp"
#include "__callable/__latency_fn_type_refs_family_instance_type_ref_id.hpp"
#include "__callable/__latency_fn_type_refs_family_nullable_id.hpp"
#include "__callable/__latency_fn_type_refs_family_result_id.hpp"
#include "__callable/__latency_fn_type_refs_generic_family_instance_spelling_descriptors.hpp"
#include "__callable/__latency_fn_type_refs_int_id.hpp"
#include "__callable/__latency_fn_type_refs_primitive_spelling_descriptor.hpp"
#include "__callable/__latency_fn_type_refs_family_nullable_id.hpp"
#include "__callable/__latency_fn_type_refs_family_result_id.hpp"
#include "__callable/__latency_fn_type_refs_family_vector_id.hpp"
#include "__callable/__latency_fn_type_refs_primitive_spelling_descriptor.hpp"
#include "__callable/__latency_fn_type_refs_source_family_spelling_descriptors.hpp"
#include "__callable/__latency_fn_type_refs_source_family_id_for_name.hpp"
#include "__callable/__latency_fn_type_refs_source_family_spelling_descriptors.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
#include "__callable/__latency_fn_type_refs_generic_family_instance_source_name_by_id.hpp"
#include "__callable/__latency_fn_type_refs_generic_family_instance_spelling_descriptors.hpp"
#include "__callable/__latency_fn_type_refs_family_id_from_type_ref_id.hpp"
#include "__callable/__latency_fn_type_refs_row_from_id.hpp"
#include "__callable/__latency_fn_type_refs_type_kind_family_instance_id.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
#include "__callable/__latency_fn_type_refs_family_name_by_id.hpp"
#include "__callable/__latency_fn_type_refs_source_family_spelling_descriptors.hpp"
#include "__callable/__latency_fn_type_refs_arg_kind_type_id.hpp"
#include "__callable/__latency_fn_type_refs_type_arg_count.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_type_refs_arg_kind_type_id.hpp"
#include "__callable/__latency_fn_type_refs_family_instance_matches_type_args.hpp"
#include "__callable/__latency_fn_type_refs_row_by_id.hpp"
#include "__callable/__latency_fn_type_refs_type_arg_count.hpp"
#include "__callable/__latency_fn_type_refs_type_kind_family_instance_id.hpp"
#include "__callable/__latency_fn_type_refs_unknown_id.hpp"
namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
TypeRefRow __latency_fn_type_refs_generic_family_instance_row_by_id(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_refs::generic_family_instance_row_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[65]);
	auto __latency_local_0 = __latency_fn_type_refs_generic_family_instance_rows();
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
vector_t<shared_p<PrimitiveTypeRefSpellingDescriptorRow>> __latency_fn_type_refs_generic_family_instance_spelling_descriptors() {
	SCPP_CALL_DEPTH_GUARD("type_refs::generic_family_instance_spelling_descriptors", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[66]);
	vector_t<shared_p<PrimitiveTypeRefSpellingDescriptorRow>> rows = {};
	php::vector_reserve(rows, static_cast<int_t<> >(2));
	{
	auto __latency_local_0 = __latency_fn_type_refs_primitive_spelling_descriptor(__latency_fn_type_refs_family_instance_type_ref_id(__latency_fn_type_refs_family_result_id(), __latency_fn_type_refs_int_id()), string_t("result<int>"));
	(void) rows.push_back(__latency_local_0);
	}
	{
	auto __latency_local_1 = __latency_fn_type_refs_primitive_spelling_descriptor(__latency_fn_type_refs_family_instance_type_ref_id(__latency_fn_type_refs_family_nullable_id(), __latency_fn_type_refs_int_id()), string_t("nullable<int>"));
	(void) rows.push_back(__latency_local_1);
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
vector_t<shared_p<PrimitiveTypeRefSpellingDescriptorRow>> __latency_fn_type_refs_source_family_spelling_descriptors() {
	SCPP_CALL_DEPTH_GUARD("type_refs::source_family_spelling_descriptors", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[67]);
	vector_t<shared_p<PrimitiveTypeRefSpellingDescriptorRow>> rows = {};
	php::vector_reserve(rows, static_cast<int_t<> >(3));
	{
	auto __latency_local_0 = __latency_fn_type_refs_primitive_spelling_descriptor(__latency_fn_type_refs_family_vector_id(), string_t("vector"));
	(void) rows.push_back(__latency_local_0);
	}
	{
	auto __latency_local_1 = __latency_fn_type_refs_primitive_spelling_descriptor(__latency_fn_type_refs_family_result_id(), string_t("result"));
	(void) rows.push_back(__latency_local_1);
	}
	{
	auto __latency_local_2 = __latency_fn_type_refs_primitive_spelling_descriptor(__latency_fn_type_refs_family_nullable_id(), string_t("nullable"));
	(void) rows.push_back(__latency_local_2);
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_type_refs_source_family_id_for_name(const string_t& sourceName) {
	SCPP_CALL_DEPTH_GUARD("type_refs::source_family_id_for_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[68]);
	auto __latency_local_0 = __latency_fn_type_refs_source_family_spelling_descriptors();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(row->source_name, sourceName))) {
			return row->type_ref_id;
		}
	}
	return __latency_fn_type_refs_unknown_id();
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
string_t __latency_fn_type_refs_generic_family_instance_source_name_by_id(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_refs::generic_family_instance_source_name_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[69]);
	auto __latency_local_0 = __latency_fn_type_refs_generic_family_instance_spelling_descriptors();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->type_ref_id), cast<int_t<>>(typeRefId)))) {
			return row->source_name;
		}
	}
	return string_t("");
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_type_refs_family_id_from_type_ref_id(int_t<std::uint32_t> typeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_refs::family_id_from_type_ref_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[70]);
	TypeRefRow row = __latency_fn_type_refs_row_from_id(cast<int_t<std::uint32_t>>(typeRefId));
	if (static_cast<bool>((php::identical(cast<int_t<>>(row->type_ref_id), cast<int_t<>>(__latency_fn_type_refs_unknown_id())) || php::not_identical(cast<int_t<>>(row->type_kind_id), cast<int_t<>>(__latency_fn_type_refs_type_kind_family_instance_id()))))) {
		return __latency_fn_type_refs_unknown_id();
	}
	return row->family_id;
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
string_t __latency_fn_type_refs_family_name_by_id(int_t<std::uint32_t> familyId) {
	SCPP_CALL_DEPTH_GUARD("type_refs::family_name_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[71]);
	auto __latency_local_0 = __latency_fn_type_refs_source_family_spelling_descriptors();
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->type_ref_id), cast<int_t<>>(familyId)))) {
			return row->source_name;
		}
	}
	return (string_t("family#") + cast<string_t>(cast<int_t<>>(familyId)));
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
int_t<> __latency_fn_type_refs_type_arg_count(TypeRefTable table, int_t<std::uint32_t> parentTypeRefId) {
	SCPP_CALL_DEPTH_GUARD("type_refs::type_arg_count", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[72]);
	int_t<> count = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = table->args;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(row->parent_type_ref_id), cast<int_t<>>(parentTypeRefId)) && php::identical(cast<int_t<>>(row->arg_kind_id), cast<int_t<>>(__latency_fn_type_refs_arg_kind_type_id()))))) {
			count = (count + static_cast<int_t<> >(1));
		}
	}
	return count;
}

}

namespace scpp { extern const int __latency_lines_type_refs[]; }
namespace scpp {
bool_t __latency_fn_type_refs_family_instance_matches_type_args(TypeRefTable table, int_t<std::uint32_t> typeRefId, int_t<std::uint32_t> familyId, const vector_t<int_t<std::uint32_t>>& typeArgRefIds) {
	SCPP_CALL_DEPTH_GUARD("type_refs::family_instance_matches_type_args", "/tmp/scpp-edit-latency-20260919/app/compile/model/type_refs.phs", __latency_lines_type_refs[73]);
	TypeRefRow row = __latency_fn_type_refs_row_by_id(table, cast<int_t<std::uint32_t>>(typeRefId));
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->type_ref_id), cast<int_t<>>(__latency_fn_type_refs_unknown_id())))) {
		return bool_t(static_cast<bool_t>(false));
	}
	if (static_cast<bool>((php::not_identical(cast<int_t<>>(row->type_kind_id), cast<int_t<>>(__latency_fn_type_refs_type_kind_family_instance_id())) || php::not_identical(cast<int_t<>>(row->family_id), cast<int_t<>>(familyId))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	if (static_cast<bool>(php::condition_truthy(php::not_identical(__latency_fn_type_refs_type_arg_count(table, cast<int_t<std::uint32_t>>(typeRefId)), php::count(typeArgRefIds))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	int_t<> index = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto& __latency_local_0 = typeArgRefIds;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto typeArgRefId = __latency_local_1.value_copy();
		int_t<std::uint16_t> argIndex = required_cast<int_t<std::uint16_t>>(__latency_fn_structure_row_ids_uint16_from_int(index));
		bool_t matched = required_cast<bool_t>(bool_t(static_cast<bool_t>(false)));
		auto __latency_local_2 = table->args;
		for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
			auto argRow = __latency_local_3.value_copy();
			if (static_cast<bool>((((php::identical(cast<int_t<>>(argRow->parent_type_ref_id), cast<int_t<>>(typeRefId)) && php::identical(cast<int_t<>>(argRow->arg_index), cast<int_t<>>(argIndex))) && php::identical(cast<int_t<>>(argRow->arg_kind_id), cast<int_t<>>(__latency_fn_type_refs_arg_kind_type_id()))) && php::identical(cast<int_t<>>(argRow->type_arg_ref_id), cast<int_t<>>(typeArgRefId))))) {
				matched = bool_t(static_cast<bool_t>(true));
			}
		}
		if (static_cast<bool>((!matched))) {
			return bool_t(static_cast<bool_t>(false));
		}
		index = (index + static_cast<int_t<> >(1));
	}
	return bool_t(static_cast<bool_t>(true));
}

}
