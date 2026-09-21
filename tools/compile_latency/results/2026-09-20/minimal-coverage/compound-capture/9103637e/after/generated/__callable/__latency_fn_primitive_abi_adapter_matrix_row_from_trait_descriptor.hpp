#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class PrimitiveAbiAdapterRow;
struct ProviderTraitDescriptorRow;
shared_p<PrimitiveAbiAdapterRow> __latency_fn_primitive_abi_adapter_matrix_row_from_trait_descriptor(int_t<std::uint16_t> adapterId, ProviderTraitDescriptorRow descriptor, int_t<std::uint16_t> mainExitPolicyId, int_t<std::uint16_t> llvmValueStatusId, int_t<std::uint16_t> mainExitStatusId, int_t<std::uint16_t> numericLoweringStatusId, int_t<std::uint16_t> nativeStatusId, int_t<std::uint16_t> loweringAdapterFamilyId, int_t<std::uint16_t> blockedReasonId);
}
