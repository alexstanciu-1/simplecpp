#include "sources/inventory.hpp"
#include "scpp_compiler/filesystem.hpp"
#include "scpp_compiler/host.hpp"
#include "scpp_compiler/textbuilder.hpp"
#include "scpp_compiler/datetime.hpp"

namespace scpp::compiler
{
	/**
	 * Canonicalize source modules and reject duplicate or overlapping ownership.
	 */
	void validate_source_roots(vector_string &roots)
	{
		vector_string canonical;
		for (int64 i = 0; i < roots.size(); ++i)
		{
			const auto root = roots.read(i);
			if (fs_is_link(root)) {
				fail(string("Source root must not be a symlink"));
			}
			const auto resolved = fs_canonical_directory(root);
			for (int64 j = 0; j < canonical.size(); ++j) {
				const auto other = canonical.read(j);
				if (fs_path_contains(other, resolved) || fs_path_contains(resolved, other)) {
					fail(string("Source roots must be disjoint"));
				}
			}
			canonical.append(resolved);
		}
		roots.swap(canonical);
	}

	/**
	 * Scan privately; prepare allocations and lookups before changing retained records.
	 * Pending flags survive equal-metadata scans until a consumer acknowledges them.
	 */
	void source_inventory::scan(const vector_string &roots, uint32 jobs)
	{
		if (this->revision_ == 18446744073709551615ULL) {
			fail(string("Source revision exhausted"));
		}
		const auto revision = this->revision_ + 1;
		vector_string excluded;
		excluded.append(string(".git"));
		excluded.append(string(".hg"));
		excluded.append(string(".svn"));
		excluded.append(string(".prism"));
		file_records observations;
		scan_measurements metrics;
		fs_discover(roots, excluded, string(".phs"), jobs, observations, metrics);
		this->records_.append_missing(observations);

		// From this point all IDs are validated and no operation allocates.
		for (int64 i = 0; i < this->records_.size(); ++i) {
			this->records_.at(i).seen = false;
		}
		for (int64 i = 0; i < observations.size(); ++i)
		{
			const auto &observed = observations.at(i);
			auto &record = this->records_.at(observations.mapped_index(i));
			if (record.status == source_status::unseen || record.status == source_status::deleted) {
				record.status = source_status::added;
				record.revision = revision;
			}
			else if (record.mtime != observed.mtime || record.bytes != observed.bytes) {
				record.revision = revision;
				if (record.status == source_status::unchanged) {
					record.status = source_status::changed;
				}
			}
			record.mtime = observed.mtime;
			record.bytes = observed.bytes;
			record.seen = true;
		}
		for (int64 i = 0; i < this->records_.size(); ++i) {
			auto &record = this->records_.at(i);
			if (!record.seen && record.status != source_status::deleted) {
				record.revision = revision;
				record.status = source_status::deleted;
			}
		}
		this->measurements_ = metrics;
		this->revision_ = revision;
	}

	/**
	 * Prepare checked reads privately, then publish a complete batch without allocating.
	 * Reading does not acknowledge changes: downstream consumers own that transition.
	 */
	void source_inventory::load(const vector_string &roots, uint32 jobs, boolean profile)
	{
		const auto request_start = profile ? monotonic_micros() : uint64(0);
		read_measurements metrics;
		int64 pending = 0;
		for (int64 i = 0; i < this->records_.size(); ++i)
		{
			const auto &record = this->records_.at(i);
			if (record.status == source_status::deleted) {
				continue;
			}
			if (record.buffer.present() && record.loaded_revision == record.revision) {
				++metrics.reused;
			}
			else {
				++pending;
			}
		}
		if (pending == 0) {
			this->reads_ = metrics;
			return;
		}

		source_reads batch;
		for (int64 i = 0; i < this->records_.size(); ++i)
		{
			const auto &record = this->records_.at(i);
			if (record.status == source_status::deleted ||
				(record.buffer.present() && record.loaded_revision == record.revision)) {
				continue;
			}
			textbuilder path;
			path.append(roots.read(record.root));
			path.append("/");
			path.append(record.path);
			batch.add(i, path.finish(), record.mtime, record.bytes, record.revision);
		}
		const auto request_ns = profile ? (monotonic_micros() - request_start) * 1000 : uint64(0);
		auto loaded = batch.execute(jobs, profile);
		loaded.request_ns = request_ns;
		const auto publication_start = profile ? monotonic_micros() : uint64(0);
		loaded.reused = metrics.reused;
		for (int64 i = 0; i < batch.size(); ++i) {
			auto &record = this->records_.at(batch.record_id(i));
			record.buffer.swap(batch.buffer(i));
			record.loaded_revision = batch.revision(i);
		}
		loaded.publication_ns = profile ? (monotonic_micros() - publication_start) * 1000 : uint64(0);
		this->reads_ = loaded;
	}

	const read_measurements &source_inventory::reads() const
	{
		return this->reads_;
	}

	int64 source_inventory::size() const
	{
		return this->records_.size();
	}

	const file_record &source_inventory::at(int64 id) const
	{
		return this->records_.at(id);
	}

	int64 source_inventory::find(int64 root, const string &path) const
	{
		return this->records_.find(root, path);
	}

	source_status source_inventory::status(int64 id) const
	{
		return this->records_.at(id).status;
	}

	/**
	 * Invalidate a live source even when a previous change remains pending.
	 */
	void source_inventory::notify(int64 id)
	{
		auto &record = this->records_.at(id);
		if (record.status == source_status::deleted) {
			return;
		}
		if (this->revision_ == 18446744073709551615ULL) {
			fail(string("Source revision exhausted"));
		}
		record.revision = ++this->revision_;
		if (record.status == source_status::unchanged) {
			record.status = source_status::changed;
		}
	}

	/**
	 * Clear only a revision whose bytes were successfully loaded.
	 */
	void source_inventory::acknowledge(int64 id)
	{
		auto &record = this->records_.at(id);
		if (record.status == source_status::added || record.status == source_status::changed) {
			if (!record.buffer.present() || record.loaded_revision != record.revision) {
				fail(string("Cannot acknowledge an unread source revision"));
			}
			record.status = source_status::unchanged;
		}
	}

	const scan_measurements &source_inventory::measurements() const
	{
		return this->measurements_;
	}

	/**
	 * Exchange the complete inventory and revision state without allocation.
	 */
	void source_inventory::swap(source_inventory &other) noexcept
	{
		this->records_.swap(other.records_);
		const auto previous = this->measurements_;
		this->measurements_ = other.measurements_;
		other.measurements_ = previous;
		const auto tokens = this->token_metrics_;
		this->token_metrics_ = other.token_metrics_;
		other.token_metrics_ = tokens;
		const auto reads = this->reads_;
		this->reads_ = other.reads_;
		other.reads_ = reads;
		const auto revision = this->revision_;
		this->revision_ = other.revision_;
		other.revision_ = revision;
	}
}
