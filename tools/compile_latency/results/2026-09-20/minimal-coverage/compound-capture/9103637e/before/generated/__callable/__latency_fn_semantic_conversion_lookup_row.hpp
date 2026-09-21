#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SemanticConversionLookupRow;
shared_p<SemanticConversionLookupRow> __latency_fn_semantic_conversion_lookup_row(int_t<std::uint16_t> conversionId, int_t<std::uint16_t> kindId, int_t<std::uint16_t> formId, int_t<std::uint16_t> permissionId, int_t<std::uint32_t> sourceTypeRefId, int_t<std::uint32_t> targetTypeRefId, int_t<std::uint32_t> authorityIndex, bool_t implicitAllowed, bool_t explicitAllowed, bool_t requiresRuntimeCheck, const string_t& sourceRuntimeType, const string_t& targetRuntimeType, const string_t& castName, const string_t& policyRole, const string_t& diagnosticKey);
}
