#pragma once
#include "scpp_compiler/file_records.hpp"
#include "scpp_compiler/source_reads.hpp"
#include "scpp_compiler/token_jobs.hpp"

namespace scpp::compiler
{
	using compiler_support::vector_string;
	class source_inventory final
	{
	public:
		void tokenize(uint32 jobs, boolean profile = false);
		const token_measurements &token_metrics() const;
		void load(const vector_string &roots, uint32 jobs, boolean profile = false);
		const read_measurements &reads() const;
		void scan(const vector_string &roots, uint32 jobs);
		int64 size() const;
		const file_record &at(int64 id) const;
		int64 find(int64 root, const string &path) const;
		source_status status(int64 id) const;
		// Explicit host notification and consumer acknowledgement use stable record IDs.
		void notify(int64 id);
		void acknowledge(int64 id);
		const scan_measurements &measurements() const;
		void swap(source_inventory &other) noexcept;
	private:
		file_records records_;
		scan_measurements measurements_;
		read_measurements reads_;
		token_measurements token_metrics_;
		uint64 revision_ = 0;
	};

	void validate_source_roots(vector_string &roots);
}
