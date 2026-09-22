#pragma once

#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>

namespace scpp {

class file_lock_handle;
class process_handle;
class process_output;
shared_p<file_lock_handle> keep_lock(shared_p<file_lock_handle> handle);

shared_p<process_handle> keep_process(shared_p<process_handle> handle);

shared_p<process_output> keep_output(shared_p<process_output> output);

int __scpp_main();

}

