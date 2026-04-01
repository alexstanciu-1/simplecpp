cmake_minimum_required(VERSION 3.20)

project(scpp_runtime LANGUAGES CXX)

option(SCPP_LANGUAGE_TARGET_PHP "Build runtime with PHP-target array semantics" ON)

enable_testing()

add_library(scpp_runtime
	src/runtime.cpp
)

target_include_directories(scpp_runtime
PUBLIC
	${CMAKE_CURRENT_SOURCE_DIR}/include
)

target_compile_features(scpp_runtime PUBLIC cxx_std_23)

if(SCPP_LANGUAGE_TARGET_PHP)
	target_compile_definitions(scpp_runtime PUBLIC SCPP_LANGUAGE_TARGET_PHP=1)
endif()

add_subdirectory(tests)
