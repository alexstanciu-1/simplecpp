cmake_minimum_required(VERSION 3.20)
project(my_try_cpp LANGUAGES CXX)
if(NOT CMAKE_CXX_COMPILER_ID MATCHES "Clang")
  message(FATAL_ERROR "This project requires Clang")
endif()
if(NOT EXISTS "${MIDDLEWARE_PACKAGE}/package.cmake")
  message(FATAL_ERROR "Missing middleware package. Run tools/build.py runtime, then middleware.")
endif()
include("${MIDDLEWARE_PACKAGE}/package.cmake")
if(NOT EXISTS "${MIDDLEWARE_PACKAGE}/detail/runtime_scalars.hpp")
  message(FATAL_ERROR "Middleware lacks the support bridge. Run tools/build.py middleware explicitly.")
endif()
find_package(Python3 REQUIRED COMPONENTS Interpreter)
find_program(MOLD_EXECUTABLE mold REQUIRED)
function(compiler_target target)
  target_compile_features(${target} PRIVATE cxx_std_23)
  target_compile_options(${target} PRIVATE -Wall -Wextra -Wpedantic)
  target_link_options(${target} PRIVATE "--ld-path=${MOLD_EXECUTABLE}" "LINKER:--threads=8")
  target_link_libraries(${target} PRIVATE compiler_support)
  set_property(TARGET ${target} PROPERTY CXX_COMPILER_LAUNCHER
    "${Python3_EXECUTABLE};${CMAKE_CURRENT_SOURCE_DIR}/tools/checked_compile.py")
  get_target_property(sources ${target} SOURCES)
  foreach(source IN LISTS sources)
    set_property(SOURCE ${source} APPEND PROPERTY OBJECT_DEPENDS
      "${CMAKE_CURRENT_SOURCE_DIR}/tools/checked_compile.py")
  endforeach()
endfunction()
# Live compiler support rebuilds automatically; published middleware/runtime remain explicit.
add_library(compiler_support STATIC
  support/src/token.cpp support/src/token_buffer.cpp support/src/token_sequence.cpp
  support/src/token_jobs.cpp support/src/file_records.cpp support/src/source_reads.cpp
  support/src/discovery.cpp)
target_compile_features(compiler_support PUBLIC cxx_std_23)
target_compile_options(compiler_support PRIVATE -Wall -Wextra -Wpedantic)
target_include_directories(compiler_support PUBLIC model/include support/include)
target_include_directories(compiler_support PRIVATE "${MIDDLEWARE_PACKAGE}/detail")
target_link_libraries(compiler_support PUBLIC compiler_middleware PRIVATE compiler_runtime)

add_library(compiler_startup STATIC src/startup/configuration.cpp src/startup/catalog.cpp
  src/startup/validation.cpp src/startup/model.cpp src/sources/inventory.cpp src/tokenize/scanner.cpp src/tokenize/spellings.cpp src/tokenize/stage.cpp)
compiler_target(compiler_startup)
target_include_directories(compiler_startup PUBLIC src)
target_link_libraries(compiler_startup PUBLIC compiler_support)
add_executable(my-try-cpp src/main.cpp src/tokenize/command.cpp)
compiler_target(my-try-cpp)
target_link_libraries(my-try-cpp PRIVATE compiler_startup)
add_executable(runtime-smoke-test tests/runtime_smoke.cpp)
compiler_target(runtime-smoke-test)
add_executable(concrete-types-test tests/concrete_types.cpp)
compiler_target(concrete-types-test)
add_executable(startup-lifecycle-test tests/startup_lifecycle.cpp)
compiler_target(startup-lifecycle-test)
target_link_libraries(startup-lifecycle-test PRIVATE compiler_startup)
add_executable(scalar-types-test tests/scalars.cpp)
compiler_target(scalar-types-test)
enable_testing()
add_test(NAME scalar_runtime_contract COMMAND scalar-types-test)
add_test(NAME scalar_generation_current COMMAND "${Python3_EXECUTABLE}" -B
  "${CMAKE_CURRENT_SOURCE_DIR}/middleware/tools/scalar_types.py" --check)
add_test(NAME runtime_middleware_smoke COMMAND runtime-smoke-test)
add_test(NAME concrete_value_lifetimes COMMAND concrete-types-test)
add_test(NAME concrete_type_names COMMAND "${Python3_EXECUTABLE}" -B "${CMAKE_CURRENT_SOURCE_DIR}/tests/type_names.py")
add_test(NAME startup_acceptance COMMAND "${Python3_EXECUTABLE}" -B "${CMAKE_CURRENT_SOURCE_DIR}/tests/startup.py"
  "$<TARGET_FILE:my-try-cpp>" "$<TARGET_FILE:startup-lifecycle-test>")

add_test(NAME scalar_public_boundary COMMAND "${Python3_EXECUTABLE}" -B "${CMAKE_CURRENT_SOURCE_DIR}/tests/scalar_boundary.py")

# Explicit profiling only: excluded from normal builds and the test suite.
add_executable(catalog-validation-profile EXCLUDE_FROM_ALL benchmarks/catalog_validation.cpp)
compiler_target(catalog-validation-profile)
target_link_libraries(catalog-validation-profile PRIVATE compiler_startup)

add_executable(source-discovery-test tests/source_discovery.cpp)
compiler_target(source-discovery-test)
target_link_libraries(source-discovery-test PRIVATE compiler_startup)
add_test(NAME source_discovery COMMAND "${Python3_EXECUTABLE}" -B
  "${CMAKE_CURRENT_SOURCE_DIR}/tests/source_discovery.py"
  "$<TARGET_FILE:my-try-cpp>" "$<TARGET_FILE:source-discovery-test>")
set_tests_properties(source_discovery PROPERTIES TIMEOUT 60)

add_executable(source-discovery-profile EXCLUDE_FROM_ALL benchmarks/source_discovery.cpp)
compiler_target(source-discovery-profile)
target_link_libraries(source-discovery-profile PRIVATE compiler_startup)

add_executable(source-reading-test tests/source_reading.cpp)
compiler_target(source-reading-test)
target_link_libraries(source-reading-test PRIVATE compiler_startup)
add_test(NAME source_reading COMMAND "${Python3_EXECUTABLE}" -B
  "${CMAKE_CURRENT_SOURCE_DIR}/tests/source_reading.py"
  "$<TARGET_FILE:source-reading-test>" "$<TARGET_FILE:my-try-cpp>")
set_tests_properties(source_reading PROPERTIES TIMEOUT 60)

add_executable(source-reading-profile EXCLUDE_FROM_ALL benchmarks/source_reading.cpp)
compiler_target(source-reading-profile)
target_link_libraries(source-reading-profile PRIVATE compiler_startup)

add_executable(source-concurrency-profile EXCLUDE_FROM_ALL benchmarks/source_concurrency.cpp)
compiler_target(source-concurrency-profile)
target_link_libraries(source-concurrency-profile PRIVATE compiler_startup)

add_executable(tokenizer-test tests/tokenizer.cpp)
compiler_target(tokenizer-test)
target_link_libraries(tokenizer-test PRIVATE compiler_startup)
add_test(NAME tokenizer_contract COMMAND tokenizer-test)
add_executable(tokenization-profile EXCLUDE_FROM_ALL benchmarks/tokenization.cpp)
compiler_target(tokenization-profile)
target_link_libraries(tokenization-profile PRIVATE compiler_startup)
add_executable(tokenization-allocations EXCLUDE_FROM_ALL benchmarks/tokenization.cpp)
compiler_target(tokenization-allocations)
target_compile_definitions(tokenization-allocations PRIVATE TOKEN_ALLOCATIONS=1)
target_link_libraries(tokenization-allocations PRIVATE compiler_startup)
add_executable(token-lifecycle-test tests/token_lifecycle.cpp)
compiler_target(token-lifecycle-test)
target_link_libraries(token-lifecycle-test PRIVATE compiler_startup)
add_test(NAME token_lifecycle COMMAND "${Python3_EXECUTABLE}" -B
  "${CMAKE_CURRENT_SOURCE_DIR}/tests/token_lifecycle.py" "$<TARGET_FILE:token-lifecycle-test>")
set_tests_properties(token_lifecycle PROPERTIES TIMEOUT 30)
add_test(NAME tokenizer_inputs COMMAND "${Python3_EXECUTABLE}" -B
  "${CMAKE_CURRENT_SOURCE_DIR}/tests/tokenizer_inputs.py" "$<TARGET_FILE:my-try-cpp>")
set_tests_properties(tokenizer_inputs PROPERTIES TIMEOUT 60)

add_test(NAME ephemeral_inputs COMMAND "${Python3_EXECUTABLE}" -B
  "${CMAKE_CURRENT_SOURCE_DIR}/tests/ephemeral_inputs.py" "$<TARGET_FILE:my-try-cpp>")
set_tests_properties(ephemeral_inputs PROPERTIES TIMEOUT 15)
