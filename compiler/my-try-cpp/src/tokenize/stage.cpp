#include "sources/inventory.hpp"
#include "tokenize/scanner.hpp"
#include "scpp_compiler/datetime.hpp"
#include "scpp_compiler/host.hpp"

namespace scpp::compiler
{
	/**
	 * Scan only loaded current revisions; publish the complete successful batch.
	 * Tokenization never acknowledges changes required by later compiler stages.
	 */
	void source_inventory::tokenize(uint32 jobs, boolean profile)
	{
		const auto begin = monotonic_micros();
		token_measurements metrics;
		int64 pending = 0;
		for (int64 i = 0; i < this->records_.size(); ++i)
		{
			const auto &record = this->records_.at(i);
			if (record.status == source_status::deleted) {
				continue;
			}
			if (!record.buffer.present() || record.loaded_revision != record.revision) {
				fail(string("Cannot tokenize an unread source revision"));
			}
			if (record.tokens.present() && record.tokenized_revision == record.loaded_revision) {
				++metrics.reused;
			}
			else {
				++pending;
			}
		}
		if (pending == 0) {
			this->token_metrics_ = metrics;
			return;
		}
		token_jobs batch;
		for (int64 i = 0; i < this->records_.size(); ++i)
		{
			const auto &record = this->records_.at(i);
			if (record.status == source_status::deleted ||
				(record.tokens.present() && record.tokenized_revision == record.loaded_revision)) {
				continue;
			}
			batch.add(i, record.path, record.buffer, record.tokens.size(), record.loaded_revision);
		}
		const auto prepared = monotonic_micros();
		auto completed = batch.execute(jobs, scan_tokens, profile);
		completed.request_ns = (prepared - begin) * 1000;
		completed.reused = metrics.reused;
		const auto publication = monotonic_micros();
		for (int64 i = 0; i < batch.size(); ++i) {
			auto &record = this->records_.at(batch.record_id(i));
			record.tokens.swap(batch.tokens(i));
			record.tokenized_revision = batch.revision(i);
		}
		completed.publication_ns = (monotonic_micros() - publication) * 1000;
		this->token_metrics_ = completed;
	}

	const token_measurements &source_inventory::token_metrics() const
	{
		return this->token_metrics_;
	}
}
