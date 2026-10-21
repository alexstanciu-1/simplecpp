#pragma once
#include "modules/tasks/tasks.hpp"
namespace task_submission_probe {
// Test only: establish the existing worker context without admitting other APIs.
template <typename Callback> void work_context(Callback callback) {
 std::mutex mutex;
 scpp::tasks::detail::publication_context context(&mutex);
 callback();
}
}
