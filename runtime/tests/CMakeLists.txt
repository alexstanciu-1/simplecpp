if(NOT TARGET scpp_runtime)
	message(FATAL_ERROR "tests/CMakeLists.txt expects the scpp_runtime target to exist")
endif()

# Defines the shared helper type used by multiple runtime smoke tests.
add_library(scpp_runtime_test_support INTERFACE)
target_include_directories(scpp_runtime_test_support INTERFACE ${CMAKE_CURRENT_SOURCE_DIR})

# Core wrapper smoke coverage.
add_executable(test_core test_core.cpp)
target_link_libraries(test_core PRIVATE scpp_runtime scpp_runtime_test_support)

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
