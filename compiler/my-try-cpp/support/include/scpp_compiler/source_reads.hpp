#pragma once
#include "scpp_compiler/token_buffer.hpp"

namespace scpp::compiler_support
{
	struct read_measurements final
	{
		uint64 files_read = 0;
		uint64 bytes_read = 0;
		uint64 reused = 0;
		uint64 peak_workers = 0;
		uint64 elapsed_us = 0;
		// Optional gated profiling: wall intervals and overlapping worker sums are distinct.
		uint64 startup_ns = 0;
		uint64 work_ns = 0;
		uint64 worker_cpu_ns = 0;
		uint64 read_callback_ns = 0;
		uint64 read_callback_cpu_ns = 0;
		uint64 completion_tail_ns = 0;
		uint64 join_tail_ns = 0;
		uint64 queue_claim_ns = 0;
		uint64 completion_lock_wait_ns = 0;
		uint64 completion_lock_hold_ns = 0;
		uint64 gather_ns = 0;
		uint64 request_ns = 0;
		uint64 publication_ns = 0;
		uint64 workers = 0;

	};

	// Private work owned by one load operation, never retained by Model.
	class source_reads final
	{
	public:
		source_reads();
		~source_reads() noexcept;
		source_reads(const source_reads &) = delete;
		source_reads &operator=(const source_reads &) = delete;
		void add(int64 id, const string &path, int64 mtime, int64 bytes, uint64 revision);
		read_measurements execute(uint32 jobs, boolean profile = false);
		int64 size() const;
		int64 record_id(int64 index) const;
		uint64 revision(int64 index) const;
		token_buffer &buffer(int64 index);
	private:
		struct state;
		state *state_;
	};
}

namespace scpp::compiler
{
	using compiler_support::read_measurements;
	using compiler_support::source_reads;
}
