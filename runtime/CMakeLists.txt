cmake_minimum_required(VERSION 3.20)

project(scpp_runtime LANGUAGES CXX)

enable_testing()

add_library(scpp_runtime
	src/runtime.cpp
)

target_include_directories(scpp_runtime
PUBLIC
	${CMAKE_CURRENT_SOURCE_DIR}/include
)

target_compile_features(scpp_runtime PUBLIC cxx_std_23)

add_subdirectory(tests)
