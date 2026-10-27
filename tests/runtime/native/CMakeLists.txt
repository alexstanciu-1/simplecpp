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
find_package(Threads REQUIRED)
add_executable(test_concurrent_value_iteration test_concurrent_value_iteration.cpp)
target_link_libraries(test_concurrent_value_iteration PRIVATE scpp_runtime Threads::Threads)
target_compile_features(test_concurrent_value_iteration PRIVATE cxx_std_20)
add_test(NAME scpp_test_concurrent_value_iteration COMMAND test_concurrent_value_iteration)
set_tests_properties(scpp_test_concurrent_value_iteration PROPERTIES TIMEOUT 60)
if(CMAKE_SYSTEM_NAME STREQUAL "Linux")
	# Exercise the lock implementation without unrelated filesystem/JSON linkage.
	add_executable(test_file_locks test_file_locks.cpp
		${CMAKE_CURRENT_SOURCE_DIR}/../../../runtime/include/modules/filesystem/file_lock.cpp)
	target_include_directories(test_file_locks PRIVATE ${CMAKE_CURRENT_SOURCE_DIR}/../../../runtime/include)
	target_compile_features(test_file_locks PRIVATE cxx_std_23)
	add_test(NAME scpp_test_file_locks COMMAND test_file_locks)
	set_tests_properties(scpp_test_file_locks PROPERTIES TIMEOUT 30)
endif()
if(SCPP_WITH_PROCESS AND CMAKE_SYSTEM_NAME STREQUAL "Linux")
	add_executable(test_process test_process.cpp)
	target_link_libraries(test_process PRIVATE scpp_process scpp_runtime_test_support)
	add_test(NAME scpp_test_process COMMAND test_process)
	set_tests_properties(scpp_test_process PROPERTIES TIMEOUT 30)
endif()
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

# Async core scheduler/coroutine smoke coverage.
add_executable(test_async_core test_async_core.cpp)
target_link_libraries(test_async_core PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_async_core COMMAND test_async_core)

# hash_t smoke coverage.
add_executable(test_hash_t test_hash_t.cpp)
target_link_libraries(test_hash_t PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_hash_t COMMAND test_hash_t)

add_executable(test_hash_churn test_hash_churn.cpp)
target_link_libraries(test_hash_churn PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_hash_churn COMMAND test_hash_churn)

# Tagged dynamic storage foundation; public alias migration is still pending.
add_executable(test_dynamic_value test_dynamic_value.cpp)
target_link_libraries(test_dynamic_value PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_dynamic_value COMMAND test_dynamic_value)

# Value-independent packed table engine shared by boxed-value families.
add_executable(test_packed_table_storage test_packed_table_storage.cpp)
target_link_libraries(test_packed_table_storage PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_packed_table_storage COMMAND test_packed_table_storage)

# row_arena_t stable-id storage coverage.
add_executable(test_row_arena_t test_row_arena_t.cpp)
target_link_libraries(test_row_arena_t PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_row_arena_t COMMAND test_row_arena_t)

# bitset_t compact dirty/visited storage coverage.
add_executable(test_bitset_t test_bitset_t.cpp)
target_link_libraries(test_bitset_t PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_bitset_t COMMAND test_bitset_t)

# work_queue_t ring-buffer queue coverage.
add_executable(test_work_queue_t test_work_queue_t.cpp)
target_link_libraries(test_work_queue_t PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_work_queue_t COMMAND test_work_queue_t)

# Little-endian binary codec coverage for future compiler artifacts.
add_executable(test_binary_codec test_binary_codec.cpp)
target_link_libraries(test_binary_codec PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_binary_codec COMMAND test_binary_codec)

# Approximate memory accounting helper coverage.
add_executable(test_memory_accounting test_memory_accounting.cpp)
target_link_libraries(test_memory_accounting PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_memory_accounting COMMAND test_memory_accounting)

# Stable hash helper coverage for compiler cache keys.
add_executable(test_stable_hash test_stable_hash.cpp)
target_link_libraries(test_stable_hash PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_stable_hash COMMAND test_stable_hash)

# string_parts_builder coverage for artifact-style string assembly.
add_executable(test_string_parts_builder test_string_parts_builder.cpp)
target_link_libraries(test_string_parts_builder PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_string_parts_builder COMMAND test_string_parts_builder)

# text_builder coverage for contiguous artifact-style text assembly.
add_executable(test_text_builder test_text_builder.cpp)
target_link_libraries(test_text_builder PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_text_builder COMMAND test_text_builder)

# mixed_t dynamic dispatch coverage.
add_executable(test_mixed_t_dynamic test_mixed_t_dynamic.cpp)
target_link_libraries(test_mixed_t_dynamic PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_mixed_t_dynamic COMMAND test_mixed_t_dynamic)

# Compact mixed layout plus release-active copy/move/lifetime checks.
add_executable(test_mixed_layout test_mixed_layout.cpp)
target_link_libraries(test_mixed_layout PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_mixed_layout COMMAND test_mixed_layout)

# Strict identity coverage for null dynamic carriers widened into mixed_t.
add_executable(test_strict_identity_null_dynamic test_strict_identity_null_dynamic.cpp)
target_link_libraries(test_strict_identity_null_dynamic PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_strict_identity_null_dynamic COMMAND test_strict_identity_null_dynamic)

# Object identity is independent of a compatible base/derived shared-handle view.
add_executable(test_strict_identity_shared test_strict_identity_shared.cpp)
target_link_libraries(test_strict_identity_shared PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_strict_identity_shared COMMAND test_strict_identity_shared)

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

# Isolate document behavior from the known aggregate static/shared link-order debt.
# The strict CLI test separately proves the complete JSON module composition.
add_executable(test_json_document test_json_document.cpp
	${CMAKE_CURRENT_SOURCE_DIR}/../../../runtime/include/modules/json/document.cpp)
target_link_libraries(test_json_document PRIVATE scpp_runtime_test_support)
target_include_directories(test_json_document PRIVATE ${CMAKE_CURRENT_SOURCE_DIR}/../../../runtime/include)
target_compile_features(test_json_document PRIVATE cxx_std_23)
add_test(NAME scpp_test_json_document COMMAND test_json_document)

add_executable(test_json_pull_reader test_json_pull_reader.cpp)
target_link_libraries(test_json_pull_reader PRIVATE scpp_runtime_test_support)
target_include_directories(test_json_pull_reader PRIVATE ${CMAKE_CURRENT_SOURCE_DIR}/../../../runtime/include)
target_compile_features(test_json_pull_reader PRIVATE cxx_std_23)
add_test(NAME scpp_test_json_pull_reader COMMAND test_json_pull_reader)

# datetime runtime and PHP wrapper coverage.
add_executable(test_datetime test_datetime.cpp)
target_link_libraries(test_datetime PRIVATE scpp_lang_php scpp_datetime scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_datetime COMMAND test_datetime)

if(TARGET scpp_regex)
	add_executable(test_regex test_regex.cpp)
	target_link_libraries(test_regex PRIVATE scpp_lang_php scpp_regex scpp_runtime scpp_runtime_test_support)
	add_test(NAME scpp_test_regex COMMAND test_regex)
endif()

if(TARGET scpp_curl)
	add_executable(test_curl test_curl.cpp)
	target_link_libraries(test_curl PRIVATE scpp_curl scpp_runtime scpp_runtime_test_support)
	add_test(NAME scpp_test_curl COMMAND test_curl)
endif()

# PHP process/CLI builtin coverage.
add_executable(test_php_process test_php_process.cpp)
target_link_libraries(test_php_process PRIVATE scpp_lang_php scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_php_process COMMAND test_php_process)

# mysqli connect/close smoke coverage for the first vertical slice.
# Disabled by default because many local/sandbox builds do not have a runnable MariaDB/MySQL test target.
option(SCPP_ENABLE_MYSQLI_TESTS "Build and register mysqli runtime smoke tests" OFF)
if(SCPP_WITH_MYSQLI)
	add_executable(test_mysqli_fetch_exhaustion test_mysqli_fetch_exhaustion.cpp)
	target_link_libraries(test_mysqli_fetch_exhaustion PRIVATE scpp_php_legacy scpp_lang_php scpp_runtime scpp_runtime_test_support)
	add_test(NAME scpp_test_mysqli_fetch_exhaustion COMMAND test_mysqli_fetch_exhaustion)
endif()
if(SCPP_ENABLE_MYSQLI_TESTS AND SCPP_WITH_MYSQLI)
	add_executable(test_mysqli_connect test_mysqli_connect.cpp)
	target_link_libraries(test_mysqli_connect PRIVATE scpp_php_legacy scpp_lang_php scpp_runtime scpp_runtime_test_support)
	add_test(NAME scpp_test_mysqli_connect COMMAND test_mysqli_connect)
endif()
if(CMAKE_SYSTEM_NAME STREQUAL "Linux")
	add_executable(test_snapshot test_snapshot.cpp)
	target_link_libraries(test_snapshot PRIVATE scpp_filesystem scpp_runtime scpp_runtime_test_support)
	add_test(NAME scpp_test_snapshot COMMAND test_snapshot)
	set_tests_properties(scpp_test_snapshot PROPERTIES TIMEOUT 30)
endif()

# Recover the existing shared owner for retained self references.
add_executable(test_shared_self test_shared_self.cpp)
target_link_libraries(test_shared_self PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_shared_self COMMAND test_shared_self)

# Typed cursors retain invariant Storage membership and widen only yielded handles.
add_executable(test_storage_cursor test_storage_cursor.cpp)
target_link_libraries(test_storage_cursor PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_storage_cursor COMMAND test_storage_cursor)

# Checked canonical integer exponentiation.
add_executable(test_pow test_pow.cpp)
target_link_libraries(test_pow PRIVATE scpp_runtime scpp_runtime_test_support)
add_test(NAME scpp_test_pow COMMAND test_pow)

# The public caller TU gets no compatibility include roots from the workload.
add_library(worker_pool_workload OBJECT worker_pool_workload.cpp)
target_link_libraries(worker_pool_workload PRIVATE scpp_tasks)
add_executable(test_worker_pool_api test_worker_pool_api.cpp $<TARGET_OBJECTS:worker_pool_workload>)
target_link_libraries(test_worker_pool_api PRIVATE scpp_tasks_api)
add_test(NAME scpp_test_worker_pool_api COMMAND test_worker_pool_api)
set_tests_properties(scpp_test_worker_pool_api PROPERTIES TIMEOUT 30)

add_library(worker_pool_error_check OBJECT worker_pool_error_check.cpp)
target_link_libraries(worker_pool_error_check PRIVATE scpp_runtime_configuration)
add_executable(test_public_failure test_public_failure.cpp $<TARGET_OBJECTS:worker_pool_error_check>)
target_link_libraries(test_public_failure PRIVATE scpp_core_api)
add_test(NAME scpp_test_public_failure COMMAND test_public_failure)
# New disabled callers link only core; exception inspection uses a separate TU.
foreach(mode default explicit)
  add_executable(test_worker_pool_disabled_${mode} test_worker_pool_disabled.cpp
    $<TARGET_OBJECTS:worker_pool_error_check>)
  target_link_libraries(test_worker_pool_disabled_${mode} PRIVATE scpp_core_api)
  if(mode STREQUAL "explicit")
    target_compile_definitions(test_worker_pool_disabled_${mode} PRIVATE SCPP_HAS_TASKS=0)
  endif()
  add_test(NAME scpp_test_worker_pool_disabled_${mode} COMMAND test_worker_pool_disabled_${mode})
endforeach()
# Preserve the old template API's no-runtime-library fallback independently.
add_executable(test_worker_pool_disabled_umbrella test_worker_pool_disabled_legacy.cpp
  $<TARGET_OBJECTS:worker_pool_error_check>)
target_link_libraries(test_worker_pool_disabled_umbrella PRIVATE scpp_runtime_configuration)
target_compile_definitions(test_worker_pool_disabled_umbrella PRIVATE SCPP_HAS_TASKS=0)
add_test(NAME scpp_test_worker_pool_disabled_umbrella COMMAND test_worker_pool_disabled_umbrella)

add_executable(test_concrete_integers test_concrete_integers.cpp)
target_link_libraries(test_concrete_integers PRIVATE scpp_runtime_core)
add_test(NAME scpp_test_concrete_integers COMMAND test_concrete_integers)
find_package(Python3 COMPONENTS Interpreter REQUIRED)
add_test(NAME scpp_test_integer_api_boundary COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_integer_api.py --compiler ${CMAKE_CXX_COMPILER})

# Public-only consumer: no private runtime headers in its compile command.
add_executable(test_public_string test_public_string.cpp)
target_link_libraries(test_public_string PRIVATE scpp_core_api)
add_test(NAME scpp_test_public_string COMMAND test_public_string)

# Probe with the same ABI-affecting compile flags as the runtime build.
string(TOUPPER "${CMAKE_BUILD_TYPE}" scpp_layout_build_type)
separate_arguments(scpp_layout_flags NATIVE_COMMAND "${CMAKE_CXX_FLAGS} ${CMAKE_CXX_FLAGS_${scpp_layout_build_type}}")
set(scpp_layout_arguments)
foreach(flag IN LISTS scpp_layout_flags)
  list(APPEND scpp_layout_arguments "--flag=${flag}")
endforeach()
add_test(NAME scpp_test_value_layout COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/../../../runtime/tools/value_layout.py
  --check --compiler ${CMAKE_CXX_COMPILER} ${scpp_layout_arguments})

add_library(error_api_fixture OBJECT error_api_fixture.cpp)
target_link_libraries(error_api_fixture PRIVATE scpp_runtime_configuration)
add_executable(test_public_errors test_public_errors.cpp $<TARGET_OBJECTS:error_api_fixture>)
target_link_libraries(test_public_errors PRIVATE scpp_core_api)
add_test(NAME scpp_test_public_errors COMMAND test_public_errors)

# Explicit diagnostic target only: glibc interposition must not replace ASan's allocator.
set(scpp_allocation_probe_flags "${CMAKE_CXX_FLAGS} ${CMAKE_CXX_FLAGS_DEBUG} ${CMAKE_CXX_FLAGS_RELEASE} ${CMAKE_CXX_FLAGS_RELWITHDEBINFO} ${CMAKE_CXX_FLAGS_MINSIZEREL}")
if(CMAKE_SYSTEM_NAME STREQUAL "Linux" AND NOT scpp_allocation_probe_flags MATCHES "sanitize")
  add_executable(test_public_errors_allocations EXCLUDE_FROM_ALL test_public_errors.cpp $<TARGET_OBJECTS:error_api_fixture>)
  target_compile_definitions(test_public_errors_allocations PRIVATE SCPP_MEASURE_GLIBC_ALLOCATIONS=1)
  target_link_libraries(test_public_errors_allocations PRIVATE scpp_core_api)
endif()

add_library(public_scan_fixture OBJECT public_scan_fixture.cpp)
target_link_libraries(public_scan_fixture PRIVATE scpp_runtime_configuration)
add_executable(test_public_scan test_public_scan.cpp $<TARGET_OBJECTS:public_scan_fixture>)
target_link_libraries(test_public_scan PRIVATE scpp_filesystem_api)
add_test(NAME scpp_test_public_scan COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/run_public_scan.py $<TARGET_FILE:test_public_scan>)
add_test(NAME scpp_test_concrete_generation COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_concrete_types.py)

add_executable(test_api_generation test_api_generation.cpp)
target_link_libraries(test_api_generation PRIVATE scpp_api_generation Threads::Threads)
add_test(NAME scpp_test_api_generation COMMAND test_api_generation)
add_test(NAME scpp_test_api_metadata COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_api_metadata.py --tool $<TARGET_FILE:scpp_api_generator>)

add_library(mixed_api_fixture OBJECT mixed_api_fixture.cpp)
target_link_libraries(mixed_api_fixture PRIVATE scpp_runtime_configuration)
add_executable(test_public_mixed test_public_mixed.cpp $<TARGET_OBJECTS:mixed_api_fixture>)
target_link_libraries(test_public_mixed PRIVATE scpp_core_api)
add_test(NAME scpp_test_public_mixed COMMAND test_public_mixed)

add_library(dynamic_api_fixture OBJECT dynamic_api_fixture.cpp)
target_link_libraries(dynamic_api_fixture PRIVATE scpp_runtime_configuration)
add_executable(test_public_dynamic test_public_dynamic.cpp $<TARGET_OBJECTS:dynamic_api_fixture>)
target_link_libraries(test_public_dynamic PRIVATE scpp_core_api)
add_test(NAME scpp_test_public_dynamic COMMAND test_public_dynamic)

add_executable(test_global_operators test_global_operators.cpp)
target_link_libraries(test_global_operators PRIVATE scpp_core_api)
add_test(NAME scpp_test_global_operators COMMAND test_global_operators)
add_test(NAME scpp_check_global_operators COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_global_operators.py $<TARGET_FILE:scpp_api_generator>)

add_executable(test_numeric_operators test_numeric_operators.cpp)
target_link_libraries(test_numeric_operators PRIVATE scpp_core_api)
add_test(NAME scpp_test_numeric_operators COMMAND test_numeric_operators)

add_executable(test_checked_scalar_casts test_checked_scalar_casts.cpp)
target_link_libraries(test_checked_scalar_casts PRIVATE scpp_runtime_core)
add_test(NAME scpp_test_checked_scalar_casts COMMAND test_checked_scalar_casts)

add_executable(test_public_conversions test_public_conversions.cpp)
target_link_libraries(test_public_conversions PRIVATE scpp_core_api)
add_test(NAME scpp_test_public_conversions COMMAND test_public_conversions)
add_test(NAME scpp_check_conversions COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_conversions.py $<TARGET_FILE:scpp_api_generator>)

add_executable(test_scalar_conversion_surface test_scalar_conversion_surface.cpp)
target_link_libraries(test_scalar_conversion_surface PRIVATE scpp_core_api)
add_test(NAME scpp_test_scalar_conversion_surface COMMAND test_scalar_conversion_surface)

add_executable(test_boxed_integer_casts test_boxed_integer_casts.cpp)
target_link_libraries(test_boxed_integer_casts PRIVATE scpp_core_api)
add_test(NAME scpp_test_boxed_integer_casts COMMAND test_boxed_integer_casts)
add_test(NAME scpp_check_conversion_coverage COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_conversion_coverage.py)

add_executable(test_public_conditions test_public_conditions.cpp)
target_link_libraries(test_public_conditions PRIVATE scpp_core_api)
add_test(NAME scpp_test_public_conditions COMMAND test_public_conditions)
add_test(NAME scpp_check_function_sets COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_function_sets.py $<TARGET_FILE:scpp_api_generator>)

add_executable(test_public_text test_public_text.cpp)
target_link_libraries(test_public_text PRIVATE scpp_core_api)
add_test(NAME scpp_test_public_text COMMAND test_public_text)
add_test(NAME scpp_check_text_api COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_text_api.py $<TARGET_FILE:scpp_api_generator>)

add_executable(test_public_identity test_public_identity.cpp)
target_link_libraries(test_public_identity PRIVATE scpp_core_api)
add_test(NAME scpp_test_public_identity COMMAND test_public_identity)
add_test(NAME scpp_check_identity_api COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_identity_api.py $<TARGET_FILE:scpp_api_generator>)

add_test(NAME scpp_check_identity_plans COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_identity_plans.py --tool $<TARGET_FILE:scpp_api_generator>)

add_test(NAME scpp_check_xor_api COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_xor_api.py --tool $<TARGET_FILE:scpp_api_generator>)

add_executable(test_public_observations test_public_observations.cpp)
target_link_libraries(test_public_observations PRIVATE scpp_core_api)
add_test(NAME scpp_test_public_observations COMMAND test_public_observations)
add_test(NAME scpp_check_observation_api COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_observation_api.py $<TARGET_FILE:scpp_api_generator>)

add_executable(test_public_identity_pairs test_public_identity_pairs.cpp)
target_link_libraries(test_public_identity_pairs PRIVATE scpp_core_api)
add_test(NAME scpp_test_public_identity_pairs COMMAND test_public_identity_pairs)
add_test(NAME scpp_check_identity_pairs COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_identity_pairs.py $<TARGET_FILE:scpp_api_generator>)

add_executable(test_public_special_integers test_public_special_integers.cpp)
target_link_libraries(test_public_special_integers PRIVATE scpp_core_api)
add_test(NAME scpp_test_public_special_integers COMMAND test_public_special_integers)
add_test(NAME scpp_check_special_integer_api COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_special_integer_api.py $<TARGET_FILE:scpp_api_generator>)

add_executable(test_public_compare test_public_compare.cpp)
target_link_libraries(test_public_compare PRIVATE scpp_core_api)
add_test(NAME scpp_test_public_compare COMMAND test_public_compare)
add_test(NAME scpp_check_compare_api COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_compare_api.py $<TARGET_FILE:scpp_api_generator>)

add_executable(test_public_unary test_public_unary.cpp)
target_link_libraries(test_public_unary PRIVATE scpp_core_api)
add_test(NAME scpp_test_public_unary COMMAND test_public_unary)
add_test(NAME scpp_check_unary_api COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_unary_api.py $<TARGET_FILE:scpp_api_generator>)

add_test(NAME scpp_check_logical_not COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_logical_not.py --tool $<TARGET_FILE:scpp_api_generator>)

add_executable(test_public_updates test_public_updates.cpp)
target_link_libraries(test_public_updates PRIVATE scpp_core_api)
add_test(NAME scpp_test_public_updates COMMAND test_public_updates)
add_test(NAME scpp_check_update_api COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_update_api.py $<TARGET_FILE:scpp_api_generator>)

add_test(NAME scpp_check_compound_api COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_compound_api.py --tool $<TARGET_FILE:scpp_api_generator>)

add_executable(test_public_boxed_arithmetic test_public_boxed_arithmetic.cpp)
target_link_libraries(test_public_boxed_arithmetic PRIVATE scpp_core_api)
add_test(NAME scpp_test_public_boxed_arithmetic COMMAND test_public_boxed_arithmetic)
add_test(NAME scpp_check_boxed_arithmetic_api COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_boxed_arithmetic_api.py $<TARGET_FILE:scpp_api_generator>)

add_test(NAME scpp_check_text_compound_api COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_text_compound_api.py --tool $<TARGET_FILE:scpp_api_generator>)

add_test(NAME scpp_check_carrier_projection COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_carrier_projection.py --tool $<TARGET_FILE:scpp_api_generator>)

add_test(NAME scpp_check_conditional_lifecycle COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_conditional_lifecycle.py --tool $<TARGET_FILE:scpp_api_generator>)

add_test(NAME scpp_check_vector_lifecycle COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_vector_lifecycle.py --tool $<TARGET_FILE:scpp_api_generator>)

add_test(NAME scpp_check_hash_lifecycle COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_hash_lifecycle.py --tool $<TARGET_FILE:scpp_api_generator>)

add_test(NAME scpp_check_sentinel_carriers COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_sentinel_carriers.py --tool $<TARGET_FILE:scpp_api_generator>)

add_test(NAME scpp_check_carrier_equality COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_carrier_equality.py --tool $<TARGET_FILE:scpp_api_generator>)

add_test(NAME scpp_check_carrier_text COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_carrier_text.py --tool $<TARGET_FILE:scpp_api_generator>)

add_test(NAME scpp_check_carrier_unary COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_carrier_unary.py --tool $<TARGET_FILE:scpp_api_generator>)

add_test(NAME scpp_check_carrier_compound COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_carrier_compound.py --tool $<TARGET_FILE:scpp_api_generator>)

add_test(NAME scpp_check_carrier_special COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_carrier_compound.py --special --tool $<TARGET_FILE:scpp_api_generator>)

add_test(NAME scpp_check_carrier_power COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_carrier_compound.py --power --tool $<TARGET_FILE:scpp_api_generator>)

add_test(NAME scpp_check_carrier_order COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_carrier_order.py --tool $<TARGET_FILE:scpp_api_generator>)

add_test(NAME scpp_check_carrier_coalesce COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_carrier_coalesce.py --tool $<TARGET_FILE:scpp_api_generator>)

add_test(NAME scpp_check_carrier_conditional COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_carrier_conditional.py --tool $<TARGET_FILE:scpp_api_generator>)

add_test(NAME scpp_check_constant_conditions COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_constant_conditions.py --tool $<TARGET_FILE:scpp_api_generator>)

add_test(NAME scpp_check_scoped_conditions COMMAND ${Python3_EXECUTABLE}
  ${CMAKE_CURRENT_SOURCE_DIR}/check_scoped_conditions.py --tool $<TARGET_FILE:scpp_api_generator>)
