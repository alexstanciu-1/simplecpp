#include "scpp/data_initializer.hpp"
#include <cassert>
#include <type_traits>

// Throwing copies prove both partially materialized input and factory-local output unwind.
struct tracked final {
	static inline int live = 0;
	static inline int copies_until_failure = -1;
	int value;
	explicit tracked(int value = 0) : value(value) { ++live; }
	tracked(const tracked &other) : value(other.value) {
		if (copies_until_failure == 0) throw std::runtime_error("copy failed");
		if (copies_until_failure > 0) --copies_until_failure;
		++live;
	}
	tracked(tracked &&other) noexcept : value(other.value) { ++live; }
	tracked &operator=(const tracked &) = default;
	tracked &operator=(tracked &&) = default;
	~tracked() { --live; }
};

int main() {
	using namespace scpp;
	static_assert(std::is_same_v<decltype(std::declval<initializer_list<tracked>>().at(int_t<>{0LL})), tracked>);
	static_assert(std::is_const_v<decltype(initializer_entry<int_t<>>{}.key)>);
	static_assert(!std::is_copy_constructible_v<initializer_buffer<tracked>>);
	initializer_list<tracked> empty;
	assert(empty.size() == 0 && empty.count().native_value() == 0);
	bool caught = false;
	try { (void)empty.at(int_t<>{0LL}); } catch (const std::out_of_range &) { caught = true; }
	assert(caught);
	for (auto value : empty) { (void)value; assert(false); }
	for (std::size_t count : {std::size_t{3}, std::size_t{80}}) {
		{
			initializer_buffer<tracked> buffer(count);
			caught = false;
			try { (void)buffer.view(); } catch (const std::logic_error &) { caught = true; }
			assert(caught);
			for (std::size_t i = 0; i < count; ++i) buffer.append(tracked(static_cast<int>(i)));
			assert(tracked::live == static_cast<int>(count));
			auto copy = buffer.view().at(int_t<>{0LL});
			copy.value = 999;
			assert(buffer.view().at(int_t<>{0LL}).value == 0);
			caught = false;
			try { (void)buffer.view().at(int_t<>{-1LL}); } catch (const std::out_of_range &) { caught = true; }
			assert(caught);
			tracked::copies_until_failure = 3;
			caught = false;
			try { (void)data_initializer<vector_t<tracked>>::from_list(buffer.view()); }
			catch (const std::runtime_error &) { caught = true; }
			assert(caught && tracked::live == static_cast<int>(count) + 1);
			tracked::copies_until_failure = -1;
		}
		assert(tracked::live == 0);
		caught = false;
		try {
			initializer_buffer<tracked> buffer(count);
			buffer.append(tracked(1));
			tracked source(2);
			tracked::copies_until_failure = 0;
			buffer.append(source);
		} catch (const std::runtime_error &) { caught = true; }
		tracked::copies_until_failure = -1;
		assert(caught && tracked::live == 0);
	}
}
