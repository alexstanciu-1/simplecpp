#pragma once

// Contract: specs/compiler_storage.md. Source/converter bindings are a later slice.
#include "modules/compiler/detail/storage_common.hpp"
#include "scpp/vector_t.hpp"
#include <memory>

namespace scpp::compiler {

template<class T>
class Storage {
public:
	using record_handle = typename detail::storage_record<T>::handle;
	using key_type = std::int64_t;
private:
	struct state {
		scpp::vector_t<record_handle> slots;
		std::size_t live = 0;
	};
	std::shared_ptr<state> data_;

public:
	template<detail::storage_integer I = std::int64_t>
	explicit Storage(I capacity = 0) : data_(std::make_shared<state>()) { reserve(capacity); }
	Storage(const Storage &) = default;
	Storage &operator=(const Storage &) = default;

	void reserve(detail::storage_integer auto capacity) {
		data_->slots.reserve(detail::storage_capacity(capacity));
	}
	[[nodiscard]] std::size_t count() const noexcept { return data_->live; }
	[[nodiscard]] bool is_empty() const noexcept { return count() == 0; }
	[[nodiscard]] bool contains(detail::storage_integer auto position) const {
		return position >= 0 && static_cast<std::uintmax_t>(position) < data_->slots.size()
			&& static_cast<bool>(data_->slots.at(static_cast<std::size_t>(position)));
	}
	[[nodiscard]] record_handle read(detail::storage_integer auto position) const {
		if (!contains(position)) throw std::out_of_range("Storage position is absent");
		return data_->slots.at(static_cast<std::size_t>(position));
	}
	key_type append(record_handle record) {
		detail::storage_record<T>::validate(record);
		const auto position = detail::append_position(data_->slots.size());
		data_->slots.append(std::move(record));
		++data_->live;
		return position;
	}
	void replace(detail::storage_integer auto position, record_handle record) {
		detail::storage_record<T>::validate(record);
		if (!contains(position)) throw std::out_of_range("Storage position is absent");
		data_->slots.at(static_cast<std::size_t>(position)) = std::move(record);
	}
	void remove(detail::storage_integer auto position) {
		if (!contains(position)) throw std::out_of_range("Storage position is absent");
		unset(position);
	}
	void unset(detail::storage_integer auto position) {
		if (!contains(position)) return;
		data_->slots.at(static_cast<std::size_t>(position)) = record_handle{};
		--data_->live;
	}
	template<class F> void for_each(F &&visit) const {
		for (std::size_t i = 0; i < data_->slots.size(); ++i) {
			if (data_->slots.at(i)) visit(static_cast<key_type>(i), record_handle(data_->slots.at(i)));
		}
	}
};

} // namespace scpp::compiler
