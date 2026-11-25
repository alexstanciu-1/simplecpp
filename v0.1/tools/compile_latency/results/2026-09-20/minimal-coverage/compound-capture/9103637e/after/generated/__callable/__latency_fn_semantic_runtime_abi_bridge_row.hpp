#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SemanticRuntimeAbiBridgeDescriptorRow;
shared_p<SemanticRuntimeAbiBridgeDescriptorRow> __latency_fn_semantic_runtime_abi_bridge_row(int_t<std::uint16_t> bridgeRowId, int_t<std::uint16_t> helperId, int_t<std::uint16_t> argumentCarrierId, int_t<std::uint16_t> returnCarrierId, int_t<std::uint16_t> ownershipPolicyId, int_t<std::uint16_t> lifetimePolicyId, int_t<std::uint16_t> callLoweringStatusId, int_t<std::uint16_t> sourceConsumptionStatusId, int_t<std::uint16_t> blockedReasonId, int_t<std::uint16_t> argumentCount, int_t<std::uint32_t> sourceTypeRefId, int_t<std::uint32_t> resultTypeRefId, const string_t& operationKey, const string_t& helperKey, const string_t& runtimeNamespace, const string_t& runtimeSymbol, const string_t& argumentSignatureKey, const string_t& resultRuntimeType, const string_t& llvmReturnType, const string_t& llvmArgumentSignature, const string_t& authoritySource);
}
