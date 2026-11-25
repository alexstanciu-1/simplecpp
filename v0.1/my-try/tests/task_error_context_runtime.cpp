#include "modules/tasks/tasks.hpp"
#include <atomic>
#include <iostream>
#include <thread>

int main() {
 using namespace scpp;
 const auto coordinator = std::this_thread::get_id();
 vector_t<int_t<>> input;
 input.append(int_t<>(1));
 std::atomic<bool> vector_coordinator{false};
 auto failing = [](int_t<>) -> int_t<> { throw std::runtime_error("probe failure"); };
 auto vector_result = tasks::run(input, int_t<>(2), failing, null, null,
  [&](int_t<>, shared_p<tasks::error>) -> int_t<> {
   vector_coordinator = std::this_thread::get_id() == coordinator;
   return int_t<>(42);
  }, int_t<>(0));
 hash_t<int_t<>, string_t> keyed;
 keyed.set(string_t("key"), int_t<>(1));
 std::atomic<bool> hash_coordinator{false};
 auto hash_result = tasks::run(keyed, int_t<>(2), failing, null, null,
  [&](int_t<>, shared_p<tasks::error>) -> int_t<> {
   hash_coordinator = std::this_thread::get_id() == coordinator;
   return int_t<>(43);
  }, int_t<>(0));
 std::atomic<int> omission_calls{0};
 auto published = tasks::run_publish(input, int_t<>(2), failing,
  [](vector_t<int_t<>>) {},
  [&](int_t<>, shared_p<tasks::error>) { ++omission_calls; }, int_t<>(0));
 std::cout << "{\"vector_error_on_coordinator\":" << (vector_coordinator ? "true" : "false")
  << ",\"hash_error_on_coordinator\":" << (hash_coordinator ? "true" : "false")
  << ",\"vector_replacement\":" << vector_result.at(0).native_value()
  << ",\"hash_replacement\":" << hash_result[string_t("key")].native_value()
  << ",\"publish_void_handler_calls\":" << omission_calls.load()
  << ",\"published\":" << published.native_value() << "}\n";
 tasks::shutdown();
}
