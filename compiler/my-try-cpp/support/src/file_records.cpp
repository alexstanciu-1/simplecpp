#include "scpp_compiler/file_records.hpp"
#include "scalar_access.hpp"
#include "scpp/vector_t.hpp"
#include "string_state.hpp"
#include "scpp/hash_t.hpp"
#include "scpp/memory.hpp"
#include <algorithm>

namespace scpp::compiler_support
{
	struct file_records::state
	{
		vector_t<shared_p<file_record>> rows;
		hash_t<int_t<>, string_t> index;
		vector_t<int_t<>> mapping;
	};

	static string_t identity(int64 root, const string &path)
	{
		return string_t(std::to_string(scalar_access::get(root).native_value()) + ":" +
			runtime_access::get(path).native_value());
	}

	file_records::file_records() : state_(new state)
	{
	}

	file_records::~file_records() noexcept
	{
		delete this->state_;
	}

	int64 file_records::size() const
	{
		return static_cast<std::int64_t>(this->state_->rows.size());
	}

	const file_record &file_records::at(int64 index) const
	{
		return *this->state_->rows.at(scalar_access::get(index));
	}

	file_record &file_records::at(int64 index)
	{
		return *this->state_->rows.at(scalar_access::get(index));
	}

	int64 file_records::find(int64 root, const string &path) const
	{
		const auto found = this->state_->index.find(identity(root, path));
		return found.has_value() ? found.value().native_value() : -1;
	}

	/**
	 * Append a privately owned row. This method is used only on unpublished storage.
	 */
	void file_records::append(const file_record &record)
	{
		if (this->find(record.root, record.path) >= 0) {
			throw std::logic_error("Duplicate discovered file");
		}
		this->state_->index.set(identity(record.root, record.path), int_t<>(this->state_->rows.size()));
		this->state_->rows.append(shared<file_record>(record));
	}

	/**
	 * Copy handles only when membership grows; failures never modify the live store.
	 */
	void file_records::append_missing(file_records &observations)
	{
		bool missing = false;
		auto &mapping = observations.state_->mapping;
		mapping = {};
		mapping.reserve(observations.state_->rows.size());
		for (int64 i = 0; i < observations.size(); ++i) {
			const auto &row = observations.at(i);
			const auto index = this->find(row.root, row.path);
			mapping.append(scalar_access::get(index));
			missing = missing || index < 0;
		}
		if (!missing) {
			return;
		}
		file_records candidate;
		*candidate.state_ = *this->state_;
		for (int64 i = 0; i < observations.size(); ++i) {
			auto &index = mapping.at(scalar_access::get(i));
			if (index.native_value() < 0) {
				index = int_t<>(candidate.state_->rows.size());
				candidate.append(observations.at(i));
			}
		}
		this->swap(candidate);
	}

	int64 file_records::mapped_index(int64 observation) const
	{
		return this->state_->mapping.at(scalar_access::get(observation)).native_value();
	}

	void file_records::swap(file_records &other) noexcept
	{
		std::swap(this->state_, other.state_);
	}

	/**
	 * Sort a private observation set, then rebuild its relative-path index.
	 */
	void file_records::sort_paths()
	{
		auto &rows = this->state_->rows.native_value();
		std::sort(rows.begin(), rows.end(), [](const auto &a, const auto &b) {
			if (a->root != b->root) {
				return static_cast<bool>(a->root < b->root);
			}
			return runtime_access::get(a->path).native_value() < runtime_access::get(b->path).native_value();
		});
		this->state_->index = {};
		for (std::size_t i = 0; i < rows.size(); ++i) {
			this->state_->index.set(identity(rows[i]->root, rows[i]->path), int_t<>(i));
		}
	}
}
