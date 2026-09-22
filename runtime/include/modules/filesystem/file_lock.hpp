#pragma once

#include "scpp/result.hpp"
#include "scpp/shared_p.hpp"

#include <cstdint>

namespace scpp::fs {

// One closeable token shared by aliases. See specs/builtins/filesystem/file_locks.md.
class file_lock final {
public:
	~file_lock();
	file_lock(const file_lock &) = delete;
	file_lock &operator=(const file_lock &) = delete;

private:
	file_lock() = default;
	int descriptor_ = -1;
	std::int64_t owner_pid_ = 0;
	bool held_ = false;

	friend result<bool_t> lock_try(shared_p<file_lock> &, const string_t &, const bool_t &);
	friend result<bool_t> lock_release(const shared_p<file_lock> &);
	friend result<shared_p<file_lock>> lock_transfer(const shared_p<file_lock> &);
};

[[nodiscard]] result<bool_t> lock_try(shared_p<file_lock> &out, const string_t &path, const bool_t &shared = bool_t(false));
[[nodiscard]] result<bool_t> lock_release(const shared_p<file_lock> &handle);
[[nodiscard]] result<shared_p<file_lock>> lock_transfer(const shared_p<file_lock> &handle);

} // namespace scpp::fs

namespace scpp {
using file_lock_handle = fs::file_lock;
}
