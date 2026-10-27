#pragma once
#include "scpp_compiler/vector_string.hpp"
#include "scpp_compiler/string.hpp"
#include "scpp_compiler/token_sequence.hpp"

namespace scpp::compiler_support
{
	enum class source_status
	{
		unseen, added, changed, unchanged, deleted
	};

	// Concrete storage payload; compiler workers own status transitions.
	struct file_record final
	{
		int64 root = 0;
		string path;
		int64 mtime = 0;
		int64 bytes = 0;
		source_status status = source_status::unseen;
		boolean seen = false;
		uint64 revision = 0;
		uint64 loaded_revision = 0;
		token_buffer buffer;
		uint64 tokenized_revision = 0;
		token_sequence tokens;
	};

	class file_records final
	{
	public:
		file_records();
		~file_records() noexcept;
		file_records(const file_records &) = delete;
		file_records &operator=(const file_records &) = delete;
		int64 size() const;
		const file_record &at(int64 index) const;
		file_record &at(int64 index);
		int64 find(int64 root, const string &path) const;
		void append(const file_record &record);
		// Allocate all additions privately, then swap storage; existing records retain addresses.
		void append_missing(file_records &observations);
		int64 mapped_index(int64 observation) const;
		void swap(file_records &other) noexcept;
		void sort_paths();
	private:
		struct state;
		state *state_;
	};

	struct scan_measurements final
	{
		uint64 directories = 0;
		uint64 entries = 0;
		uint64 files = 0;
		uint64 peak_workers = 0;
		uint64 elapsed_us = 0;
	};

	// Inputs are canonical, disjoint roots. No file contents are read.
	void fs_discover(const vector_string &roots, const vector_string &excluded,
		const string &suffix, uint32 jobs, file_records &output, scan_measurements &metrics);
	boolean fs_path_contains(const string &root, const string &path);
	boolean fs_is_link(const string &path);
}

namespace scpp::compiler
{
	using compiler_support::source_status;
	using compiler_support::file_record;
	using compiler_support::file_records;
	using compiler_support::scan_measurements;
	using compiler_support::fs_discover;
	using compiler_support::fs_path_contains;
	using compiler_support::fs_is_link;
}
