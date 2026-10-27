// Full header first also checks compatibility with the new focused header.
#include "modules/tasks/tasks.hpp"
#include "modules/tasks/worker_pool.hpp"
#include <atomic>
#include <barrier>
#include <stdexcept>

int exercise_worker_pool()
{
	const auto created = scpp::tasks::default_worker_pool_created_workers().native_value();
	if (created != 4) { return 10; }
	scpp::vector_t<scpp::int_t<>> items;
	for (int i = 0; i < 16; ++i) { items.append(scpp::int_t<>(i)); }
	for (int round = 0; round < 2; ++round) {
		std::barrier ready(4);
		std::atomic<int> count{0};
		const auto results = scpp::tasks::run(items, scpp::int_t<>(4), [&](scpp::int_t<> item) {
			if (item.native_value() < 4) { ready.arrive_and_wait(); }
			count.fetch_add(1);
			return item;
		});
		if (count.load() != 16 || results.size() != 16) { return 11; }
		for (int i = 0; i < 16; ++i) {
			if (results.at(scpp::int_t<>(i)).native_value() != i) { return 12; }
		}
		if (scpp::tasks::default_worker_pool_created_workers().native_value() != created) { return 13; }
	}
	bool failed = false;
	try {
		(void)scpp::tasks::run(items, scpp::int_t<>(4), [](scpp::int_t<>) -> scpp::int_t<> {
			throw std::runtime_error("worker failure");
		});
	}
	catch (const std::runtime_error &) { failed = true; }
	if (!failed) { return 14; }
	const auto recovered = scpp::tasks::run(items, scpp::int_t<>(4), [](scpp::int_t<> value) { return value; });
	return recovered.size() == 16 ? 0 : 15;
}
