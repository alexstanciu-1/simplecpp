#include "token_sequence.hpp"
#include <algorithm>
#include <cstdint>
#include <memory>
#include <vector>
#include <type_traits>
#include <stdexcept>
#include <utility>

namespace scpp::compiler_support
{
	struct token_data {
		token_buffer source;
		std::vector<token> rows;
	};

	struct token_sequence::state {
		std::shared_ptr<const token_data> data;
	};

	struct token_builder::state {
		token_data data;
		bool finished = false;
	};

	token_sequence::token_sequence() noexcept = default;

	token_sequence::token_sequence(const token_sequence &other)
		: state_(other.state_ ? new state(*other.state_) : nullptr)
	{
	}

	token_sequence::token_sequence(token_sequence &&other) noexcept
	{
		this->swap(other);
	}

	token_sequence &token_sequence::operator=(const token_sequence &other)
	{
		token_sequence candidate(other);
		this->swap(candidate);
		return *this;
	}

	token_sequence &token_sequence::operator=(token_sequence &&other) noexcept
	{
		if (this != &other) {
			token_sequence candidate(std::move(other));
			this->swap(candidate);
		}
		return *this;
	}

	token_sequence::~token_sequence() noexcept
	{
		delete this->state_;
	}

	bool_t token_sequence::present() const noexcept
	{
		return bool_t{this->state_ != nullptr};
	}

	int64 token_sequence::size() const
	{
		return int64{this->state_ ? this->state_->data->rows.size() : std::size_t{0}};
	}

	uint64 token_sequence::capacity_bytes() const
	{
		return uint64{this->state_ ? this->state_->data->rows.capacity() * sizeof(token) : std::size_t{0}};
	}

	token token_sequence::at(int64 index) const
	{
		if (!this->state_ || index.native_value() < 0 ||
			static_cast<std::uint64_t>(index.native_value()) >= this->state_->data->rows.size()) {
			throw std::out_of_range("No token generation");
		}
		return this->state_->data->rows.at(static_cast<std::size_t>(index.native_value()));
	}

	token_buffer token_sequence::source() const
	{
		if (!this->state_) {
			throw std::logic_error("No token generation source");
		}
		return this->state_->data->source;
	}

	bool_t token_sequence::same_storage(const token_sequence &other) const noexcept
	{
		return bool_t{this->state_ && other.state_ && this->state_->data == other.state_->data};
	}

	void token_sequence::swap(token_sequence &other) noexcept
	{
		std::swap(this->state_, other.state_);
	}

	/**
	 * Reserve from a previous generation when available, bounding hints by source size.
	 */
	token_builder::token_builder(const token_buffer &source, int64 previous_count) : state_(nullptr)
	{
		const auto bytes = source.byte_size().native_value();
		if (!source.present() || bytes > 0xffffffffULL || previous_count.native_value() < 0) {
			throw std::out_of_range("Source token buffer exceeds uint32 capacity or invalid input");
		}
		auto candidate = std::make_unique<state>();
		candidate->data.source = source;
		const auto hint = static_cast<std::uint64_t>(previous_count.native_value());
		candidate->data.rows.reserve(std::min(bytes, hint ? hint : std::uint64_t(256)));
		this->state_ = candidate.release();
	}

	token_builder::~token_builder() noexcept
	{
		delete this->state_;
	}

	/**
	 * Append only ordered, nonoverlapping spans contained in the retained source.
	 */
	void token_builder::append(uint64 offset, uint64 length, token_kind kind)
	{
		if (this->state_->finished) {
			throw std::logic_error("Token builder already finished");
		}
		const auto start = offset.native_value();
		const auto size = length.native_value();
		const auto bytes = this->state_->data.source.byte_size().native_value();
		if (start > bytes || size > bytes - start) {
			throw std::out_of_range("Token outside source");
		}
		auto &rows = this->state_->data.rows;
		if (!rows.empty()) {
			const auto &last = rows.back();
			if (start < static_cast<std::uint64_t>(last.offset().native_value()) + last.length().native_value()) {
				throw std::out_of_range("Overlapping token spans");
			}
		}
		rows.emplace_back(offset, length, kind);
	}

	/**
	 * Transfer contiguous storage without copying source bytes or token payloads.
	 */
	token_sequence token_builder::finish()
	{
		if (this->state_->finished) {
			throw std::logic_error("Token builder already finished");
		}
		token_sequence result;
		// Allocate the handle before consuming the builder, so failure preserves its data.
		auto handle = std::make_unique<token_sequence::state>();
		static_assert(std::is_nothrow_move_constructible_v<token_data>);
		handle->data = std::make_shared<const token_data>(std::move(this->state_->data));
		result.state_ = handle.release();
		this->state_->finished = true;
		return result;
	}
}
