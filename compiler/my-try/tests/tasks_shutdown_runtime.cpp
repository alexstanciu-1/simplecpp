#include "modules/tasks/tasks.hpp"
#include <atomic>
#include <cassert>
#include <iostream>
#include <latch>
#include <memory>
#include <stdexcept>

namespace tasks = scpp::tasks;
using integer = scpp::int_t<>;
int main()
{
 scpp::vector_t<integer> items;
 items.push_back(integer(1));
 std::atomic<int> completed{0};
 auto owned = std::make_shared<int>(42);
 std::weak_ptr<int> lifetime = owned;
 std::latch enter(1), release(1);
 (void) tasks::start(items, integer(1), [owned, &enter, &release, &completed](integer value) {
  enter.count_down(); release.wait(); assert(*owned == 42); ++completed; return value;
 });
 owned.reset(); enter.wait(); assert(!lifetime.expired()); release.count_down();
 tasks::shutdown();
 assert(completed == 1 && lifetime.expired());
 tasks::shutdown(); // Idempotent cleanup.

 // All outstanding work is drained even when an earlier batch fails.
 (void) tasks::start(items, integer(1), [](integer) -> integer { throw std::runtime_error("shutdown worker failure"); });
 (void) tasks::start(items, integer(1), [&completed](integer value) { ++completed; return value; });
 bool rejected = false;
 try { tasks::shutdown(); }
 catch (const std::exception& error) { rejected = std::string(error.what()).find("shutdown worker failure") != std::string::npos; }
 assert(rejected && completed == 2);
 tasks::shutdown();

 // A coordinator can create another batch while cleanup waits for it.
 (void) tasks::start(items, integer(1), [items, &completed](integer value) {
  (void) tasks::start(items, integer(1), [&completed](integer inner) { ++completed; return inner; });
  return value;
 });
 tasks::shutdown();
 assert(completed == 3);
 std::cout << "background retention, drain, failures and nested cleanup passed\n";
}
