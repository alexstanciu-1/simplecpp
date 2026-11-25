#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct CallableAbiReadinessRow;
struct ProjectCallableContractRow;
struct StorageLifetimeRequestRow;
CallableAbiReadinessRow __latency_fn_backend_preflight_requests_callable_abi_row_from_contract(int_t<std::uint32_t> abiRowId, ProjectCallableContractRow contract, StorageLifetimeRequestRow storage);
}
