#include <scpp/lang/php.hpp>
#include "__types/ResidentReverseDependencyLookupRow.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_status_dependent_found_id.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_status_name.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_status_no_dependents_id.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_debug_string.hpp"
#include "__callable/__latency_fn_resident_reverse_dependencies_status_name.hpp"
namespace scpp { extern const int __latency_lines_resident_reverse_dependencies[]; }
namespace scpp {
string_t __latency_fn_resident_reverse_dependencies_status_name(int_t<std::uint16_t> statusId) {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependencies::status_name", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependencies.phs", __latency_lines_resident_reverse_dependencies[10]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_resident_reverse_dependencies_status_dependent_found_id())))) {
		return string_t("dependent_found");
	}
	if (static_cast<bool>(php::identical(cast<int_t<>>(statusId), cast<int_t<>>(__latency_fn_resident_reverse_dependencies_status_no_dependents_id())))) {
		return string_t("no_dependents");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_resident_reverse_dependencies[]; }
namespace scpp {
string_t __latency_fn_resident_reverse_dependencies_debug_string(ResidentReverseDependencyLookupRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_reverse_dependencies::debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_reverse_dependencies.phs", __latency_lines_resident_reverse_dependencies[11]);
	if (static_cast<bool>(php::identical(cast<int_t<>>(row->lookup_id), static_cast<int_t<> >(0)))) {
		return string_t("");
	}
	return (string_t("resident_reverse_dependency:") + cast<string_t>(cast<int_t<>>(row->provider_symbol_id)) + string_t("->") + cast<string_t>(cast<int_t<>>(row->consumer_symbol_id)) + string_t(":") + cast<string_t>(__latency_fn_resident_reverse_dependencies_status_name(row->status_id)));
}

}
