cmake_minimum_required(VERSION 3.21)
project(scpp_v02_foundation LANGUAGES CXX)

if(NOT CMAKE_GENERATOR STREQUAL "Ninja")
  message(FATAL_ERROR "v0.2 uses Ninja. Configure with cmake --preset default.")
endif()
if(NOT CMAKE_CXX_COMPILER_ID MATCHES "Clang")
  message(FATAL_ERROR "v0.2 uses Clang. Configure with cmake --preset default.")
endif()
find_program(SCPP_MOLD_LINKER NAMES ld.mold mold REQUIRED)
add_link_options("--ld-path=${SCPP_MOLD_LINKER}")
message(STATUS "v0.2 linker: ${SCPP_MOLD_LINKER}; default build preset: 8 jobs")

# Tool-private support; this is not the runtime's template-free public API.
add_library(scpp_tool_json INTERFACE)
target_include_directories(scpp_tool_json INTERFACE ${CMAKE_CURRENT_SOURCE_DIR}/tools/support)
target_compile_features(scpp_tool_json INTERFACE cxx_std_23)

add_library(scpp_metadata_value STATIC tools/api_generator/value.cpp)
target_include_directories(scpp_metadata_value PUBLIC ${CMAKE_CURRENT_SOURCE_DIR}/tools)
target_link_libraries(scpp_metadata_value PRIVATE scpp_tool_json)
target_compile_features(scpp_metadata_value PUBLIC cxx_std_23)

add_library(scpp_api_generation STATIC
  tools/api_generator/runtime.cpp tools/api_generator/emission.cpp tools/api_generator/ownership.cpp)
target_link_libraries(scpp_api_generation PUBLIC scpp_metadata_value)
add_executable(scpp_api_generator tools/api_generator/main.cpp)
target_link_libraries(scpp_api_generator PRIVATE scpp_api_generation)

set(runtime_metadata ${CMAKE_CURRENT_SOURCE_DIR}/runtime/metadata/runtime.json)
set(runtime_publication ${CMAKE_CURRENT_SOURCE_DIR}/runtime/metadata/publication.json)
set(runtime_generated ${CMAKE_CURRENT_SOURCE_DIR}/runtime/generated)
set(runtime_outputs
  ${runtime_generated}/public/scpp/carriers.hpp
  ${runtime_generated}/public/scpp/vectors.hpp
  ${runtime_generated}/public/scpp/string.hpp
  ${runtime_generated}/public/scpp/values.hpp
  ${runtime_generated}/public/scpp/integers.hpp
  ${runtime_generated}/public/scpp/numeric.hpp
  ${runtime_generated}/public/scpp/native_bridge.hpp
  ${runtime_generated}/public/scpp/short_names.hpp
  ${runtime_generated}/src/runtime.cpp
  ${runtime_generated}/prepared.json
  ${runtime_generated}/inventory.json)
add_custom_command(OUTPUT ${runtime_outputs}
  COMMAND scpp_api_generator ${runtime_metadata} ${runtime_publication} ${runtime_generated}
  DEPENDS scpp_api_generator ${runtime_metadata} ${runtime_publication}
  VERBATIM)
add_custom_target(scpp_runtime_publication DEPENDS ${runtime_outputs})
add_library(scpp_runtime STATIC ${runtime_generated}/src/runtime.cpp)
add_dependencies(scpp_runtime scpp_runtime_publication)
target_include_directories(scpp_runtime
  PUBLIC ${runtime_generated}/public
  PRIVATE ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/numeric ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/sequence)
target_compile_features(scpp_runtime PUBLIC cxx_std_23)

add_executable(scpp_runtime_demo examples/runtime_demo.cpp)
target_link_libraries(scpp_runtime_demo PRIVATE scpp_runtime)

include(CTest)
if(BUILD_TESTING)
  find_package(Python3 3.10 REQUIRED COMPONENTS Interpreter)
  # Explicit G2 probe target; production API generation still uses the C++ engine.
  string(TOUPPER "${CMAKE_BUILD_TYPE}" probe_build_type)
  separate_arguments(probe_flags NATIVE_COMMAND "${CMAKE_CXX_FLAGS} ${CMAKE_CXX_FLAGS_${probe_build_type}}")
  set(probe_flag_args)
  foreach(flag IN LISTS probe_flags)
    list(APPEND probe_flag_args "--flag=${flag}")
  endforeach()
  add_custom_target(scpp_layout_facts
    COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/tools/layout_probe/probe.py
      --cxx ${CMAKE_CXX_COMPILER} ${probe_flag_args} "--flag=--ld-path=${SCPP_MOLD_LINKER}"
      --cross-compiling ${CMAKE_CROSSCOMPILING} --out ${CMAKE_CURRENT_BINARY_DIR}/native-layout
    BYPRODUCTS ${CMAKE_CURRENT_BINARY_DIR}/native-layout/facts.json
      ${CMAKE_CURRENT_BINARY_DIR}/native-layout/context.json
    VERBATIM)
  add_test(NAME generator_parity COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tests/generator_parity/parity.py
    --reference $<TARGET_FILE:scpp_api_generator>
    --candidate $<TARGET_FILE:scpp_api_generator>
    --source ${CMAKE_CURRENT_SOURCE_DIR}
    --work ${CMAKE_CURRENT_BINARY_DIR}/generator-parity)
  add_test(NAME generator_parity_comparison COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tests/generator_parity/test_comparison.py)
  add_executable(carrier_preparation_test tests/carrier_preparation.cpp)
  target_link_libraries(carrier_preparation_test PRIVATE scpp_api_generation)
  add_test(NAME carrier_preparation COMMAND carrier_preparation_test ${runtime_metadata} ${runtime_publication})
  add_test(NAME carrier_publication COMMAND ${CMAKE_COMMAND}
    -DMOLD_LINKER=${SCPP_MOLD_LINKER}
    -DGENERATOR=$<TARGET_FILE:scpp_api_generator>
    -DMETADATA=${runtime_metadata} -DPUBLICATION=${runtime_publication}
    -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
    -DSOURCE=${CMAKE_CURRENT_SOURCE_DIR}
    -DWORK=${CMAKE_CURRENT_BINARY_DIR}/carrier-proof
    -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/carrier_publication.cmake)
  add_executable(carrier_consumer_test tests/carrier_consumer.cpp)
  target_link_libraries(carrier_consumer_test PRIVATE scpp_runtime)
  add_test(NAME carrier_consumer COMMAND carrier_consumer_test)
  add_test(NAME runtime_demo COMMAND scpp_runtime_demo)
  set_tests_properties(runtime_demo PROPERTIES PASS_REGULAR_EXPRESSION "Ada: 21; ratio=7")
  add_executable(vector_preparation_test tests/vector_preparation.cpp)
  target_link_libraries(vector_preparation_test PRIVATE scpp_api_generation)
  add_test(NAME vector_preparation COMMAND vector_preparation_test ${runtime_metadata} ${runtime_publication})
  add_executable(vector_consumer_test tests/vector_consumer.cpp)
  target_link_libraries(vector_consumer_test PRIVATE scpp_runtime)
  add_test(NAME vector_consumer COMMAND vector_consumer_test)
  add_test(NAME vector_publication COMMAND ${CMAKE_COMMAND}
    -DMOLD_LINKER=${SCPP_MOLD_LINKER}
    -DGENERATOR=$<TARGET_FILE:scpp_api_generator>
    -DMETADATA=${runtime_metadata} -DPUBLICATION=${runtime_publication}
    -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
    -DSOURCE=${CMAKE_CURRENT_SOURCE_DIR}
    -DWORK=${CMAKE_CURRENT_BINARY_DIR}/vector-proof
    -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/vector_publication.cmake)
  add_executable(string_preparation_test tests/string_preparation.cpp)
  target_link_libraries(string_preparation_test PRIVATE scpp_api_generation)
  add_test(NAME string_preparation COMMAND string_preparation_test ${runtime_metadata} ${runtime_publication})
  add_executable(string_consumer_test tests/string_consumer.cpp)
  target_link_libraries(string_consumer_test PRIVATE scpp_runtime)
  add_test(NAME string_consumer COMMAND string_consumer_test)
  add_test(NAME string_publication COMMAND ${CMAKE_COMMAND}
    -DMOLD_LINKER=${SCPP_MOLD_LINKER}
    -DGENERATOR=$<TARGET_FILE:scpp_api_generator>
    -DMETADATA=${runtime_metadata} -DPUBLICATION=${runtime_publication}
    -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
    -DSOURCE=${CMAKE_CURRENT_SOURCE_DIR}
    -DWORK=${CMAKE_CURRENT_BINARY_DIR}/string-proof
    -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/string_publication.cmake)
  add_executable(sequence_semantics_test tests/sequence_semantics.cpp)
  target_include_directories(sequence_semantics_test PRIVATE ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/sequence)
  target_compile_features(sequence_semantics_test PRIVATE cxx_std_23)
  add_test(NAME sequence_semantics COMMAND sequence_semantics_test)
  add_executable(json_reader_test tests/json_reader.cpp)
  target_link_libraries(json_reader_test PRIVATE scpp_tool_json)
  add_test(NAME json_reader COMMAND json_reader_test)
  add_executable(metadata_value_test tests/metadata_value.cpp)
  target_link_libraries(metadata_value_test PRIVATE scpp_metadata_value scpp_tool_json)
  add_test(NAME metadata_value COMMAND metadata_value_test)
  add_executable(integer_preparation_test tests/integer_preparation.cpp)
  target_link_libraries(integer_preparation_test PRIVATE scpp_api_generation)
  add_test(NAME integer_preparation COMMAND integer_preparation_test ${runtime_metadata} ${runtime_publication})
  add_executable(value_preparation_test tests/value_preparation.cpp)
  target_link_libraries(value_preparation_test PRIVATE scpp_api_generation)
  add_test(NAME value_preparation COMMAND value_preparation_test ${runtime_metadata} ${runtime_publication})
  add_executable(floating_preparation_test tests/floating_preparation.cpp)
  target_link_libraries(floating_preparation_test PRIVATE scpp_api_generation)
  add_test(NAME floating_preparation COMMAND floating_preparation_test ${runtime_metadata} ${runtime_publication})
  add_executable(floating_consumer_test tests/floating_consumer.cpp)
  target_link_libraries(floating_consumer_test PRIVATE scpp_runtime)
  add_test(NAME floating_consumer COMMAND floating_consumer_test)
  add_executable(value_consumer_test tests/value_consumer.cpp)
  target_link_libraries(value_consumer_test PRIVATE scpp_runtime)
  add_test(NAME value_consumer COMMAND value_consumer_test)
  add_executable(integer_consumer_test tests/integer_consumer.cpp)
  target_link_libraries(integer_consumer_test PRIVATE scpp_runtime)
  add_test(NAME integer_consumer COMMAND integer_consumer_test)
  add_test(NAME runtime_freshness COMMAND scpp_api_generator
    ${runtime_metadata} ${runtime_publication} ${runtime_generated} --check)
  add_test(NAME runtime_generation COMMAND ${CMAKE_COMMAND}
    -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
    -DGENERATOR=$<TARGET_FILE:scpp_api_generator>
    -DMETADATA=${runtime_metadata} -DPUBLICATION=${runtime_publication}
    -DWORK=${CMAKE_CURRENT_BINARY_DIR}/generation-proof
    -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/runtime_generation.cmake)
  add_test(NAME runtime_public_boundary COMMAND ${CMAKE_COMMAND}
    -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
    -DGENERATED=${runtime_generated} -DWORK=${CMAKE_CURRENT_BINARY_DIR}/public-proof
    -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/runtime_public_boundary.cmake)
  add_test(NAME integer_full_publication COMMAND ${CMAKE_COMMAND}
    -DMOLD_LINKER=${SCPP_MOLD_LINKER}
    -DGENERATOR=$<TARGET_FILE:scpp_api_generator>
    -DMETADATA=${runtime_metadata} -DPUBLICATION=${runtime_publication}
    -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
    -DSOURCE=${CMAKE_CURRENT_SOURCE_DIR}
    -DWORK=${CMAKE_CURRENT_BINARY_DIR}/integer-full-proof
    -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/integer_full_publication.cmake)
  add_test(NAME native_operator_publication COMMAND ${CMAKE_COMMAND}
    -DGENERATOR=$<TARGET_FILE:scpp_api_generator>
    -DMETADATA=${runtime_metadata} -DPUBLICATION=${runtime_publication}
    -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
    -DWORK=${CMAKE_CURRENT_BINARY_DIR}/native-publication-proof
    -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/native_operator_publication.cmake)
endif()


if(BUILD_TESTING)
  add_subdirectory(tests/ownership)
endif()
