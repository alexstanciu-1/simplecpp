#pragma once

#include "scpp/shared_p.hpp"
#include "scpp/string_t.hpp"
#include "scpp/vector_t.hpp"
#include <memory>
#include <string>
#include <unordered_map>
#include <vector>
#include <stdexcept>

namespace scpp::compiler {

// Copies alias membership; queries copy membership while retaining record identity.
template<class T>
class Key_Storage_List {
	using handle = scpp::shared_p<T>;
	struct state {
		std::vector<handle> ordered;
		std::unordered_map<std::string, std::vector<std::size_t>> positions;
	};
	std::shared_ptr<state> data_ = std::make_shared<state>();
public:
	void add(const scpp::string_t &key, handle record) {
		if (!record) throw std::invalid_argument("Key_Storage_List requires a non-null record");
		auto &bucket = data_->positions[std::string(key.native_value())];
		bucket.push_back(data_->ordered.size());
		try {
			data_->ordered.push_back(std::move(record));
		} catch (...) {
			bucket.pop_back();
			throw;
		}
	}

	scpp::vector_t<handle> named(const scpp::string_t &key) const {
		scpp::vector_t<handle> result;
		auto found = data_->positions.find(std::string(key.native_value()));
		if (found != data_->positions.end()) {
			for (auto position : found->second) result.push_back(data_->ordered[position]);
		}
		return result;
	}

	scpp::vector_t<handle> items() const {
		scpp::vector_t<handle> result;
		for (const auto &record : data_->ordered) result.push_back(record);
		return result;
	}

	bool is_empty() const { return data_->ordered.empty(); }
};

} // namespace scpp::compiler
