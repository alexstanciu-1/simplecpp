#pragma once

#include <algorithm>
#include <cstdint>
#include <limits>
#include <string>
#include "scpp/error_t.hpp"
#include "scpp/result.hpp"
#include "scpp/string_t.hpp"

namespace scpp::fs::snapshot_detail {

inline error_t failure(const char *reason) {
	return error_t(string_t(std::string("fs_read_snapshot: ") + reason));
}

// Backend-owned observations and identity never escape the filesystem module.
// The seam is private: production has no callback/hook or shared mutable state.
template <typename Operations>
result<string_t> read_checked(Operations &ops, std::int64_t mtime, std::int64_t size) {
	struct cleanup {
		Operations &ops;
		~cleanup() { ops.close(); }
	} guard{ops};
	if (size < 0 || static_cast<std::uint64_t>(size) >= std::string().max_size()
		|| static_cast<std::uint64_t>(size) >= std::numeric_limits<std::size_t>::max()) {
		return failure("invalid expected size");
	}
	using metadata = typename Operations::metadata;
	metadata before{}, opened{}, after{}, named{};
	if (!ops.path_status(before)) { return ops.error("initial path stat"); }
	if (!ops.regular(before)) { return failure("path is not a regular file"); }
	const auto matches = [&](const metadata &value) {
		return ops.regular(value) && ops.mtime(value) == mtime
			&& ops.size(value) == static_cast<std::uint64_t>(size)
			&& ops.same_identity(before, value);
	};
	if (!matches(before)) { return failure("source version changed"); }
	if (!ops.open()) { return ops.error("open"); }
	if (!ops.handle_status(opened)) { return ops.error("initial handle stat"); }
	if (!matches(opened)) { return failure("source version or identity changed before read"); }

	const auto limit = static_cast<std::size_t>(size) + 1;
	std::string content;
	char buffer[65536];
	while (content.size() < limit) {
		const auto amount = std::min(sizeof(buffer), limit - content.size());
		const auto count = ops.read(buffer, amount);
		if (count < 0) { return ops.error("read"); }
		if (count == 0) { break; }
		content.append(buffer, static_cast<std::size_t>(count));
	}
	if (!ops.handle_status(after)) { return ops.error("final handle stat"); }
	if (!ops.path_status(named)) { return ops.error("final path stat"); }
	if (!matches(after) || !matches(named) || content.size() != static_cast<std::size_t>(size)) {
		return failure("source version or identity changed during read");
	}
	return string_t(std::move(content));
}
} // namespace scpp::fs::snapshot_detail
