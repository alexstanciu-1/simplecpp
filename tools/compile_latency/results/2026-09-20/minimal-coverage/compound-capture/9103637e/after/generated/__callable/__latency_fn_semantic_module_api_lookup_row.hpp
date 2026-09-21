#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SemanticModuleApiLookupRow;
shared_p<SemanticModuleApiLookupRow> __latency_fn_semantic_module_api_lookup_row(int_t<std::uint16_t> rowId, int_t<std::uint16_t> moduleOwnerId, int_t<std::uint16_t> availabilityPolicyId, int_t<std::uint16_t> contractStatusId, int_t<std::uint16_t> signatureStatusId, int_t<std::uint16_t> sourceConsumptionStatusId, int_t<std::int32_t> minArity, int_t<std::int32_t> maxArity, int_t<std::uint16_t> parameterTypeRefCount, int_t<std::uint32_t> returnTypeRefId, const string_t& parameterTypeRefKeys, const string_t& sourceName, const string_t& runtimeTarget, const string_t& moduleOwnerKey, const string_t& returnTypeRefKey, const string_t& authoritySource, const string_t& contractAuthoritySource, const string_t& blockedReason);
}
