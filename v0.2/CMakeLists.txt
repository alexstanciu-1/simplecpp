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

# PHP prepares and renders runtime APIs; the C++ probe supplies target facts.
find_package(Python3 3.10 REQUIRED COMPONENTS Interpreter)
find_program(PHP_EXECUTABLE NAMES php8.5 php REQUIRED)
execute_process(COMMAND ${PHP_EXECUTABLE} -r "exit(PHP_VERSION_ID >= 80500 && PHP_INT_SIZE === 8 ? 0 : 1);"
  RESULT_VARIABLE php_supported)
if(NOT php_supported EQUAL 0)
  message(FATAL_ERROR "The API generator requires 64-bit PHP 8.5 or later")
endif()
set(php_generator_source ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/generate.php)
file(GLOB_RECURSE generator_sources CONFIGURE_DEPENDS
  ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/php/*.php
  ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/templates/*.cpp.tpl)
list(APPEND generator_sources ${php_generator_source})
set(api_tooling ${CMAKE_CURRENT_BINARY_DIR}/scpp-api-tooling)
configure_file(tools/api_generator/tooling.py.in ${api_tooling} @ONLY)
file(CHMOD ${api_tooling} PERMISSIONS OWNER_READ OWNER_WRITE OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
add_custom_target(scpp_api_tooling DEPENDS ${generator_sources} ${api_tooling})

# Always revalidate compiler/header context; the probe preserves unchanged files.
string(TOUPPER "${CMAKE_BUILD_TYPE}" probe_build_type)
separate_arguments(probe_flags NATIVE_COMMAND "${CMAKE_CXX_FLAGS} ${CMAKE_CXX_FLAGS_${probe_build_type}}")
set(probe_flag_args)
foreach(flag IN LISTS probe_flags)
  list(APPEND probe_flag_args "--flag=${flag}")
endforeach()
set(native_facts ${CMAKE_CURRENT_BINARY_DIR}/native-layout/facts.json)
set(native_context ${CMAKE_CURRENT_BINARY_DIR}/native-layout/context.json)
add_custom_target(scpp_layout_facts
  COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/tools/layout_probe/probe.py
    --cxx ${CMAKE_CXX_COMPILER} ${probe_flag_args} "--flag=--ld-path=${SCPP_MOLD_LINKER}"
    --cross-compiling ${CMAKE_CROSSCOMPILING} --out ${CMAKE_CURRENT_BINARY_DIR}/native-layout
  BYPRODUCTS ${native_facts} ${native_context}
  VERBATIM)
set(generator_launcher ${CMAKE_CURRENT_BINARY_DIR}/scpp_api_generator)
configure_file(tools/api_generator/launcher.py.in ${generator_launcher} @ONLY)
file(CHMOD ${generator_launcher} PERMISSIONS OWNER_READ OWNER_WRITE OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
add_custom_target(scpp_api_generator DEPENDS scpp_api_tooling scpp_layout_facts)

set(runtime_metadata ${CMAKE_CURRENT_SOURCE_DIR}/runtime/metadata/runtime.json)
include(cmake/RuntimePublication.cmake)
include(cmake/Datetime.cmake)
include(cmake/Json.cmake)
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
if(SCPP_ENABLE_DATETIME)
  list(APPEND runtime_outputs ${runtime_generated}/public/scpp/datetime.hpp ${runtime_generated}/src/datetime.cpp)
endif()
if(SCPP_ENABLE_JSON)
  list(APPEND runtime_outputs ${runtime_generated}/public/scpp/boxed.hpp ${runtime_generated}/src/boxed.cpp
    ${runtime_generated}/public/scpp/json.hpp ${runtime_generated}/src/json.cpp)
endif()
add_custom_command(OUTPUT ${runtime_outputs}
  COMMAND ${generator_launcher} ${runtime_metadata} ${runtime_publication} ${runtime_generated}
  DEPENDS scpp_api_generator ${generator_sources} ${api_tooling} ${native_facts} ${native_context} ${generator_launcher} ${runtime_metadata} ${runtime_publication}
  VERBATIM)
add_custom_target(scpp_runtime_publication DEPENDS ${runtime_outputs})
add_library(scpp_runtime STATIC ${runtime_generated}/src/runtime.cpp)
add_dependencies(scpp_runtime scpp_runtime_publication)
target_include_directories(scpp_runtime
  PUBLIC ${runtime_generated}/public
  PRIVATE ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/numeric ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/sequence)
target_compile_features(scpp_runtime PUBLIC cxx_std_23)

if(SCPP_ENABLE_DATETIME)
  scpp_add_datetime(scpp_datetime scpp_runtime ${runtime_generated})
  add_executable(scpp_datetime_demo examples/datetime_demo.cpp)
  target_link_libraries(scpp_datetime_demo PRIVATE scpp_datetime)
endif()

# Private storage shared by generated boxed APIs and module implementations.
add_library(scpp_packed_storage STATIC runtime/src/hash/packed_key.cpp)
target_compile_features(scpp_packed_storage PUBLIC cxx_std_23)
add_library(scpp_boxed_storage STATIC runtime/src/boxed/boxed_value.cpp runtime/src/boxed/boxed_conversion.cpp)
target_include_directories(scpp_boxed_storage PRIVATE runtime/src/hash)
target_link_libraries(scpp_boxed_storage PRIVATE scpp_packed_storage)
target_compile_features(scpp_boxed_storage PUBLIC cxx_std_23)

if(SCPP_ENABLE_JSON)
  target_sources(scpp_runtime PRIVATE ${runtime_generated}/src/boxed.cpp)
  target_include_directories(scpp_runtime PRIVATE
    ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/boxed ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/ownership)
  target_link_libraries(scpp_runtime PRIVATE scpp_boxed_storage)
  scpp_add_json(scpp_json scpp_runtime ${runtime_generated})
  add_executable(scpp_json_demo examples/json_demo.cpp)
  target_link_libraries(scpp_json_demo PRIVATE scpp_json)
endif()

add_executable(scpp_runtime_demo examples/runtime_demo.cpp)
target_link_libraries(scpp_runtime_demo PRIVATE scpp_runtime)

include(CTest)
if(BUILD_TESTING)
  # Wide-number regression consumers have their own explicit numeric publication.
  # Its generated directory is a sibling, never a child of another publication.
  set(numeric_proof_generated ${CMAKE_CURRENT_BINARY_DIR}/generated-numeric-proof)
  set(numeric_proof_publication ${CMAKE_CURRENT_SOURCE_DIR}/tests/generator_parity/numeric_publication.json)
  set(numeric_proof_outputs)
  foreach(path public/scpp/carriers.hpp public/scpp/vectors.hpp public/scpp/string.hpp
      public/scpp/values.hpp public/scpp/integers.hpp public/scpp/numeric.hpp
      public/scpp/native_bridge.hpp public/scpp/short_names.hpp src/runtime.cpp prepared.json inventory.json)
    list(APPEND numeric_proof_outputs ${numeric_proof_generated}/${path})
  endforeach()
  add_custom_command(OUTPUT ${numeric_proof_outputs}
    COMMAND ${generator_launcher} ${runtime_metadata} ${numeric_proof_publication} ${numeric_proof_generated}
    DEPENDS scpp_api_generator ${generator_sources} ${api_tooling} ${native_facts} ${native_context}
      ${generator_launcher} ${runtime_metadata} ${numeric_proof_publication} VERBATIM)
  add_library(numeric_proof_runtime STATIC ${numeric_proof_generated}/src/runtime.cpp)
  target_include_directories(numeric_proof_runtime PUBLIC ${numeric_proof_generated}/public
    PRIVATE ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/numeric ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/sequence)
  target_compile_features(numeric_proof_runtime PUBLIC cxx_std_23)
  add_executable(byte_consumer_test tests/byte_consumer.cpp)
  target_link_libraries(byte_consumer_test PRIVATE scpp_runtime)
  add_test(NAME byte_consumer COMMAND byte_consumer_test)
  add_test(NAME generator_cleanup COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/publication.php)
  add_test(NAME numeric_contract COMMAND ${PHP_EXECUTABLE}
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/numeric.php ${native_facts} ${native_context})
  add_test(NAME generator_input_contract COMMAND ${PHP_EXECUTABLE}
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/contracts.php ${native_facts} ${native_context})
  add_test(NAME generator_parity COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tests/generator_parity/parity.py
    --reference ${generator_launcher}
    --source ${CMAKE_CURRENT_SOURCE_DIR}
    --work ${CMAKE_CURRENT_BINARY_DIR}/generator-parity)
  add_test(NAME runtime_family_contract COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tests/generator_parity/runtime_parity.py
    --generator ${api_tooling} --facts ${native_facts} --context ${native_context}
    --work ${CMAKE_CURRENT_BINARY_DIR}/runtime-family-contract)
  add_test(NAME scalar_contract COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tests/generator_parity/scalar_parity.py
    --generator ${api_tooling} --facts ${native_facts} --context ${native_context}
    --work ${CMAKE_CURRENT_BINARY_DIR}/scalar-contract --emit
    --cxx ${CMAKE_CXX_COMPILER} --flag=-stdlib=libc++)
  add_test(NAME native_probe_contract COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/layout_probe/test_integration.py
    --generator ${api_tooling} --cxx ${CMAKE_CXX_COMPILER}
    --flag=-stdlib=libc++ --flag=--ld-path=${SCPP_MOLD_LINKER}
    --work ${CMAKE_CURRENT_BINARY_DIR}/probe-contract)
  add_test(NAME generator_parity_comparison COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tests/generator_parity/test_comparison.py)
  add_test(NAME carrier_publication COMMAND ${CMAKE_COMMAND}
    -DMOLD_LINKER=${SCPP_MOLD_LINKER}
    -DGENERATOR=${generator_launcher}
    -DMETADATA=${runtime_metadata} -DPUBLICATION=${numeric_proof_publication}
    -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
    -DSOURCE=${CMAKE_CURRENT_SOURCE_DIR}
    -DWORK=${CMAKE_CURRENT_BINARY_DIR}/carrier-proof
    -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/carrier_publication.cmake)
  add_executable(carrier_consumer_test tests/carrier_consumer.cpp)
  target_link_libraries(carrier_consumer_test PRIVATE numeric_proof_runtime)
  add_test(NAME carrier_consumer COMMAND carrier_consumer_test)
  add_test(NAME runtime_demo COMMAND scpp_runtime_demo)
  set_tests_properties(runtime_demo PROPERTIES PASS_REGULAR_EXPRESSION "Ada: 21; ratio=7")
  add_executable(vector_consumer_test tests/vector_consumer.cpp)
  target_link_libraries(vector_consumer_test PRIVATE numeric_proof_runtime)
  add_test(NAME vector_consumer COMMAND vector_consumer_test)
  add_test(NAME vector_publication COMMAND ${CMAKE_COMMAND}
    -DMOLD_LINKER=${SCPP_MOLD_LINKER}
    -DGENERATOR=${generator_launcher}
    -DMETADATA=${runtime_metadata} -DPUBLICATION=${numeric_proof_publication}
    -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
    -DSOURCE=${CMAKE_CURRENT_SOURCE_DIR}
    -DWORK=${CMAKE_CURRENT_BINARY_DIR}/vector-proof
    -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/vector_publication.cmake)
  add_executable(string_consumer_test tests/string_consumer.cpp)
  target_link_libraries(string_consumer_test PRIVATE numeric_proof_runtime)
  add_test(NAME string_consumer COMMAND string_consumer_test)
  add_test(NAME string_publication COMMAND ${CMAKE_COMMAND}
    -DMOLD_LINKER=${SCPP_MOLD_LINKER}
    -DGENERATOR=${generator_launcher}
    -DMETADATA=${runtime_metadata} -DPUBLICATION=${numeric_proof_publication}
    -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
    -DSOURCE=${CMAKE_CURRENT_SOURCE_DIR}
    -DWORK=${CMAKE_CURRENT_BINARY_DIR}/string-proof
    -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/string_publication.cmake)
  add_executable(sequence_semantics_test tests/sequence_semantics.cpp)
  target_include_directories(sequence_semantics_test PRIVATE ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/sequence)
  target_compile_features(sequence_semantics_test PRIVATE cxx_std_23)
  add_test(NAME sequence_semantics COMMAND sequence_semantics_test)
  add_executable(floating_consumer_test tests/floating_consumer.cpp)
  target_link_libraries(floating_consumer_test PRIVATE numeric_proof_runtime)
  add_test(NAME floating_consumer COMMAND floating_consumer_test)
  add_executable(value_consumer_test tests/value_consumer.cpp)
  target_link_libraries(value_consumer_test PRIVATE numeric_proof_runtime)
  add_test(NAME value_consumer COMMAND value_consumer_test)
  add_executable(integer_consumer_test tests/integer_consumer.cpp)
  target_link_libraries(integer_consumer_test PRIVATE numeric_proof_runtime)
  add_test(NAME integer_consumer COMMAND integer_consumer_test)
  add_test(NAME runtime_freshness COMMAND scpp_api_generator
    ${runtime_metadata} ${runtime_publication} ${runtime_generated} --check)
  add_test(NAME runtime_generation COMMAND ${CMAKE_COMMAND}
    -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
    -DGENERATOR=${generator_launcher}
    -DMETADATA=${runtime_metadata} -DPUBLICATION=${numeric_proof_publication}
    -DWORK=${CMAKE_CURRENT_BINARY_DIR}/generation-proof
    -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/runtime_generation.cmake)
  add_test(NAME runtime_public_boundary COMMAND ${CMAKE_COMMAND}
    -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
    -DGENERATED=${numeric_proof_generated} -DWORK=${CMAKE_CURRENT_BINARY_DIR}/public-proof
    -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/runtime_public_boundary.cmake)
  add_test(NAME integer_full_publication COMMAND ${CMAKE_COMMAND}
    -DMOLD_LINKER=${SCPP_MOLD_LINKER}
    -DGENERATOR=${generator_launcher}
    -DMETADATA=${runtime_metadata} -DPUBLICATION=${numeric_proof_publication}
    -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
    -DSOURCE=${CMAKE_CURRENT_SOURCE_DIR}
    -DWORK=${CMAKE_CURRENT_BINARY_DIR}/integer-full-proof
    -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/integer_full_publication.cmake)
  add_test(NAME native_operator_publication COMMAND ${CMAKE_COMMAND}
    -DGENERATOR=${generator_launcher}
    -DMETADATA=${runtime_metadata} -DPUBLICATION=${numeric_proof_publication}
    -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
    -DWORK=${CMAKE_CURRENT_BINARY_DIR}/native-publication-proof
    -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/native_operator_publication.cmake)
endif()


if(BUILD_TESTING)
  add_subdirectory(tests/ownership)
  add_subdirectory(tests/hash)
  add_subdirectory(tests/packed)
  add_subdirectory(tests/mixed)
  add_subdirectory(tests/dynamic)
  add_subdirectory(tests/boxed)
  add_subdirectory(tests/boxed_results)
  add_subdirectory(tests/json)
  add_subdirectory(tests/datetime)
endif()
