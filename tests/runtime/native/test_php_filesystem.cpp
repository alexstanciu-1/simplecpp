#include "test_common.hpp"

#include "lang/php/support/php_resource.hpp"

#include <chrono>
#include <filesystem>
#include <fstream>
#include <string>
#include <sstream>
#include <thread>

namespace {

class temp_dir_guard final {
private:
	std::filesystem::path path_;

public:
	temp_dir_guard()
		: path_(([]() {
			const auto base = std::filesystem::path("/tmp");
			return base / ("scpp_fs_test_" + std::to_string(static_cast<long long>(std::chrono::steady_clock::now().time_since_epoch().count())));
		}())) {
		std::filesystem::create_directories(path_);
	}

	~temp_dir_guard() {
		std::error_code error;
		std::filesystem::remove_all(path_, error);
	}

	[[nodiscard]] const std::filesystem::path &path() const noexcept {
		return path_;
	}
};

static scpp::string_t to_string_t(const std::filesystem::path &path) {
	return scpp::string_t(path.string());
}

static void assert_true(const scpp::bool_t &value) {
	assert(value.native_value());
}

static void test_fopen_and_resource_kind() {
	temp_dir_guard guard;
	const auto file_path = guard.path() / "resource_kind.txt";
	std::ofstream(file_path).write("seed", 4);

	auto file = scpp::php::fopen(to_string_t(file_path), scpp::string_t("rb"));
	assert(file.has_value().native_value());
	assert(file.value().has_value().native_value());
	assert(file.value().get() != nullptr);
	assert(file.value().get()->kind == scpp::php::resource_kind::file_stream);
	assert(scpp::php::require_file_resource(file, "test_fopen_and_resource_kind").mode.native_value() == "rb");

	assert_true(scpp::php::fclose(file));
	assert(!scpp::php::fopen(to_string_t(guard.path() / "missing.txt"), scpp::string_t("r")).has_value().native_value());
	scpp_test::expect_throw<std::runtime_error>([]() {
		static_cast<void>(scpp::php::fopen(scpp::string_t("ignored"), scpp::string_t("unsupported")));
	});
}

static void test_stdio_roundtrip_and_aliases() {
	temp_dir_guard guard;
	const auto file_path = guard.path() / "roundtrip.txt";

	auto file = scpp::php::fopen(to_string_t(file_path), scpp::string_t("wb+"));
	assert(file.has_value().native_value());

	const auto bytes_written = scpp::php::fwrite(file, scpp::string_t("alpha\n"));
	assert(bytes_written.has_value().native_value());
	assert(bytes_written.value().native_value() == 6);

	const auto alias_written = scpp::php::fputs(file, scpp::string_t("beta"));
	assert(alias_written.has_value().native_value());
	assert(alias_written.value().native_value() == 4);

	const auto empty_write = scpp::php::fwrite(file, scpp::string_t(""));
	assert(empty_write.has_value().native_value());
	assert(empty_write.value().native_value() == 0);

	assert_true(scpp::php::rewind(file));

	const auto first_line = scpp::php::fgets(file);
	assert(first_line.has_value().native_value());
	assert(first_line.value().native_value() == "alpha\n");

	const auto position_after_line = scpp::php::ftell(file);
	assert(position_after_line.has_value().native_value());
	assert(position_after_line.value().native_value() == 6);

	const auto second_chunk = scpp::php::fread(file, scpp::int_t(4));
	assert(second_chunk.has_value().native_value());
	assert(second_chunk.value().native_value() == "beta");
	assert(!scpp::php::feof(file).native_value());

	const auto eof_probe = scpp::php::fread(file, scpp::int_t(1));
	assert(eof_probe.has_value().native_value());
	assert(eof_probe.value().native_value() == "");
	assert(scpp::php::feof(file).native_value());

	const auto seek_start = scpp::php::fseek(file, scpp::int_t(0));
	assert(seek_start.has_value().native_value());
	assert(seek_start.value().native_value() == 0);
	assert(!scpp::php::feof(file).native_value());

	assert_true(scpp::php::fflush(file));
	assert_true(scpp::php::fclose(file));

	scpp_test::expect_throw<std::runtime_error>([&]() {
		static_cast<void>(scpp::php::fread(file, scpp::int_t(1)));
	});
}

static void test_fgets_length_and_partial_line() {
	temp_dir_guard guard;
	const auto file_path = guard.path() / "line.txt";
	{
		std::ofstream out(file_path, std::ios::binary | std::ios::trunc);
		out << "abcdef\nlast";
	}

	auto file = scpp::php::fopen(to_string_t(file_path), scpp::string_t("rb"));
	assert(file.has_value().native_value());

	const auto limited = scpp::php::fgets(file, scpp::int_t(4));
	assert(limited.has_value().native_value());
	assert(limited.value().native_value() == "abc");

	const auto remainder = scpp::php::fgets(file);
	assert(remainder.has_value().native_value());
	assert(remainder.value().native_value() == "def\n");

	const auto tail = scpp::php::fgets(file);
	assert(tail.has_value().native_value());
	assert(tail.value().native_value() == "last");

	const auto eof_line = scpp::php::fgets(file);
	assert(!eof_line.has_value().native_value());

	assert_true(scpp::php::fclose(file));

	file = scpp::php::fopen(to_string_t(file_path), scpp::string_t("rb"));
	assert(file.has_value().native_value());
	scpp_test::expect_throw<std::runtime_error>([&]() {
		static_cast<void>(scpp::php::fgets(file, scpp::int_t(0)));
	});
	assert_true(scpp::php::fclose(file));
}

static void test_fread_and_fwrite_error_contracts() {
	temp_dir_guard guard;
	const auto file_path = guard.path() / "readonly.txt";
	{
		std::ofstream out(file_path, std::ios::binary | std::ios::trunc);
		out << "xyz";
	}

	auto read_only = scpp::php::fopen(to_string_t(file_path), scpp::string_t("rb"));
	assert(read_only.has_value().native_value());
	const auto zero_read = scpp::php::fread(read_only, scpp::int_t(0));
	assert(zero_read.has_value().native_value());
	assert(zero_read.value().native_value().empty());
	assert_true(scpp::php::fclose(read_only));

	read_only = scpp::php::fopen(to_string_t(file_path), scpp::string_t("rb"));
	assert(read_only.has_value().native_value());
	scpp_test::expect_throw<std::runtime_error>([&]() {
		static_cast<void>(scpp::php::fwrite(read_only, scpp::string_t("nope")));
	});
	assert_true(scpp::php::fclose(read_only));

	auto write_only = scpp::php::fopen(to_string_t(file_path), scpp::string_t("wb"));
	assert(write_only.has_value().native_value());
	scpp_test::expect_throw<std::runtime_error>([&]() {
		static_cast<void>(scpp::php::fread(write_only, scpp::int_t(1)));
	});
	assert_true(scpp::php::fclose(write_only));

	auto readable = scpp::php::fopen(to_string_t(file_path), scpp::string_t("rb"));
	assert(readable.has_value().native_value());
	scpp_test::expect_throw<std::runtime_error>([&]() {
		static_cast<void>(scpp::php::fread(readable, scpp::int_t(-1)));
	});
	assert_true(scpp::php::fclose(readable));
}

static void test_var_dump_or_false_outputs() {
	std::ostringstream captured;
	auto *old_buf = std::cout.rdbuf(captured.rdbuf());

	const scpp::result_or_false<scpp::int_t> bytes_written = scpp::int_t(12);
	scpp::php::var_dump(bytes_written);

	const scpp::result_or_false<scpp::string_t> file_text = scpp::string_t("Hello World\n");
	scpp::php::var_dump(file_text);

	const scpp::result_or_false<scpp::int_t> missing = scpp::false_sentinel;
	scpp::php::var_dump(missing);

	std::cout.rdbuf(old_buf);

	assert(captured.str() == std::string("int(12)\n") +
		std::string("string(12) \"Hello World\n\"\n") +
		std::string("bool(false)\n"));
}

static void test_file_helpers_and_paths() {
	temp_dir_guard guard;
	const auto file_path = guard.path() / "data.txt";
	const auto copy_path = guard.path() / "copy.txt";
	const auto moved_path = guard.path() / "moved.txt";
	const auto subdir_path = guard.path() / "subdir";
	const auto nested_dir = subdir_path / "child";

	const auto put_status = scpp::php::file_put_contents(to_string_t(file_path), scpp::string_t("payload"));
	assert(put_status.has_value().native_value());
	assert(put_status.value().native_value() == 7);

	const auto overwrite_status = scpp::php::file_put_contents(to_string_t(file_path), scpp::string_t("p"));
	assert(overwrite_status.has_value().native_value());
	assert(overwrite_status.value().native_value() == 1);

	assert(scpp::php::file_exists(to_string_t(file_path)).native_value());
	assert(scpp::php::is_file(to_string_t(file_path)).native_value());
	assert(!scpp::php::is_dir(to_string_t(file_path)).native_value());
	assert(!scpp::php::is_link(to_string_t(file_path)).native_value());

	const auto contents = scpp::php::file_get_contents(to_string_t(file_path));
	assert(contents.has_value().native_value());
	assert(contents.value().native_value() == "p");

	const auto empty_file = guard.path() / "empty.txt";
	std::ofstream(empty_file, std::ios::binary | std::ios::trunc).close();
	const auto empty_contents = scpp::php::file_get_contents(to_string_t(empty_file));
	assert(empty_contents.has_value().native_value());
	assert(empty_contents.value().native_value().empty());

	const auto size = scpp::php::filesize(to_string_t(file_path));
	assert(size.has_value().native_value());
	assert(size.value().native_value() == 1);

	const auto copy_status = scpp::php::copy(to_string_t(file_path), to_string_t(copy_path));
	assert(copy_status.native_value());
	assert(scpp::php::file_exists(to_string_t(copy_path)).native_value());

	const auto rename_status = scpp::php::rename(to_string_t(copy_path), to_string_t(moved_path));
	assert(rename_status.native_value());
	assert(!scpp::php::file_exists(to_string_t(copy_path)).native_value());
	assert(scpp::php::file_exists(to_string_t(moved_path)).native_value());

	const auto real = scpp::php::realpath(to_string_t(moved_path));
	assert(real.has_value().native_value());
	assert(real.value().native_value() == std::filesystem::canonical(moved_path).string());

	assert(scpp::php::dirname(to_string_t(nested_dir / "leaf.txt")).native_value() == nested_dir.string());
	assert(scpp::php::basename(to_string_t(nested_dir / "leaf.txt")).native_value() == "leaf.txt");

	const auto mkdir_status = scpp::php::mkdir(to_string_t(subdir_path));
	assert_true(mkdir_status);
	assert(scpp::php::is_dir(to_string_t(subdir_path)).native_value());
	assert(!scpp::php::mkdir(to_string_t(subdir_path)).native_value());

	const auto child_file = subdir_path / "child.txt";
	const auto child_put = scpp::php::file_put_contents(to_string_t(child_file), scpp::string_t("x"));
	assert(child_put.has_value().native_value());
	assert(child_put.value().native_value() == 1);
	const auto rmdir_non_empty = scpp::php::rmdir(to_string_t(subdir_path));
	assert(!rmdir_non_empty.native_value());
	assert_true(scpp::php::unlink(to_string_t(child_file)));
	assert_true(scpp::php::rmdir(to_string_t(subdir_path)));

	const auto unlink_status = scpp::php::unlink(to_string_t(moved_path));
	assert_true(unlink_status);
	assert(!scpp::php::file_exists(to_string_t(moved_path)).native_value());
	assert(!scpp::php::unlink(to_string_t(moved_path)).native_value());
}

static void test_touch_and_filemtime() {
	temp_dir_guard guard;
	const auto file_path = guard.path() / "touch.txt";

	const auto create_status = scpp::php::touch(to_string_t(file_path));
	assert_true(create_status);
	assert(scpp::php::file_exists(to_string_t(file_path)).native_value());

	const auto created_time = scpp::php::filemtime(to_string_t(file_path));
	assert(created_time.has_value().native_value());

	const auto older = std::filesystem::file_time_type::clock::now() - std::chrono::hours(1);
	std::error_code error;
	std::filesystem::last_write_time(file_path, older, error);
	assert(!error);

	const auto before_touch = scpp::php::filemtime(to_string_t(file_path));
	assert(before_touch.has_value().native_value());
	assert(before_touch.value().native_value() <= created_time.value().native_value());

	std::this_thread::sleep_for(std::chrono::milliseconds(20));
	const auto update_status = scpp::php::touch(to_string_t(file_path));
	assert_true(update_status);
	const auto after_touch = scpp::php::filemtime(to_string_t(file_path));
	assert(after_touch.has_value().native_value());
	assert(after_touch.value().native_value() >= before_touch.value().native_value());
}

static void test_scandir_sorted_names() {
	temp_dir_guard guard;
	const auto dir_path = guard.path() / "scan";
	std::filesystem::create_directories(dir_path);
	std::ofstream(dir_path / "b.txt").put('b');
	std::ofstream(dir_path / "a.txt").put('a');
	std::ofstream(dir_path / "A.txt").put('A');

	const auto listing = scpp::php::scandir(to_string_t(dir_path));
	assert(listing.has_value().native_value());
	const auto &table = listing.value();
	assert(table.size() == 3);
	assert(table.at(scpp::int_t(0)).get_string().native_value() == "A.txt");
	assert(table.at(scpp::int_t(1)).get_string().native_value() == "a.txt");
	assert(table.at(scpp::int_t(2)).get_string().native_value() == "b.txt");

	scpp::hash_t<scpp::mixed_t> taken_listing;
	assert(scpp::php::take(taken_listing, scpp::php::scandir(to_string_t(dir_path))).native_value());
	assert(taken_listing.size() == 3);

	std::size_t iterated = 0;
	for (auto file = listing.begin_entries(); file != listing.end_entries(); ++file) {
		const auto entry = *file;
		if (iterated == 0) {
			assert(entry.value_copy().get_string().native_value() == "A.txt");
		}
		++iterated;
	}
	assert(iterated == 3);

	const auto missing_listing = scpp::php::scandir(to_string_t(guard.path() / "missing_iterable"));
	assert(!missing_listing.has_value().native_value());
	scpp_test::expect_throw<std::runtime_error>([&]() {
		static_cast<void>(missing_listing.size());
	});
}

static void test_failure_contracts() {
	temp_dir_guard guard;
	const auto missing_file = guard.path() / "missing.txt";
	const auto missing_dir = guard.path() / "missing_dir";

	assert(!scpp::php::file_get_contents(to_string_t(missing_file)).has_value().native_value());
	assert(!scpp::php::file_put_contents(to_string_t(missing_dir / "child.txt"), scpp::string_t("x")).has_value().native_value());
	assert(!scpp::php::scandir(to_string_t(missing_dir)).has_value().native_value());
	assert(!scpp::php::realpath(to_string_t(missing_file)).has_value().native_value());
	assert(!scpp::php::touch(to_string_t(missing_dir / "child.txt")).native_value());
	assert(!scpp::php::filesize(to_string_t(missing_file)).has_value().native_value());
	assert(!scpp::php::filemtime(to_string_t(missing_file)).has_value().native_value());
	assert(!scpp::php::copy(to_string_t(missing_file), to_string_t(guard.path() / "dest.txt")).native_value());
	assert(!scpp::php::rename(to_string_t(missing_file), to_string_t(guard.path() / "dest.txt")).native_value());
	assert(!scpp::php::rmdir(to_string_t(missing_dir)).native_value());
}

static void test_shared_surfaces() {
	temp_dir_guard guard;
	const auto file_path = guard.path() / "shared.txt";
	const auto dir_path = guard.path() / "shared_dir";
	std::filesystem::create_directories(dir_path);
	std::ofstream(dir_path / "b.txt").put('b');
	std::ofstream(dir_path / "a.txt").put('a');

	const auto put_status = scpp::fs::put(to_string_t(file_path), scpp::string_t("payload"));
	assert(put_status.has_value().native_value());
	assert(put_status.value().native_value() == 7);

	const auto get_status = scpp::fs::get(to_string_t(file_path));
	assert(get_status.has_value().native_value());
	assert(get_status.value().native_value() == "payload");

	const auto size_status = scpp::fs::size(to_string_t(file_path));
	assert(size_status.has_value().native_value());
	assert(size_status.value().native_value() == 7);

	const auto list_status = scpp::fs::scan(to_string_t(dir_path));
	assert(list_status.has_value().native_value());
	assert(list_status.value().size() == 2);
	assert(list_status.value().native_value()[0].native_value() == "a.txt");
	assert(list_status.value().native_value()[1].native_value() == "b.txt");

	auto file = scpp::io::open(to_string_t(file_path), scpp::string_t("rb"));
	assert(file.has_value().native_value());
	const auto bytes = scpp::io::read(file, scpp::int_t(7));
	assert(bytes.has_value().native_value());
	assert(bytes.value().native_value() == "payload");
	assert(scpp::io::close(file).native_value());
}

} // namespace

int main() {
	test_fopen_and_resource_kind();
	test_stdio_roundtrip_and_aliases();
	test_fgets_length_and_partial_line();
	test_fread_and_fwrite_error_contracts();
	test_var_dump_or_false_outputs();
	test_file_helpers_and_paths();
	test_touch_and_filemtime();
	test_scandir_sorted_names();
	test_failure_contracts();
	test_shared_surfaces();
	return 0;
}
