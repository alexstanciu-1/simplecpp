#pragma once

#include "scpp/string_t.hpp"

#include <cstdint>
#include <string>
#include <unordered_map>
#include <vector>

namespace scpp {

// Owns the single process-wide string-key pool used by hash_t key interning.
// This must stay in one compiled runtime implementation so string-key ids remain
// stable across generated code and runtime helpers.
class global_string_pool final {
public:
	static constexpr std::uint32_t string_key_flag = 0x80000000u;
	static constexpr std::uint32_t string_id_mask  = 0x7FFFFFFFu;

	static global_string_pool &instance();

	global_string_pool(const global_string_pool &)            = delete;
	global_string_pool &operator=(const global_string_pool &) = delete;

	std::uint32_t intern(const string_t &value);
	[[nodiscard]] string_t resolve(std::uint32_t tagged_id) const;
	static bool is_string_id(std::uint32_t tagged_id);

private:
	global_string_pool();

	std::unordered_map<std::string, std::uint32_t> lookup_;
	std::vector<const std::string *> strings_;
};

} // namespace scpp
