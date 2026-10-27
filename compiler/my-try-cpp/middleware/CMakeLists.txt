cmake_minimum_required(VERSION 3.20)
project(compiler_middleware LANGUAGES CXX)
if(NOT EXISTS "${RUNTIME_PACKAGE}/package.cmake")
  message(FATAL_ERROR "Missing runtime package. Run tools/build.py runtime first.")
endif()
include("${RUNTIME_PACKAGE}/package.cmake")
find_package(Python3 REQUIRED COMPONENTS Interpreter)
execute_process(COMMAND "${Python3_EXECUTABLE}" -B
  "${CMAKE_CURRENT_SOURCE_DIR}/tools/scalar_types.py" --check
  COMMAND_ERROR_IS_FATAL ANY)
set(BINDINGS "${CMAKE_CURRENT_BINARY_DIR}/generated/runtime_bindings.hpp")
add_custom_command(OUTPUT "${BINDINGS}"
  COMMAND "${Python3_EXECUTABLE}" -B "${CMAKE_CURRENT_SOURCE_DIR}/tools/type_names.py"
    --bindings "${BINDINGS}"
  DEPENDS tools/type_names.py types.json
  VERBATIM)
add_library(compiler_middleware STATIC src/textbuilder.cpp src/types.cpp src/string.cpp src/vector_string.cpp
  src/json_value.cpp src/host.cpp "${BINDINGS}")
target_compile_features(compiler_middleware PUBLIC cxx_std_23)
target_include_directories(compiler_middleware PUBLIC include)
target_include_directories(compiler_middleware PRIVATE "${CMAKE_CURRENT_BINARY_DIR}/generated")
target_link_libraries(compiler_middleware PRIVATE compiler_runtime)
target_compile_options(compiler_middleware PRIVATE -Wall -Wextra -Wpedantic)
