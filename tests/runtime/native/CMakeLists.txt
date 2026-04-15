if(NOT TARGET scpp_runtime)
	message(FATAL_ERROR "tests/runtime/native/CMakeLists.txt expects the scpp_runtime target to exist")
endif()

# Defines the shared helper type used by multiple runtime smoke tests.
add_library(scpp_runtime_test_support INTERFACE)
target_include_directories(scpp_runtime_test_support INTERFACE ${CMAKE_CURRENT_SOURCE_DIR})

# Core wrapper smoke coverage.
add_executable(test_core test_core.cpp)
target_link_libraries(test_core PRIVATE scpp_lang_php scpp_runtime scpp_runtime_test_support)

# nullable<T> smoke coverage.
add_executable(test_nullable test_nullable.cpp)
target_link_libraries(test_nullable PRIVATE scpp_runtime scpp_runtime_test_support)

# Smart-pointer wrapper smoke coverage.
add_executable(test_pointers test_pointers.cpp)
target_link_libraries(test_pointers PRIVATE scpp_runtime scpp_runtime_test_support)

# Sentinel comparison smoke coverage.
add_executable(test_sentinels test_sentinels.cpp)
target_link_libraries(test_sentinels PRIVATE scpp_runtime scpp_runtime_test_support)

# Registers the executables with CTest for repeatable smoke execution.
enable_testing()
add_test(NAME scpp_test_core COMMAND test_core)
add_test(NAME scpp_test_nullable COMMAND test_nullable)

# false-able / error-able wrapper smoke coverage.
add_executable(test_or_wrappers test_or_wrappers.cpp)
target_link_libraries(test_or_wrappers PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_or_wrappers COMMAND test_or_wrappers)
add_test(NAME scpp_test_pointers COMMAND test_pointers)
add_test(NAME scpp_test_sentinels COMMAND test_sentinels)

# Inline-value and native-reference smoke coverage.
add_executable(test_value_ref test_value_ref.cpp)
target_link_libraries(test_value_ref PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_value_ref COMMAND test_value_ref)

# Config-contract coverage for stable wrapper semantics.
add_executable(test_contract test_contract.cpp)
target_link_libraries(test_contract PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_contract COMMAND test_contract)

# Closure smoke coverage.
add_executable(test_closure test_closure.cpp)
target_link_libraries(test_closure PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_closure COMMAND test_closure)

# hash_t smoke coverage.
add_executable(test_hash_t test_hash_t.cpp)
target_link_libraries(test_hash_t PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_hash_t COMMAND test_hash_t)

# mixed_t dynamic dispatch coverage.
add_executable(test_mixed_t_dynamic test_mixed_t_dynamic.cpp)
target_link_libraries(test_mixed_t_dynamic PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_mixed_t_dynamic COMMAND test_mixed_t_dynamic)

# Array semantics conformance coverage for the reduced Prism++ subset.
add_executable(test_array_semantics test_array_semantics.cpp)
target_link_libraries(test_array_semantics PRIVATE scpp_lang_php scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_array_semantics COMMAND test_array_semantics)

# PHP string builtin wrapper coverage.
add_executable(test_php_string test_php_string.cpp)
target_link_libraries(test_php_string PRIVATE scpp_lang_php scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_php_string COMMAND test_php_string)


# PHP filesystem and stdio builtin coverage.
add_executable(test_php_filesystem test_php_filesystem.cpp)
target_link_libraries(test_php_filesystem PRIVATE scpp_lang_php scpp_filesystem scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_php_filesystem COMMAND test_php_filesystem)

# PHP JSON builtin coverage.
add_executable(test_json test_json.cpp)
target_link_libraries(test_json PRIVATE scpp_lang_php scpp_json scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_json COMMAND test_json)

# mysqli connect/close smoke coverage for the first vertical slice.
# Disabled by default because many local/sandbox builds do not have a runnable MariaDB/MySQL test target.
option(SCPP_ENABLE_MYSQLI_TESTS "Build and register mysqli runtime smoke tests" OFF)
if(SCPP_ENABLE_MYSQLI_TESTS)
	add_executable(test_mysqli_connect test_mysqli_connect.cpp)
	target_link_libraries(test_mysqli_connect PRIVATE scpp_lang_php scpp_runtime scpp_runtime_test_support)
	add_test(NAME scpp_test_mysqli_connect COMMAND test_mysqli_connect)
endif()
