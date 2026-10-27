#include "scpp_compiler/source_reads.hpp"
#include "scalar_access.hpp"
#include "string_state.hpp"
#include "modules/tasks/tasks.hpp"
#include "modules/datetime/datetime.hpp"
#include <atomic>

namespace scpp::compiler_support
{
	struct read_work
	{
		int64 id;
		string path;
		int64 mtime;
		int64 bytes;
		uint64 revision;
		token_buffer buffer;
		std::uint64_t read_ns = 0;
		std::uint64_t read_cpu_ns = 0;
	};

	struct source_reads::state {
		vector_t<shared_p<read_work>> work;
	};

	source_reads::source_reads() : state_(new state)
	{
	}

	source_reads::~source_reads() noexcept
	{
		delete this->state_;
	}

	void source_reads::add(int64 id, const string &path, int64 mtime, int64 bytes, uint64 revision)
	{
		this->state_->work.append(shared<read_work>(read_work{id, path, mtime, bytes, revision, {}}));
	}

	/**
	 * Run checked reads in private work records and join all workers before returning.
	 */
	read_measurements source_reads::execute(uint32 jobs, boolean profile)
	{
		if (jobs == 0) {
			throw std::invalid_argument("Source read jobs must be positive");
		}
		read_measurements result;
		tasks::detail::batch_profile batch_metrics;
		tasks::detail::batch_profile_scope scope(profile ? &batch_metrics : nullptr);
		const auto begin = dt::monotonic_micros().native_value();
		std::atomic<std::uint64_t> active{0};
		std::atomic<std::uint64_t> peak{0};
		if (!this->state_->work.empty().native_value())
		{
			(void)tasks::run(this->state_->work, int_t<>(scalar_access::get(jobs).native_value()),
				[&](const shared_p<read_work> &work)
				{
					const auto count = active.fetch_add(1) + 1;
					auto previous = peak.load();
					while (previous < count && !peak.compare_exchange_weak(previous, count)) {
					}
					try
					{
						const auto cpu = profile ? tasks::detail::profile_thread_cpu_ns() : 0;
						const auto start = profile ? tasks::detail::profile_now_ns() : 0;
						work->buffer = token_buffer::read(work->path, work->mtime, work->bytes);
						if (profile) {
							work->read_ns = tasks::detail::profile_now_ns() - start;
							work->read_cpu_ns = tasks::detail::profile_thread_cpu_ns() - cpu;
						}
						active.fetch_sub(1);
						return bool_t(true);
					}
					catch (...) {
						active.fetch_sub(1);
						throw;
					}
				});
		}
		result.files_read = this->state_->work.size();
		for (const auto &work : this->state_->work.native_value()) {
			result.bytes_read += work->buffer.byte_size();
			result.read_callback_ns += work->read_ns;
			result.read_callback_cpu_ns += work->read_cpu_ns;
		}
		result.startup_ns = batch_metrics.startup_ns;
		result.work_ns = batch_metrics.work_ns;
		result.worker_cpu_ns = batch_metrics.worker_cpu_ns;
		result.completion_tail_ns = batch_metrics.completion_tail_ns;
		result.join_tail_ns = batch_metrics.join_tail_ns;
		result.queue_claim_ns = batch_metrics.queue_claim_ns;
		result.completion_lock_wait_ns = batch_metrics.completion_lock_wait_ns;
		result.completion_lock_hold_ns = batch_metrics.completion_lock_hold_ns;
		result.gather_ns = batch_metrics.gather_ns;
		result.workers = batch_metrics.workers;
		result.peak_workers = peak.load();
		result.elapsed_us = dt::monotonic_micros().native_value() - begin;
		return result;
	}

	int64 source_reads::size() const
	{
		return static_cast<std::int64_t>(this->state_->work.size());
	}

	int64 source_reads::record_id(int64 index) const
	{
		return this->state_->work.at(scalar_access::get(index))->id;
	}

	uint64 source_reads::revision(int64 index) const
	{
		return this->state_->work.at(scalar_access::get(index))->revision;
	}

	token_buffer &source_reads::buffer(int64 index)
	{
		return this->state_->work.at(scalar_access::get(index))->buffer;
	}
}
