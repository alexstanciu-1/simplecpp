#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct StorageLifetimeRequestRow;
int_t<std::uint32_t> __latency_fn_storage_lifetime_readiness_publication_ready_request_count(StorageLifetimeRequestRow readyRow, StorageLifetimeRequestRow blockedRow);
}
