#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class SemanticOperatorLookupRow;
shared_p<SemanticOperatorLookupRow> __latency_fn_semantic_operator_lookup_row(int_t<std::uint16_t> operatorId, int_t<std::uint16_t> arity, const string_t& operatorSymbol, const string_t& operatorName, const string_t& authorityItemId, const string_t& authorityRowFile, int_t<std::uint32_t> lhsTypeRefId, int_t<std::uint32_t> rhsTypeRefId, int_t<std::uint32_t> resultTypeRefId, int_t<std::uint16_t> capabilityId, int_t<std::uint16_t> operandCategoryId, int_t<std::uint16_t> featureId, int_t<std::uint16_t> operationKindId, int_t<std::uint16_t> contractId, int_t<std::uint16_t> loweringAdapterId, int_t<std::uint16_t> loweringStepKindId, int_t<std::uint16_t> localImmediateOperationId, int_t<std::uint16_t> localStoreOperationId, int_t<std::uint16_t> precedence, const string_t& loweringStepKey, const string_t& binaryResultKey, const string_t& llvmOpcode, const string_t& runtimeSymbol, const string_t& diagnosticFamily, const string_t& diagnosticOperandSuffix);
}
