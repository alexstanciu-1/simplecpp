#include "modules/filesystem/filesystem.hpp"
#include "modules/filesystem/detail/snapshot_reader.hpp"
#if defined(__linux__)
#include "modules/filesystem/detail/snapshot_linux.hpp"
#endif

namespace scpp::fs {
result<string_t> read_snapshot(const string_t &path, const int_t<> &expected_mtime, const int_t<> &expected_size) {
#if defined(__linux__)
	const auto &name = path.native_value();
	if (name.empty() || name.find('\0') != std::string::npos) {
		return snapshot_detail::failure("path must be nonempty and contain no NUL bytes");
	}
	snapshot_detail::linux_operations operations(name);
	return snapshot_detail::read_checked(operations, expected_mtime.native_value(), expected_size.native_value());
#else
	(void)path; (void)expected_mtime; (void)expected_size;
	return snapshot_detail::failure("unsupported platform (Linux backend required)");
#endif
}
} // namespace scpp::fs
