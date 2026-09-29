#include "test_common.hpp"
#include "modules/filesystem/filesystem.hpp"
#include "modules/filesystem/detail/snapshot_linux.hpp"
#include <filesystem>
#include <fstream>
#include <functional>
#include <limits>

using namespace scpp;
namespace fsys = std::filesystem;
using backend = fs::snapshot_detail::linux_operations;
constexpr std::int64_t stamp = 1700000000;
static std::size_t descriptors() {
	return std::distance(fsys::directory_iterator("/proc/self/fd"), fsys::directory_iterator());
}
static void write_file(const std::string &path, const std::string &content) {
	{ std::ofstream out(path, std::ios::binary | std::ios::trunc); out.write(content.data(), content.size()); assert(out.good()); }
	timespec times[2]{{stamp, 0}, {stamp, 0}};
	assert(::utimensat(AT_FDCWD, path.c_str(), times, 0) == 0);
}
struct instrumented : backend {
	using backend::backend;
	std::function<void(const std::string &)> hook;
	std::string fail;
	int path_calls = 0, handle_calls = 0, read_calls = 0, closes = 0;
	std::size_t consumed = 0, requested = 0;
	bool event(const std::string &stage) {
		if (hook) hook(stage);
		if (stage == fail) { errno = EIO; return false; }
		return true;
	}
	bool path_status(metadata &s) { return event("path" + std::to_string(++path_calls)) && backend::path_status(s); }
	bool open() { return event("open") && backend::open(); }
	bool handle_status(metadata &s) { return event("handle" + std::to_string(++handle_calls)) && backend::handle_status(s); }
	ssize_t read(char *buffer, std::size_t count) {
		requested = std::max(requested, count);
		if (!event("read" + std::to_string(++read_calls))) return -1;
		auto result = backend::read(buffer, count);
		if (result > 0) consumed += static_cast<std::size_t>(result);
		return result;
	}
	void close() noexcept { ++closes; backend::close(); }
};
int main() {
	char pattern[] = "/tmp/scpp-snapshot-XXXXXX";
	const auto created = ::mkdtemp(pattern); assert(created);
	const std::string dir(created), path = dir + "/source", replacement = dir + "/replacement";
	struct cleanup { std::string dir; ~cleanup() { fsys::remove_all(dir); } } cleanup{dir};
	const auto baseline = descriptors();
	const std::string bytes("a\0b\ncd", 6);
	const auto public_read = [&](std::int64_t size, std::int64_t mtime = stamp) {
		return fs::read_snapshot(string_t(path), int_t<>(mtime), int_t<>(size));
	};
	write_file(path, bytes);
	assert(public_read(6).value().native_value() == bytes);
	assert(public_read(5).has_error().native_value());
	assert(public_read(7).has_error().native_value());
	assert(public_read(6, stamp + 1).has_error().native_value());
	assert(public_read(-1).has_error().native_value());
	assert(public_read(std::numeric_limits<std::int64_t>::max()).has_error().native_value());
	assert(fs::read_snapshot(string_t(std::string("x\0y", 3)), int_t<>(stamp), int_t<>(0)).has_error().native_value());
	assert(fs::read_snapshot(string_t(""), int_t<>(stamp), int_t<>(0)).has_error().native_value());
	write_file(path, "");
	assert(public_read(0).value().native_value().empty());
	fsys::remove(path);
	assert(public_read(0).has_error().native_value());
	write_file(replacement, bytes);
	fsys::create_symlink(replacement, path);
	assert(public_read(6).has_error().native_value());
	fsys::remove(path); fsys::create_directory(path);
	assert(public_read(6).has_error().native_value());
	fsys::remove(path); assert(::mkfifo(path.c_str(), 0600) == 0);
	assert(public_read(6).has_error().native_value());
	fsys::remove(path);

	// Actual inode/device comparisons: same-length, same-mtime replacement is rejected.
	for (const std::string stage : {"open", "handle1", "read1", "handle2", "path2"}) {
		write_file(path, bytes); write_file(replacement, "UVWXYZ");
		instrumented ops(path);
		ops.hook = [&](const std::string &at) { if (at == stage) fsys::rename(replacement, path); };
		assert(fs::snapshot_detail::read_checked(ops, stamp, 6).has_error().native_value());
		assert(ops.closes == 1 && descriptors() == baseline);
	}
	// Removed name behind a still-open descriptor cannot publish content.
	write_file(path, bytes);
	{
		instrumented ops(path);
		ops.hook = [&](const std::string &at) { if (at == "path2") fsys::remove(path); };
		assert(fs::snapshot_detail::read_checked(ops, stamp, 6).has_error().native_value());
		assert(ops.closes == 1 && descriptors() == baseline);
	}
	// A final-component symlink or FIFO swapped in after lstat cannot block/follow.
	for (bool fifo : {false, true}) {
		write_file(path, bytes); write_file(replacement, bytes);
		instrumented ops(path);
		ops.hook = [&](const std::string &at) {
			if (at == "open") { fsys::remove(path); if (fifo) assert(::mkfifo(path.c_str(), 0600) == 0); else fsys::create_symlink(replacement, path); }
		};
		assert(fs::snapshot_detail::read_checked(ops, stamp, 6).has_error().native_value());
		assert(ops.closes == 1 && descriptors() == baseline); fsys::remove(path);
	}
	for (const std::string changed : {std::string(200000, 'g'), std::string("x")}) {
		write_file(path, bytes);
		instrumented ops(path);
		ops.hook = [&](const std::string &at) { if (at == "read1") write_file(path, changed); };
		assert(fs::snapshot_detail::read_checked(ops, stamp, 6).has_error().native_value());
		assert(ops.consumed <= 7 && ops.requested <= 7);
		assert(ops.closes == 1 && descriptors() == baseline);
	}
	// Every stat/open/read boundary can fail, including a read after partial data.
	for (const std::string stage : {"path1", "open", "handle1", "read1", "read2", "handle2", "path2"}) {
		write_file(path, std::string(100000, 'a'));
		instrumented ops(path); ops.fail = stage;
		assert(fs::snapshot_detail::read_checked(ops, stamp, 100000).has_error().native_value());
		assert(ops.closes == 1 && descriptors() == baseline);
	}
	for (const std::string stage : {"handle1", "read2", "handle2", "path2"}) {
		write_file(path, std::string(100000, 'a'));
		instrumented ops(path);
		ops.hook = [&](const std::string &at) { if (at == stage) throw std::runtime_error("injected exception"); };
		scpp_test::expect_throw<std::runtime_error>([&] { (void)fs::snapshot_detail::read_checked(ops, stamp, 100000); });
		assert(ops.closes == 1 && descriptors() == baseline);
	}
	// Document the deliberate limit: same inode, size and whole-second mtime edits.
	write_file(path, bytes);
	{
		instrumented ops(path);
		ops.hook = [&](const std::string &at) { if (at == "read1") write_file(path, "UVWXYZ"); };
		assert(fs::snapshot_detail::read_checked(ops, stamp, 6).value().native_value() == "UVWXYZ");
		assert(ops.closes == 1 && descriptors() == baseline);
	}
}
