#include "structure_kernel/frontend_model_kernel_counters.hpp"
#include "__counter/create.hpp"
#include "__counter/update_call_count.hpp"
#include <chrono>
#include <iostream>
#include <string>
using namespace scpp;
int main(int argc, char** argv) {
 const bool boundary = argc > 1 && std::string(argv[1]) == "boundary";
 auto value = __latency_counter_create();
 auto alias = value;
 const auto start = std::chrono::steady_clock::now();
 for (int i=0; i<5000000; ++i) {
  if (boundary) __latency_counter_update_call_count(alias) = __latency_counter_update_call_count(value) + static_cast<int_t<>>(1);
  else alias->update_call_count = value->update_call_count + static_cast<int_t<>>(1);
 }
 const double elapsed=std::chrono::duration<double>(std::chrono::steady_clock::now()-start).count();
 if (value->update_call_count.native_value()!=5000000) return 2;
 std::cout << elapsed << "\n";
}
