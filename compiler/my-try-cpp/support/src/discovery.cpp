#include "scpp_compiler/file_records.hpp"
#include "scalar_access.hpp"
#include "scpp/vector_t.hpp"
#include "string_state.hpp"
#include "modules/filesystem/filesystem.hpp"
#include "modules/tasks/tasks.hpp"
#include "modules/datetime/datetime.hpp"
#include <atomic>

namespace scpp::compiler_support
{
	struct directory_job {
		std::int64_t root;
		string_t relative;
	};

	struct directory_result
	{
		vector_t<directory_job> directories;
		vector_t<file_record> files;
		std::uint64_t entries = 0;
	};

	boolean fs_is_link(const string &path)
	{
		return fs::is_link(runtime_access::get(path)).native_value();
	}

	boolean fs_path_contains(const string &root, const string &path)
	{
		const auto &a = runtime_access::get(root).native_value();
		const auto &b = runtime_access::get(path).native_value();
		return a == b || (b.starts_with(a) && (a == "/" || (b.size() > a.size() && b[a.size()] == '/')));
	}

	/**
	 * Read one directory into private observations. Symlinks are never traversed.
	 */
	static directory_result read_directory(const directory_job &job, const vector_string &roots,
		const vector_string &excluded, const string &suffix)
	{
		directory_result result;
		const auto base = runtime_access::get(roots.read(job.root)).native_value();
		const auto prefix = base == "/" ? base : base + "/";
		const auto folder = prefix + job.relative.native_value();
		if (fs::is_link(string_t(folder)).native_value()) {
			throw std::runtime_error("Directory became a symlink: " + folder);
		}
		const auto listing = fs::scan(string_t(folder));
		if (!listing.has_value().native_value()) {
			throw std::runtime_error("Cannot scan source directory: " + folder);
		}
		const auto names = listing.value();
		for (const auto &name : names.native_value())
		{
			++result.entries;
			const auto relative = job.relative.native_value() + name.native_value();
			const string_t full(prefix + relative);
			if (fs::is_link(full).native_value()) {
				continue;
			}
			if (fs::is_dir(full).native_value()) {
				if (!excluded.contains(runtime_access::wrap(name))) {
					result.directories.append(directory_job{job.root, string_t(relative + "/")});
				}
				continue;
			}
			if (!name.native_value().ends_with(runtime_access::get(suffix).native_value())) {
				continue;
			}
			if (!fs::is_file(full).native_value()) {
				if (!fs::exists(full).native_value()) {
					throw std::runtime_error("File disappeared during discovery: " + full.native_value());
				}
				continue;
			}
			file_record row;
			row.root = job.root;
			row.path = runtime_access::wrap(string_t(relative));
			const auto mtime = fs::mtime(full);
			const auto bytes = fs::size(full);
			if (!mtime.has_value().native_value() || !bytes.has_value().native_value()) {
				throw std::runtime_error("Cannot read source metadata: " + full.native_value());
			}
			row.mtime = mtime.value().native_value();
			row.bytes = bytes.value().native_value();
			result.files.append(row);
		}
		return result;
	}

	/**
	 * Use bounded runtime workers per directory frontier; publish only after every join succeeds.
	 */
	void fs_discover(const vector_string &roots, const vector_string &excluded,
		const string &suffix, uint32 jobs, file_records &output, scan_measurements &metrics)
	{
		const auto begin = dt::monotonic_micros().native_value();
		file_records candidate;
		scan_measurements measured;
		vector_t<directory_job> pending;
		for (int64 i = 0; i < roots.size(); ++i) {
			pending.append(directory_job{scalar_access::get(i).native_value(), string_t("")});
		}
		std::atomic<std::uint64_t> active{0};
		std::atomic<std::uint64_t> peak{0};
		while (!pending.empty().native_value())
		{
			vector_t<directory_job> next;
			(void)tasks::run_publish_unordered(pending, int_t<>(scalar_access::get(jobs).native_value()),
				[&](directory_job job) {
					const auto count = active.fetch_add(1) + 1;
					auto previous = peak.load();
					while (previous < count && !peak.compare_exchange_weak(previous, count)) {
					}
					try {
						auto result = read_directory(job, roots, excluded, suffix);
						active.fetch_sub(1);
						return result;
					}
					catch (...) {
						active.fetch_sub(1);
						throw;
					}
				},
				[&](const directory_result &result) {
					++measured.directories;
					measured.entries += result.entries;
					for (const auto &folder : result.directories.native_value()) {
						next.append(folder);
					}
					for (const auto &row : result.files.native_value()) {
						candidate.append(row);
					}
					return bool_t(true);
				});
			pending = std::move(next);
		}
		candidate.sort_paths();
		measured.files = uint64(candidate.size());
		measured.peak_workers = peak.load();
		measured.elapsed_us = dt::monotonic_micros().native_value() - begin;
		output.swap(candidate);
		metrics = measured;
	}
}
