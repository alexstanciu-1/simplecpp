#pragma once
#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>
#include "__types/ArtifactWriteRecord.hpp"
namespace scpp {
class ArtifactWriteReportRecord {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> artifact_kind_id = static_cast<int_t<> >(1);
	int_t<> artifact_key_id = static_cast<int_t<> >(1);
	int_t<> schema_version = static_cast<int_t<> >(1);
	int_t<> write_policy_id = static_cast<int_t<> >(1);
	int_t<> row_count = static_cast<int_t<> >(0);
	int_t<> written_count = static_cast<int_t<> >(0);
	int_t<> reused_count = static_cast<int_t<> >(0);
	int_t<> failed_count = static_cast<int_t<> >(0);
	int_t<> skipped_count = static_cast<int_t<> >(0);
	int_t<> total_byte_count = static_cast<int_t<> >(0);
	vector_t<ArtifactWriteRecord> rows = vector_t<ArtifactWriteRecord>{};
};
}
