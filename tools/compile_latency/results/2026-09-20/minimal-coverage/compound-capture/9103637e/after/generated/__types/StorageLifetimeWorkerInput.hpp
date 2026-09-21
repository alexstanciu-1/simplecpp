#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
namespace scpp {
class StorageLifetimeWorkerInput {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> owner_run_id = static_cast<int_t<> >(0);
	int_t<> source_unit_id = static_cast<int_t<> >(0);
	int_t<> symbol_id = static_cast<int_t<> >(0);
	int_t<> ready_operation_contract_id = static_cast<int_t<> >(0);
	int_t<> ready_operation_lowering_adapter_id = static_cast<int_t<> >(0);
	int_t<> ready_operation_source_row_id = static_cast<int_t<> >(0);
	int_t<> ready_operation_kind_id = static_cast<int_t<> >(0);
	int_t<> ready_operation_capability_id = static_cast<int_t<> >(0);
	int_t<> ready_operation_consumer_feature_id = static_cast<int_t<> >(0);
	int_t<> ready_operation_provider_type_ref_id = static_cast<int_t<> >(0);
	int_t<> ready_operation_status_id = static_cast<int_t<> >(0);
	int_t<> ready_operation_blocked_reason_id = static_cast<int_t<> >(0);
	int_t<> ready_operation_result_type_ref_id = static_cast<int_t<> >(0);
	int_t<> blocked_operation_contract_id = static_cast<int_t<> >(0);
	int_t<> blocked_operation_lowering_adapter_id = static_cast<int_t<> >(0);
	int_t<> blocked_operation_source_row_id = static_cast<int_t<> >(0);
	int_t<> blocked_operation_kind_id = static_cast<int_t<> >(0);
	int_t<> blocked_operation_capability_id = static_cast<int_t<> >(0);
	int_t<> blocked_operation_consumer_feature_id = static_cast<int_t<> >(0);
	int_t<> blocked_operation_provider_type_ref_id = static_cast<int_t<> >(0);
	int_t<> blocked_operation_status_id = static_cast<int_t<> >(0);
	int_t<> blocked_operation_blocked_reason_id = static_cast<int_t<> >(0);
	int_t<> blocked_operation_result_type_ref_id = static_cast<int_t<> >(0);
	int_t<> ready_consumer_capability_id = static_cast<int_t<> >(0);
	int_t<> ready_consumer_source_row_id = static_cast<int_t<> >(0);
	int_t<> ready_consumer_provider_source_row_id = static_cast<int_t<> >(0);
	int_t<> ready_consumer_feature_id = static_cast<int_t<> >(0);
	int_t<> ready_consumer_source_key_id = static_cast<int_t<> >(0);
	int_t<> ready_consumer_provider_source_key_id = static_cast<int_t<> >(0);
	int_t<> ready_consumer_provider_type_ref_id = static_cast<int_t<> >(0);
	int_t<> ready_consumer_status_id = static_cast<int_t<> >(0);
	int_t<> ready_consumer_blocked_reason_id = static_cast<int_t<> >(0);
	int_t<> blocked_consumer_capability_id = static_cast<int_t<> >(0);
	int_t<> blocked_consumer_source_row_id = static_cast<int_t<> >(0);
	int_t<> blocked_consumer_provider_source_row_id = static_cast<int_t<> >(0);
	int_t<> blocked_consumer_feature_id = static_cast<int_t<> >(0);
	int_t<> blocked_consumer_source_key_id = static_cast<int_t<> >(0);
	int_t<> blocked_consumer_provider_source_key_id = static_cast<int_t<> >(0);
	int_t<> blocked_consumer_provider_type_ref_id = static_cast<int_t<> >(0);
	int_t<> blocked_consumer_status_id = static_cast<int_t<> >(0);
	int_t<> blocked_consumer_blocked_reason_id = static_cast<int_t<> >(0);
	int_t<> provider_capability_id = static_cast<int_t<> >(0);
	int_t<> provider_source_row_id = static_cast<int_t<> >(0);
	int_t<> provider_type_ref_id = static_cast<int_t<> >(0);
	int_t<> provider_source_key_id = static_cast<int_t<> >(0);
	int_t<> provider_status_id = static_cast<int_t<> >(0);
	int_t<> provider_adapter_id = static_cast<int_t<> >(0);
	int_t<> provider_evidence_id = static_cast<int_t<> >(0);
	int_t<> provider_blocked_reason_id = static_cast<int_t<> >(0);
};
}
