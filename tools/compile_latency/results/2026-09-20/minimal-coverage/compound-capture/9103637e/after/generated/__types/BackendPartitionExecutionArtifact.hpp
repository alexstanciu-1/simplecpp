#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/BackendPartitionExecutionRow.hpp"
#include "__types/BackendProjectLinkExecutionRow.hpp"
namespace scpp {
class BackendPartitionExecutionArtifact {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> artifact_kind_id = static_cast<int_t<> >(1);
	int_t<> schema_version = static_cast<int_t<> >(1);
	int_t<> source_model_id = static_cast<int_t<> >(1);
	int_t<> execution_model_id = static_cast<int_t<> >(1);
	int_t<> partition_count = static_cast<int_t<> >(0);
	int_t<> ready_partition_count = static_cast<int_t<> >(0);
	int_t<> blocked_partition_count = static_cast<int_t<> >(0);
	int_t<> link_count = static_cast<int_t<> >(0);
	vector_t<BackendPartitionExecutionRow> partitions = vector_t<BackendPartitionExecutionRow>{};
	vector_t<BackendProjectLinkExecutionRow> links = vector_t<BackendProjectLinkExecutionRow>{};
};
}
