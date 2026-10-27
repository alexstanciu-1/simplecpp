#pragma once
#include "scpp_compiler/token_sequence.hpp"

namespace scpp::compiler_support
{
	struct token_measurements final
	{
		uint64 files_tokenized = 0;
		uint64 bytes_scanned = 0;
		uint64 token_count = 0;
		uint64 capacity_bytes = 0;
		uint64 reused = 0;
		uint64 peak_workers = 0;
		uint64 elapsed_us = 0;
		// Optional gated profiling: wall intervals and overlapping worker sums are distinct.
		uint64 startup_ns = 0;
		uint64 work_ns = 0;
		uint64 worker_cpu_ns = 0;
		uint64 scan_callback_ns = 0;
		uint64 scan_callback_cpu_ns = 0;
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

	using token_scan_function = token_sequence (*)(const token_buffer &, int64);

	class token_jobs final
	{
	public:
		token_jobs();
		~token_jobs() noexcept;
		token_jobs(const token_jobs &) = delete;
		token_jobs &operator=(const token_jobs &) = delete;
		void add(int64 id, const string &path, const token_buffer &source, int64 hint, uint64 revision);
		// Rejects null scanners before dispatch, even when the batch is empty.
		token_measurements execute(uint32 jobs, token_scan_function scanner, boolean profile = false);
		int64 size() const;
		int64 record_id(int64 index) const;
		uint64 revision(int64 index) const;
		token_sequence &tokens(int64 index);
	private:
		struct state;
		state *state_;
	};
}

namespace scpp::compiler
{
	using compiler_support::token_jobs;
	using compiler_support::token_measurements;
}
