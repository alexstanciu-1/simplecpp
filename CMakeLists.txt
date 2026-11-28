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
unset(PHP_MT_EXECUTABLE CACHE)
if(PHP_EXECUTABLE STREQUAL "${CMAKE_CURRENT_SOURCE_DIR}/tools/php-mt")
  unset(PHP_EXECUTABLE CACHE)
endif()
find_program(PHP_EXECUTABLE NAMES php php8.5 REQUIRED)
execute_process(COMMAND ${PHP_EXECUTABLE} -r "exit(PHP_VERSION_ID >= 80500 && PHP_INT_SIZE === 8 ? 0 : 1);"
  RESULT_VARIABLE php_supported)
if(NOT php_supported EQUAL 0)
  message(FATAL_ERROR "v0.2 tools require 64-bit PHP 8.5+; set PHP_EXECUTABLE to a supported PHP binary")
endif()
set(php_generator_source ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/generate.php)
# Track PHP renderers and both template languages recursively across the owning-area folders.
file(GLOB_RECURSE generator_sources CONFIGURE_DEPENDS
  ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/php/*.php
  ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/templates/*.cpp.tpl
  ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/templates/*.php.tpl
  ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/templates/*.concrete.json)
list(APPEND generator_sources ${php_generator_source}
  ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/boxed/value_kinds.def
  ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/json/record_output.hpp
  ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/structure/record_fields.hpp)
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
  COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/tools/layout_probe/qualification.py
    --cxx ${CMAKE_CXX_COMPILER} ${probe_flag_args} --flag=-std=c++23 "--link-flag=--ld-path=${SCPP_MOLD_LINKER}"
    --cache ${CMAKE_CURRENT_BINARY_DIR}/native-qualification
  BYPRODUCTS ${native_facts} ${native_context}
  VERBATIM)
set(generator_launcher ${CMAKE_CURRENT_BINARY_DIR}/scpp_api_generator)
configure_file(tools/api_generator/launcher.py.in ${generator_launcher} @ONLY)
file(CHMOD ${generator_launcher} PERMISSIONS OWNER_READ OWNER_WRITE OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
add_custom_target(scpp_api_generator DEPENDS scpp_api_tooling scpp_layout_facts)

set(runtime_metadata ${CMAKE_CURRENT_SOURCE_DIR}/runtime/metadata/runtime.json)
include(cmake/RuntimePublication.cmake)
include(cmake/ProviderOutputs.cmake)
include(cmake/Datetime.cmake)
include(cmake/Json.cmake)
scpp_provider_outputs(runtime_outputs ${runtime_generated} ${runtime_publication})
string(JSON async_frames ERROR_VARIABLE async_error GET "${runtime_profile}" async_frames)
if(async_frames)
  list(APPEND runtime_outputs ${runtime_generated}/public/scpp/async/frame.hpp ${runtime_generated}/public/scpp/async_timer.hpp)
endif()
string(JSON mt_segments ERROR_VARIABLE mt_error GET "${runtime_profile}" mt_segments)
if(mt_segments)
  list(APPEND runtime_outputs ${runtime_generated}/public/scpp/mt_segment.hpp ${runtime_generated}/public/scpp/mt_segments.hpp)
endif()
foreach(helper argument_count arguments mixed_arguments)
  if("${runtime_generated}/public/scpp/cli/${helper}.hpp" IN_LIST runtime_outputs)
    list(APPEND runtime_outputs ${runtime_generated}/private/cli_context.hpp)
    break()
  endif()
endforeach()
list(APPEND runtime_outputs
  ${runtime_generated}/public/scpp/arithmetic.hpp
  ${runtime_generated}/public/scpp/owner_access.hpp
  ${runtime_generated}/public/scpp/fixed_array.hpp
  ${runtime_generated}/public/scpp/exception.hpp
  ${runtime_generated}/public/scpp/entry.hpp
  ${runtime_generated}/private/output_provider.hpp
  ${runtime_generated}/public/scpp/numeric.hpp
  ${runtime_generated}/public/scpp/short_names.hpp
  ${runtime_generated}/src/runtime.cpp
  ${runtime_generated}/prepared.json
  ${runtime_generated}/inventory.json)
string(JSON hash_publication_type ERROR_VARIABLE hash_publication_error TYPE "${runtime_profile}" hashes)
if(hash_publication_type STREQUAL "OBJECT")
  list(APPEND runtime_outputs ${runtime_generated}/src/hash.cpp)
endif()
if(SCPP_ENABLE_DATETIME)
  list(APPEND runtime_outputs ${runtime_generated}/public/scpp/datetime.hpp ${runtime_generated}/src/datetime.cpp)
  if("${runtime_generated}/public/scpp/datetime/php_strtotime.hpp" IN_LIST runtime_outputs)
    enable_language(C)
    set(datetime_php_strtotime ON)
    list(APPEND runtime_outputs ${runtime_generated}/src/php_strtotime.cpp)
  endif()
  if("${runtime_generated}/public/scpp/datetime/php_date.hpp" IN_LIST runtime_outputs)
    set(datetime_php_date ON)
    list(APPEND runtime_outputs ${runtime_generated}/src/php_date.cpp)
  endif()
endif()
string(JSON boxed_publication_type ERROR_VARIABLE boxed_publication_error TYPE "${runtime_profile}" boxed)
if(boxed_publication_type STREQUAL "OBJECT")
  list(APPEND runtime_outputs ${runtime_generated}/public/scpp/boxed.hpp ${runtime_generated}/src/boxed.cpp)
  string(JSON boxed_dispatch ERROR_VARIABLE boxed_dispatch_error GET "${runtime_profile}" boxed scalar_dispatch)
  if(boxed_dispatch)
    list(APPEND runtime_outputs ${runtime_generated}/public/scpp/boxed_kind.hpp ${runtime_generated}/public/scpp/boxed_key.hpp)
  endif()
endif()
if(SCPP_ENABLE_JSON)
  list(APPEND runtime_outputs
    ${runtime_generated}/public/scpp/json.hpp ${runtime_generated}/src/json.cpp)
endif()
if(SCPP_ENABLE_REGEX)
  list(APPEND runtime_outputs ${runtime_generated}/private/regex_provider.hpp ${runtime_generated}/private/regex_capture_bindings.hpp)
endif()
if(SCPP_ENABLE_FILESYSTEM)
  list(APPEND runtime_outputs ${runtime_generated}/private/filesystem_provider.hpp)
  string(JSON filesystem_locks ERROR_VARIABLE locks_error TYPE "${runtime_profile}" filesystem resources)
  if(filesystem_locks STREQUAL "OBJECT")
    list(APPEND runtime_outputs ${runtime_generated}/private/filesystem_locks_provider.hpp ${runtime_generated}/private/filesystem_locks_values.hpp)
  endif()
endif()
foreach(family IN ITEMS io process curl mysqli ui webview fcgi)
  string(TOUPPER "${family}" upper)
  if(SCPP_ENABLE_${upper})
    list(APPEND runtime_outputs "${runtime_generated}/private/${family}_provider.hpp" "${runtime_generated}/private/${family}_values.hpp")
  endif()
endforeach()
# This source-local publication may have been written by another build profile.
# Check its contents on every build; unchanged output keeps its timestamps.
add_custom_target(scpp_runtime_publication
  COMMAND ${CMAKE_COMMAND}
    -DGENERATOR=${generator_launcher} -DMETADATA=${runtime_metadata}
    -DPUBLICATION=${runtime_publication} -DOUTPUT=${runtime_generated}
    -P ${CMAKE_CURRENT_SOURCE_DIR}/cmake/PublishRuntime.cmake
  BYPRODUCTS ${runtime_outputs}
  DEPENDS scpp_api_generator ${generator_sources} ${api_tooling} ${native_facts} ${native_context} ${generator_launcher} ${runtime_metadata} ${runtime_publication}
  VERBATIM)
add_library(scpp_runtime STATIC runtime/src/sequence/fixed_array.cpp ${runtime_generated}/src/runtime.cpp runtime/src/output/output.cpp runtime/src/entry/entry.cpp runtime/src/ownership/owner_access.cpp runtime/src/numeric/arithmetic.cpp)
if("${runtime_generated}/public/scpp/string.hpp" IN_LIST runtime_outputs)
  target_sources(scpp_runtime PRIVATE runtime/src/entry/source_path.cpp)
endif()
if(mt_segments)
  find_package(Threads REQUIRED)
  target_sources(scpp_runtime PRIVATE runtime/src/mt/segments.cpp)
  target_link_libraries(scpp_runtime PUBLIC Threads::Threads)
endif()
if(hash_publication_type STREQUAL "OBJECT")
  target_sources(scpp_runtime PRIVATE ${runtime_generated}/src/hash.cpp)
endif()
add_dependencies(scpp_runtime scpp_runtime_publication)
# The PHP compiler consumes the same validated publication demand as the runtime.
# Runtime-only source exports intentionally omit the compiler tree.
if(EXISTS "${CMAKE_CURRENT_SOURCE_DIR}/compiler/boot.php")
  set(compiler_generated ${CMAKE_CURRENT_SOURCE_DIR}/compiler/generated)
  if(SCPP_ENABLE_DATETIME)
    set(compiler_optional_types ${compiler_generated}/types/uint64.php)
  endif()
  foreach(builder IN ITEMS string_parts_builder text_builder)
    if("${runtime_generated}/public/scpp/str/${builder}.hpp" IN_LIST runtime_outputs)
      list(APPEND compiler_optional_types ${compiler_generated}/types/${builder}.php)
    endif()
  endforeach()
  add_custom_target(scpp_compiler_publication ALL
    COMMAND ${CMAKE_COMMAND}
      -DPHP=${PHP_EXECUTABLE} -DGENERATOR=${php_generator_source}
      -DMETADATA=${runtime_metadata} -DPUBLICATION=${runtime_publication} -DFACTS=${native_facts} -DCONTEXT=${native_context}
      -DOUTPUT=${compiler_generated}
      -P ${CMAKE_CURRENT_SOURCE_DIR}/cmake/PublishCompiler.cmake
    BYPRODUCTS ${compiler_optional_types} ${compiler_generated}/types/int8.php ${compiler_generated}/types/int16.php ${compiler_generated}/types/int32.php ${compiler_generated}/types/uint16.php ${compiler_generated}/types/uint32.php ${compiler_generated}/types/int64.php ${compiler_generated}/types/null.php ${compiler_generated}/types/bool.php ${compiler_generated}/types/uint8.php ${compiler_generated}/types/float64.php ${compiler_generated}/types/string.php ${compiler_generated}/types/registry.php ${compiler_generated}/types/vectors.php ${compiler_generated}/types/carriers.php ${compiler_generated}/types/boxed.php ${compiler_generated}/types/hashes.php ${compiler_generated}/bindings/entry.php ${compiler_generated}/inventory.json
    DEPENDS scpp_layout_facts ${generator_sources} ${native_facts} ${native_context} ${runtime_metadata} ${runtime_publication}
    VERBATIM)
  add_dependencies(scpp_runtime scpp_compiler_publication)
endif()
target_include_directories(scpp_runtime
  PUBLIC ${runtime_generated}/public
  PRIVATE ${runtime_generated}/private ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/numeric ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/sequence ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/hash)
target_compile_features(scpp_runtime PUBLIC cxx_std_23)
if(async_frames)
  target_sources(scpp_runtime PRIVATE runtime/src/async/task.cpp)
endif()

if(SCPP_ENABLE_DATETIME)
  scpp_add_datetime(scpp_datetime scpp_runtime ${runtime_generated} "${datetime_php_date}" "${datetime_php_strtotime}")
  add_executable(scpp_datetime_demo examples/datetime_demo.cpp)
  target_link_libraries(scpp_datetime_demo PRIVATE scpp_datetime)
endif()

# Private storage shared by generated boxed APIs and module implementations.
add_library(scpp_packed_objects OBJECT runtime/src/hash/packed_key.cpp)
target_compile_features(scpp_packed_objects PRIVATE cxx_std_23)
add_library(scpp_packed_storage STATIC $<TARGET_OBJECTS:scpp_packed_objects>)
target_compile_features(scpp_packed_storage PUBLIC cxx_std_23)
add_library(scpp_boxed_objects OBJECT runtime/src/boxed/boxed_value.cpp runtime/src/boxed/boxed_conversion.cpp)
target_include_directories(scpp_boxed_objects PRIVATE runtime/src/hash)
target_compile_features(scpp_boxed_objects PRIVATE cxx_std_23)
add_library(scpp_boxed_storage STATIC $<TARGET_OBJECTS:scpp_boxed_objects>)
target_link_libraries(scpp_boxed_storage PRIVATE scpp_packed_storage)
target_compile_features(scpp_boxed_storage PUBLIC cxx_std_23)

# Application builders link one prebuilt runtime archive. Reuse the same private objects in proof archives.
if(boxed_publication_type STREQUAL "OBJECT")
  target_sources(scpp_runtime PRIVATE ${runtime_generated}/src/boxed.cpp
    $<TARGET_OBJECTS:scpp_boxed_objects> $<TARGET_OBJECTS:scpp_packed_objects>)
  target_include_directories(scpp_runtime PRIVATE
    ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/boxed ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/ownership)
endif()
if(SCPP_ENABLE_JSON)
  scpp_add_json(scpp_json scpp_runtime ${runtime_generated})
  add_executable(scpp_json_demo examples/json_demo.cpp)
  target_link_libraries(scpp_json_demo PRIVATE scpp_json)
endif()

if(SCPP_ENABLE_REGEX)
  find_path(PCRE2_INCLUDE_DIR pcre2.h REQUIRED)
  find_library(PCRE2_LIBRARY NAMES libpcre2-8.so REQUIRED)
  # The native app builder consumes module-owned file paths from regex.module.json.
  file(MAKE_DIRECTORY ${CMAKE_CURRENT_BINARY_DIR}/regex-deps)
  file(CREATE_LINK "${PCRE2_LIBRARY}" "${CMAKE_CURRENT_BINARY_DIR}/regex-deps/libpcre2-8.so" SYMBOLIC)
  add_library(scpp_regex STATIC runtime/src/regex/regex.cpp)
  target_link_libraries(scpp_regex PUBLIC scpp_runtime ${PCRE2_LIBRARY})
  target_include_directories(scpp_regex PRIVATE ${runtime_generated}/private ${PCRE2_INCLUDE_DIR})
endif()

if(SCPP_ENABLE_FILESYSTEM)
  add_library(scpp_filesystem STATIC runtime/src/filesystem/filesystem.cpp)
  if(TARGET scpp_compiler_publication)
    add_dependencies(scpp_filesystem scpp_compiler_publication)
  endif()
  if(filesystem_locks STREQUAL "OBJECT")
    target_sources(scpp_filesystem PRIVATE runtime/src/filesystem/file_lock.cpp)
  endif()
  target_link_libraries(scpp_filesystem PUBLIC scpp_runtime)
  target_include_directories(scpp_filesystem PRIVATE ${runtime_generated}/private runtime/src/sequence)
endif()

foreach(family IN ITEMS io process curl mysqli ui webview fcgi)
  string(TOUPPER "${family}" upper)
  if(SCPP_ENABLE_${upper})
    add_library(scpp_${family} STATIC runtime/src/${family}/${family}.cpp)
    target_link_libraries(scpp_${family} PUBLIC scpp_runtime)
    target_include_directories(scpp_${family} PRIVATE "${runtime_generated}/private" runtime/src/sequence)
  endif()
endforeach()

# The native application linker consumes these same selected shared objects.
function(scpp_attach_sdk family)
  find_package(PkgConfig REQUIRED)
  cmake_parse_arguments(SDK "" "" "PACKAGES;LIBRARIES" ${ARGN})
  pkg_check_modules(SDK_${family} REQUIRED IMPORTED_TARGET ${SDK_PACKAGES})
  target_link_libraries(scpp_${family} PRIVATE PkgConfig::SDK_${family})
  file(MAKE_DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}/${family}-deps")
  foreach(lib IN LISTS SDK_LIBRARIES)
    find_library(SCPP_LIB_${lib} NAMES lib${lib}.so REQUIRED)
    file(CREATE_LINK "${SCPP_LIB_${lib}}" "${CMAKE_CURRENT_BINARY_DIR}/${family}-deps/lib${lib}.so" SYMBOLIC)
  endforeach()
endfunction()
if(SCPP_ENABLE_UI)
  scpp_attach_sdk(ui PACKAGES gtk+-3.0 LIBRARIES gtk-3 gobject-2.0 glib-2.0)
endif()
if(SCPP_ENABLE_WEBVIEW)
  scpp_attach_sdk(webview PACKAGES "webkit2gtk-4.1>=2.40" json-glib-1.0 LIBRARIES webkit2gtk-4.1 javascriptcoregtk-4.1 json-glib-1.0 gio-2.0)
  target_link_libraries(scpp_webview PUBLIC scpp_ui)
endif()
if(SCPP_ENABLE_MYSQLI)
  scpp_attach_sdk(mysqli PACKAGES libmariadb LIBRARIES mariadb)
endif()
if(SCPP_ENABLE_FCGI)
  if(NOT CMAKE_SYSTEM_NAME STREQUAL "Linux")
    message(FATAL_ERROR "The FastCGI host requires Linux")
  endif()
  find_path(SCPP_FCGI_INCLUDE fcgiapp.h REQUIRED)
  find_library(SCPP_FCGI_LIBRARY NAMES libfcgi.so REQUIRED)
  target_include_directories(scpp_fcgi PRIVATE "${SCPP_FCGI_INCLUDE}")
  target_link_libraries(scpp_fcgi PRIVATE "${SCPP_FCGI_LIBRARY}")
  file(MAKE_DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}/fcgi-deps")
  file(CREATE_LINK "${SCPP_FCGI_LIBRARY}" "${CMAKE_CURRENT_BINARY_DIR}/fcgi-deps/libfcgi.so" SYMBOLIC)
endif()
if(SCPP_ENABLE_CURL)
  find_path(SCPP_CURL_INCLUDE curl/curl.h REQUIRED)
  find_library(SCPP_CURL_LIBRARY NAMES libcurl.so REQUIRED)
  file(MAKE_DIRECTORY "${CMAKE_CURRENT_BINARY_DIR}/curl-deps")
  file(CREATE_LINK "${SCPP_CURL_LIBRARY}" "${CMAKE_CURRENT_BINARY_DIR}/curl-deps/libcurl.so" SYMBOLIC)
  target_include_directories(scpp_curl PRIVATE "${SCPP_CURL_INCLUDE}")
  target_link_libraries(scpp_curl PRIVATE "${SCPP_CURL_LIBRARY}")
endif()
if(SCPP_ENABLE_PROCESS AND CMAKE_SYSTEM_NAME STREQUAL "Linux")
  find_package(Threads REQUIRED)
  target_link_libraries(scpp_process PRIVATE Threads::Threads)
endif()

if(SCPP_ENABLE_FILESYSTEM)
  # The translated compiler tools share a private numeric adapter.
  set(decimal_adapter ${CMAKE_CURRENT_BINARY_DIR}/binary64-adapter)
  add_library(scpp_compiler_binary64 STATIC compiler/06_native/binary64_literal.cpp
    compiler/examples/retained_frontend/socket/binary64_host.cpp)
  target_link_libraries(scpp_compiler_binary64 PRIVATE scpp_runtime)
  file(GENERATE OUTPUT "${decimal_adapter}/module.json" CONTENT
    "{\"name\":\"compiler-private-frontend\",\"type\":\"c++\",\"artifacts\":[\"$<TARGET_FILE:scpp_compiler_binary64>\"]}\n")
endif()

add_executable(scpp_runtime_demo examples/runtime_demo.cpp)
target_link_libraries(scpp_runtime_demo PRIVATE scpp_runtime)

if(CMAKE_SYSTEM_NAME STREQUAL "Linux" AND TARGET scpp_compiler_publication AND SCPP_ENABLE_FILESYSTEM AND SCPP_ENABLE_JSON AND SCPP_ENABLE_DATETIME)
  add_subdirectory(compiler/examples/retained_frontend/socket native-frontend)
endif()

include(CTest)
if(BUILD_TESTING)
  function(scpp_fixture_test feature)
    add_test(NAME compiler_${feature}_native COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings_native.py --fixture ${feature}.php --cases ${ARGN})
    set_property(GLOBAL APPEND PROPERTY scpp_full_fixture_tests ${feature})
  endfunction()

  # Wide-number regression consumers have their own explicit numeric publication.
  # Its generated directory is a sibling, never a child of another publication.
  set(numeric_proof_generated ${CMAKE_CURRENT_BINARY_DIR}/generated-numeric-proof)
  set(numeric_proof_publication ${CMAKE_CURRENT_SOURCE_DIR}/tests/generator_parity/numeric_publication.json)
  scpp_provider_outputs(numeric_proof_outputs ${numeric_proof_generated} ${numeric_proof_publication})
  foreach(path public/scpp/numeric.hpp
      public/scpp/short_names.hpp src/runtime.cpp prepared.json inventory.json)
    list(APPEND numeric_proof_outputs ${numeric_proof_generated}/${path})
  endforeach()
  add_custom_command(OUTPUT ${numeric_proof_outputs}
    COMMAND ${generator_launcher} ${runtime_metadata} ${numeric_proof_publication} ${numeric_proof_generated}
    DEPENDS scpp_api_generator ${generator_sources} ${api_tooling} ${native_facts} ${native_context}
      ${generator_launcher} ${runtime_metadata} ${numeric_proof_publication} VERBATIM)
  add_library(numeric_proof_runtime STATIC ${numeric_proof_generated}/src/runtime.cpp runtime/src/numeric/arithmetic.cpp)
  target_include_directories(numeric_proof_runtime PUBLIC ${numeric_proof_generated}/public
    PRIVATE ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/numeric ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/sequence)
  target_compile_features(numeric_proof_runtime PUBLIC cxx_std_23)
  set(checked_generated ${CMAKE_CURRENT_BINARY_DIR}/generated-checked-conversions)
  scpp_provider_outputs(checked_outputs ${checked_generated} ${CMAKE_CURRENT_SOURCE_DIR}/tests/checked_conversions_publication.json)
  foreach(path src/runtime.cpp prepared.json inventory.json
      public/scpp/numeric.hpp public/scpp/short_names.hpp
      public/scpp/arithmetic.hpp public/scpp/entry.hpp)
    list(APPEND checked_outputs ${checked_generated}/${path})
  endforeach()
  add_custom_command(OUTPUT ${checked_outputs}
    COMMAND ${generator_launcher} ${runtime_metadata}
      ${CMAKE_CURRENT_SOURCE_DIR}/tests/checked_conversions_publication.json ${checked_generated}
    DEPENDS scpp_api_generator ${generator_sources} ${native_facts} ${native_context}
      ${runtime_metadata} ${CMAKE_CURRENT_SOURCE_DIR}/tests/checked_conversions_publication.json VERBATIM)
  add_library(checked_conversion_runtime STATIC ${checked_generated}/src/runtime.cpp)
  target_include_directories(checked_conversion_runtime PUBLIC ${checked_generated}/public
    PRIVATE ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/numeric)
  target_compile_features(checked_conversion_runtime PUBLIC cxx_std_23)
  add_executable(checked_conversions_test tests/checked_conversions.cpp)
  target_link_libraries(checked_conversions_test PRIVATE checked_conversion_runtime)
  add_test(NAME checked_conversions COMMAND checked_conversions_test)
  add_test(NAME checked_conversion_generation COMMAND ${PHP_EXECUTABLE}
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/checked_conversions.php ${native_facts} ${native_context})
  add_executable(byte_consumer_test tests/byte_consumer.cpp)
  target_link_libraries(byte_consumer_test PRIVATE scpp_runtime)
  add_test(NAME byte_consumer COMMAND byte_consumer_test)
  add_test(NAME entry_runtime COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/tests/entry_runtime.py)
  add_test(NAME output_runtime COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/tests/output_runtime.py)
  add_test(NAME compiler_entry_binding COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/entry_binding.php)
  scpp_fixture_test(entry_binding php_proven php_checked)
  add_test(NAME compiler_cli_environment_native COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/cli_environment_native.py ${CMAKE_CXX_COMPILER})
  add_test(NAME compiler_launcher_native COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/launcher_native.py
    ${PHP_EXECUTABLE} ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER} $<TARGET_FILE:scpp_runtime>)
  set_tests_properties(compiler_entry_binding_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_entry COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/entry.php)
  add_test(NAME compiler_python_bridge_native COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/python_bridge_quick.py)
  add_test(NAME compiler_boxed_containers_native COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/boxed_containers_quick.py)
  add_test(NAME compiler_static_access_native COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/static_access_quick.py)
  add_test(NAME compiler_callback_native COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/callback_quick.py)
  add_test(NAME compiler_record_ownership_native COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/record_ownership_quick.py)
  add_test(NAME compiler_method_dispatch_native COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/dispatch_quick.py)
  add_test(NAME compiler_python_tuple_native COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/python_tuple_native.py --quick)
  add_test(NAME compiler_python_frontend COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/python_frontend.php)
  set_tests_properties(compiler_python_frontend PROPERTIES TIMEOUT 120 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_frontend COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/frontend.php)
  add_test(NAME compiler_native_build COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/native_build.py --quick)
  set_tests_properties(compiler_native_build PROPERTIES TIMEOUT 300 ENVIRONMENT "XDEBUG_MODE=off")
  if(UNIX)
    add_test(NAME compiler_bundle_publication_native COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/bundle_publication_native.py
      ${PHP_EXECUTABLE} ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER})
  endif()
  add_test(NAME compiler_style COMMAND ${CMAKE_COMMAND} -E env PHP_EXECUTABLE=${PHP_EXECUTABLE}
    ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tools/check_style.py)
  add_test(NAME compiler_scopes COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/scopes.php)
  add_test(NAME compiler_ast_file COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/ast_file.php)
  add_test(NAME compiler_list_initialization COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/list_initialization.php ${CMAKE_CURRENT_BINARY_DIR}/list-fixtures.json)
  add_test(NAME compiler_list_initialization_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings_native.py --fixture list_initialization.php --cases php-order-0 php-entry-failure php-strings)
  add_test(NAME compiler_list_initialization_ownership COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/list_initialization_ownership.py --out ${CMAKE_CURRENT_BINARY_DIR}/list-ownership --fixtures ${CMAKE_CURRENT_BINARY_DIR}/list-fixtures.json)
  add_test(NAME list_initialization_providers COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/list_initialization.py --quick --out ${CMAKE_CURRENT_BINARY_DIR}/list-providers)
  set_tests_properties(compiler_list_initialization_ownership PROPERTIES DEPENDS compiler_list_initialization)
  set_tests_properties(compiler_list_initialization_native compiler_list_initialization_ownership list_initialization_providers PROPERTIES TIMEOUT 300)
  add_test(NAME compiler_keyed_initialization COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/keyed_initialization.php ${CMAKE_CURRENT_BINARY_DIR}/keyed-fixtures.json)
  add_test(NAME compiler_keyed_initialization_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/keyed_initialization_quick.py)
  add_test(NAME compiler_keyed_initialization_ownership COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/keyed_initialization_ownership.py --quick --out ${CMAKE_CURRENT_BINARY_DIR}/keyed-ownership --fixtures ${CMAKE_CURRENT_BINARY_DIR}/keyed-fixtures.json)
  add_test(NAME compiler_bootstrap_runtime_lookup_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/bootstrap_runtime_lookup_native.py --quick)
  foreach(member_case properties methods constants construction)
    add_test(NAME compiler_incremental_member_${member_case} COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/production_member_incremental.py ${member_case} ${CMAKE_CXX_COMPILER} $<TARGET_FILE:scpp_runtime>)
  endforeach()
  add_test(NAME compiler_keyed_initialization_incremental COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/keyed_initialization_incremental.py --quick-runtime $<TARGET_FILE:scpp_runtime> --out ${CMAKE_CURRENT_BINARY_DIR}/keyed-incremental)
  add_test(NAME keyed_initialization_providers COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/keyed_initialization.py --quick --out ${CMAKE_CURRENT_BINARY_DIR}/keyed-providers)
  set_tests_properties(compiler_keyed_initialization_ownership PROPERTIES DEPENDS compiler_keyed_initialization)
  set_tests_properties(compiler_bootstrap_runtime_lookup_native compiler_keyed_initialization_native compiler_keyed_initialization_ownership keyed_initialization_providers PROPERTIES TIMEOUT 300)
  add_test(NAME compiler_populated_vectors COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/populated_vectors.php)
  add_test(NAME compiler_scoped_byte_view_native COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/scoped_byte_view_native.py ${native_facts} ${native_context} --quick)
  add_test(NAME compiler_last_use_transfer COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/last_use_transfer_quick.py)
  add_test(NAME compiler_collection_transfer COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/collection_transfer.py --quick)
  add_test(NAME compiler_destination_append COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/destination_append.py --quick-runtime $<TARGET_FILE:scpp_runtime>)
  add_test(NAME compiler_vector_operations COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/vector_operations.php)
  scpp_fixture_test(vector_operations php php-negative)
  add_test(NAME compiler_carrier_values COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/carrier_values.php)
  scpp_fixture_test(carrier_values php php-false-guards php-false-pairs php-nullable-pairs php-false-equality php-nullable-equality php-peer-failure)
  add_test(NAME compiler_bool_sentinel_equality_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings_native.py
    --fixture carrier_values.php --cases php-bool-sentinel-peers)
  add_test(NAME compiler_php_carrier_operators_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings_native.py
    --fixture php_carrier_operators.php --cases php arithmetic bitwise)
  add_test(NAME compiler_php_carrier_operator_failures_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings_native.py
    --fixture php_carrier_operators.php --cases ordering spaceship concat)
  add_test(NAME compiler_php_builtin_admission_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings_native.py
    --fixture php_builtin_admission.php --cases php)
  add_test(NAME compiler_php_reference_binding_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings_native.py
    --fixture php_reference_binding.php --cases php)
  add_test(NAME compiler_php_decimal_arguments COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings_native.py
    --fixture php_decimal_arguments.php --cases php)
  add_test(NAME compiler_php_carrier_implicit_matrix COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/php_carrier_implicit_matrix.php)
  add_test(NAME compiler_php_carrier_implicit_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings_native.py
    --fixture php_carrier_implicit.php --cases php)
  add_test(NAME compiler_php_carrier_casts_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings_native.py
    --fixture php_carrier_casts.php --cases php)
  add_test(NAME compiler_php_take_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings_native.py
    --fixture php_take.php --cases php)
  add_test(NAME compiler_php_carrier_writes_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings_native.py
    --fixture php_carrier_writes.php --cases php)
  add_test(NAME compiler_php_carrier_write_failures_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings_native.py
    --fixture php_carrier_writes.php --cases missing-0 missing-1 missing-2)
  add_test(NAME compiler_mixed_carrier_equality_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings_native.py
    --fixture mixed_carrier_equality.php --cases php)
  add_test(NAME compiler_carrier_participation_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings_native.py
    --fixture carrier_participation.php --cases php)
  add_test(NAME compiler_result_equality_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings_native.py
    --fixture carrier_values.php --cases php-result-peers)
  add_test(NAME compiler_boxed_scalars COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/boxed_scalars.php)
  add_test(NAME compiler_boxed_scalars_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings_native.py
    --fixture boxed_scalars.php --cases php-mixed php-dynamic-mutating php-mixed-bad-pair php-container-incremental-1)
  add_test(NAME compiler_hash_operations COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/hash_operations.php)
  scpp_fixture_test(hash_operations php php-missing)
  add_test(NAME compiler_hash_types COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/hash_types.php)
  scpp_fixture_test(hash_types php-0 php-2)
  add_test(NAME compiler_carrier_types COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/carrier_types.php)
  scpp_fixture_test(carrier_types php-0 php-2)
  add_test(NAME compiler_vector_types COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/vector_types.php)
  add_test(NAME compiler_vector_types_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/vector_types_native.py)
  add_test(NAME compiler_type_references COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/type_references.php)
  add_test(NAME compiler_ast_locations COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/ast_locations.php)
  add_test(NAME compiler_ast_preparation COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/ast_preparation.php)
  add_test(NAME compiler_declaration_facts COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/declaration_facts.php)
  add_test(NAME compiler_duplicates_cli COMMAND ${CMAKE_COMMAND} -E env PHP_EXECUTABLE=${PHP_EXECUTABLE}
    ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/duplicates_cli.py)
  add_test(NAME compiler_incremental COMMAND ${CMAKE_COMMAND} -E env PHP_EXECUTABLE=${PHP_EXECUTABLE}
    ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/incremental.py)
  add_test(NAME compiler_duplicates COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/duplicates.php)
  if(mt_segments)
    add_test(NAME compiler_mt_segments_native COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/mt_segments_native.py ${native_facts} $<TARGET_FILE:scpp_runtime> ${CMAKE_CXX_COMPILER} --quick)
    set(mt_contracts readonly_helper job_caps)
    if(hash_publication_type STREQUAL "OBJECT")
      list(APPEND mt_contracts hash_segments)
    endif()
    foreach(contract IN LISTS mt_contracts)
      add_test(NAME compiler_mt_${contract} COMMAND ${Python3_EXECUTABLE} -B
        ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/mt_segments_native.py ${native_facts} $<TARGET_FILE:scpp_runtime> ${CMAKE_CXX_COMPILER} --${contract})
    endforeach()
  endif()
  add_test(NAME compiler_functions COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/functions.php)
  add_test(NAME compiler_direct_lowering COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/direct_lowering.php)
  add_test(NAME compiler_lowering_catalog COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/lowering_catalog.php)
  scpp_fixture_test(direct_lowering php php_ordered_reference_alias php_ordered_final_conversion)
  set_tests_properties(compiler_direct_lowering_native PROPERTIES TIMEOUT 120)
  add_test(NAME compiler_mixed_ordering COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/mixed_ordering.php)
  scpp_fixture_test(mixed_ordering php)
  set_tests_properties(compiler_mixed_ordering_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_mixed_arithmetic COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/mixed_arithmetic.php)
  scpp_fixture_test(mixed_arithmetic php)
  set_tests_properties(compiler_mixed_arithmetic_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_mixed_compounds COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/mixed_compounds.php)
  scpp_fixture_test(mixed_compounds php)
  set_tests_properties(compiler_mixed_compounds_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_mixed_equality COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/mixed_equality.php)
  scpp_fixture_test(mixed_equality php)
  set_tests_properties(compiler_mixed_equality_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_string_equality COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/string_equality.php)
  scpp_fixture_test(string_equality php)
  set_tests_properties(compiler_string_equality_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_power COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/power.php)
  scpp_fixture_test(power php)
  set_tests_properties(compiler_power compiler_power_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_bitwise COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/bitwise.php)
  scpp_fixture_test(bitwise php)
  set_tests_properties(compiler_bitwise compiler_bitwise_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_integer_unary_updates COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/integer_unary_updates.php)
  scpp_fixture_test(integer_unary_updates php)
  set_tests_properties(compiler_integer_unary_updates compiler_integer_unary_updates_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_mixed_numeric_families COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/mixed_numeric_families.php)
  scpp_fixture_test(mixed_numeric_families php)
  set_tests_properties(compiler_mixed_numeric_families compiler_mixed_numeric_families_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_family_conversions COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/family_conversions.php)
  scpp_fixture_test(family_conversions php)
  set_tests_properties(compiler_family_conversions compiler_family_conversions_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_optional_integers COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/optional_integers.php)
  scpp_fixture_test(optional_integers php php_overflow_int8)
  set_tests_properties(compiler_optional_integers compiler_optional_integers_native PROPERTIES TIMEOUT 120)
  add_test(NAME compiler_enums COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/enums.php)
  scpp_fixture_test(enums php)
  set_tests_properties(compiler_enums compiler_enums_native PROPERTIES TIMEOUT 120)
  add_test(NAME compiler_enum_fields COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/enum_fields.php)
  scpp_fixture_test(enum_fields php_constructor)
  set_tests_properties(compiler_enum_fields compiler_enum_fields_native PROPERTIES TIMEOUT 120)
  add_test(NAME compiler_enum_defaults COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/enum_defaults.php)
  scpp_fixture_test(enum_defaults php_0 php_2)
  set_tests_properties(compiler_enum_defaults compiler_enum_defaults_native PROPERTIES TIMEOUT 120)
  add_test(NAME compiler_enum_constructor_defaults COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/enum_constructor_defaults.php)
  scpp_fixture_test(enum_constructor_defaults php_0 php_2)
  set_tests_properties(compiler_enum_constructor_defaults compiler_enum_constructor_defaults_native PROPERTIES TIMEOUT 120)
  add_test(NAME compiler_enum_from_value COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/enum_from_value.php)
  scpp_fixture_test(enum_from_value php_widths php_miss_1)
  set_tests_properties(compiler_enum_from_value compiler_enum_from_value_native PROPERTIES TIMEOUT 120)
  add_test(NAME compiler_enum_name COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/enum_name.php)
  scpp_fixture_test(enum_name php)
  set_tests_properties(compiler_enum_name compiler_enum_name_native PROPERTIES TIMEOUT 120)
  add_test(NAME compiler_enum_strings COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/enum_strings.php)
  scpp_fixture_test(enum_strings php)
  set_tests_properties(compiler_enum_strings compiler_enum_strings_native PROPERTIES TIMEOUT 120)
  add_test(NAME compiler_enum_methods COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/enum_methods.php)
  scpp_fixture_test(enum_methods php php_argument_replaces_receiver)
  set_tests_properties(compiler_enum_methods compiler_enum_methods_native PROPERTIES TIMEOUT 120)
  add_test(NAME compiler_application_collections_native COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/application_collections_quick.py)
  if(async_frames)
    add_test(NAME compiler_async_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/async_native.py --quick)
    add_test(NAME compiler_python_async_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/python_async_native.py)
  endif()
  add_test(NAME compiler_yield COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/yield.php)
  add_test(NAME compiler_yield_foreach COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/yield_foreach.php)
  scpp_fixture_test(yield_foreach php_keyed_0 php_clear_0)
  scpp_fixture_test(yield php_resume php_checked_cleanup)
  add_test(NAME compiler_iterator COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/iterator.php)
  scpp_fixture_test(iterator php php_keyed)
  add_test(NAME compiler_foreach COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/foreach.php)
  scpp_fixture_test(foreach php_pairs_0 php_replace_2)
  add_executable(collection_iteration_test tests/collection_iteration.cpp)
  target_link_libraries(collection_iteration_test PRIVATE scpp_runtime)
  target_include_directories(collection_iteration_test PRIVATE runtime/src/boxed runtime/src/hash runtime/src/ownership)
  add_test(NAME collection_iteration COMMAND collection_iteration_test)
  add_test(NAME compiler_collection_has COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/collection_has.php)
  scpp_fixture_test(collection_has php_vector_string_ php_hash_int_string_)
  set_tests_properties(compiler_collection_has compiler_collection_has_native PROPERTIES TIMEOUT 120)
  add_executable(collection_has_storage_test tests/collection_has_storage.cpp)
  target_link_libraries(collection_has_storage_test PRIVATE scpp_runtime)
  add_test(NAME collection_has_storage COMMAND collection_has_storage_test)
  add_test(NAME compiler_collection_remove COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/collection_remove.php)
  scpp_fixture_test(collection_remove php_vector_string_ php_hash_int_string_)
  set_tests_properties(compiler_collection_remove compiler_collection_remove_native PROPERTIES TIMEOUT 120)
  add_executable(collection_remove_storage_test tests/collection_remove_storage.cpp)
  target_link_libraries(collection_remove_storage_test PRIVATE scpp_runtime)
  add_test(NAME collection_remove_storage COMMAND collection_remove_storage_test)
  add_test(NAME compiler_mixed_predicates_native COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/mixed_predicates_native.py ${CMAKE_CXX_COMPILER})
  add_test(NAME compiler_stable_hash_native COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/stable_hash_native.py
    ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER} $<TARGET_FILE:scpp_runtime>)
  add_test(NAME compiler_text_helpers_native COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/text_helpers_native.py
    ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER} $<TARGET_FILE:scpp_runtime>)
  add_test(NAME compiler_collection_map COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/collection_map.py)
  if(SCPP_ENABLE_REGEX)
    set(checked_slot_types published)
  else()
    set(checked_slot_types application)
  endif()
  add_test(NAME compiler_checked_slots COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/checked_slots.py ${checked_slot_types})
  add_test(NAME compiler_indexed_entry_reads COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/indexed_entry_reads.py)
  add_test(NAME compiler_collection_subset COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/collection_subset.py)
  add_test(NAME compiler_collection_value_scan COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/collection_value_scan.py)
  add_test(NAME compiler_collection_transforms COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/collection_transforms.py
    ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER} $<TARGET_FILE:scpp_runtime>)
  add_test(NAME compiler_vector_capacity_family COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/vector_capacity_family.py
    ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER} $<TARGET_FILE:scpp_runtime>)
  add_test(NAME compiler_collection_clear COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/collection_clear.php)
  scpp_fixture_test(collection_clear php_vector_string_ php_hash_int_string_)
  set_tests_properties(compiler_collection_clear compiler_collection_clear_native PROPERTIES TIMEOUT 120)
  add_executable(collection_clear_storage_test tests/collection_clear_storage.cpp)
  target_link_libraries(collection_clear_storage_test PRIVATE scpp_runtime)
  add_test(NAME collection_clear_storage COMMAND collection_clear_storage_test)
  add_test(NAME compiler_indexed_updates COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/indexed_updates.php)
  scpp_fixture_test(indexed_updates php_hash_int_string_ php_vector_int__missing)
  set_tests_properties(compiler_indexed_updates compiler_indexed_updates_native PROPERTIES TIMEOUT 120)
  add_test(NAME compiler_indexed_compounds COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/indexed_compounds.php)
  scpp_fixture_test(indexed_compounds php_vector_int_ php_vector_int__failure_divide)
  set_tests_properties(compiler_indexed_compounds compiler_indexed_compounds_native PROPERTIES TIMEOUT 180)
  add_test(NAME compiler_collection_fields COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/collection_fields.php)
  scpp_fixture_test(collection_fields php_vector_int_ php_hash_int_string__missing)
  set_tests_properties(compiler_collection_fields compiler_collection_fields_native PROPERTIES TIMEOUT 180)
  add_test(NAME compiler_collection_references COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/collection_references.php)
  add_test(NAME compiler_collection_references_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/collection_references_native.py --quick)
  set_tests_properties(compiler_collection_references compiler_collection_references_native PROPERTIES TIMEOUT 180)
  add_test(NAME compiler_value_passing COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/value_passing.php)
  scpp_fixture_test(value_passing php_vector_string_ php_hash_int_string_)
  set_tests_properties(compiler_value_passing compiler_value_passing_native PROPERTIES TIMEOUT 180)
  add_test(NAME compiler_vector_copies COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/vector_copies.php)
  scpp_fixture_test(vector_copies php_int php_string)
  add_test(NAME compiler_data_record_codec_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/data_record_codec_native.py --quick --runtime $<TARGET_FILE:scpp_runtime>)
  foreach(flavor php cpp js py)
    add_test(NAME compiler_data_records_${flavor} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/data_records_native.py --quick --flavor ${flavor})
    set_tests_properties(compiler_data_records_${flavor} PROPERTIES TIMEOUT 120 ENVIRONMENT "XDEBUG_MODE=off")
  endforeach()
  add_test(NAME compiler_named_construction_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/named_construction_native.py --quick)
  set_tests_properties(compiler_named_construction_native PROPERTIES TIMEOUT 180 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_named_arguments_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/named_arguments_native.py --quick)
  set_tests_properties(compiler_named_arguments_native PROPERTIES TIMEOUT 120 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_undefined_key_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/undefined_key_native.py --quick)
  set_tests_properties(compiler_undefined_key_native PROPERTIES TIMEOUT 120 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_collection_append_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/collection_append_native.py --quick)
  set_tests_properties(compiler_collection_append_native PROPERTIES TIMEOUT 600 ENVIRONMENT "XDEBUG_MODE=off")
  if(boxed_publication_type STREQUAL "OBJECT")
    add_test(NAME compiler_indexed_descriptor_native COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/indexed_descriptor_native.py
      ${PHP_EXECUTABLE} ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER} $<TARGET_FILE:scpp_runtime>)
  endif()
  add_test(NAME compiler_collection_bootstrap_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/collection_bootstrap_native.py --quick)
  set_tests_properties(compiler_collection_bootstrap_native PROPERTIES TIMEOUT 600 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_boxed_record_casts COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/boxed_record_casts.py)
  add_test(NAME compiler_boxed_record_vectors COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/boxed_record_vectors.py)
  add_test(NAME compiler_boxed_record_decode COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/boxed_record_decode.py)
  add_test(NAME compiler_boxed_vector_casts COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/boxed_vector_casts.py)
  add_test(NAME compiler_boxed_collection_coverage COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/boxed_collection_coverage.py --quick)
  set_tests_properties(compiler_boxed_collection_coverage PROPERTIES TIMEOUT 180 ENVIRONMENT "XDEBUG_MODE=off")
  set_tests_properties(compiler_vector_copies compiler_vector_copies_native PROPERTIES TIMEOUT 120)
  add_test(NAME compiler_integer_compounds COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/integer_compounds.php)
  scpp_fixture_test(integer_compounds php)
  set_tests_properties(compiler_integer_compounds compiler_integer_compounds_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_integer_promotion COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/integer_promotion.php)
  scpp_fixture_test(integer_promotion php)
  set_tests_properties(compiler_integer_promotion compiler_integer_promotion_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_byte_alias COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/byte_alias.php)
  scpp_fixture_test(byte_alias php)
  set_tests_properties(compiler_byte_alias_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_float_updates COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/float_updates.php)
  scpp_fixture_test(float_updates php)
  set_tests_properties(compiler_float_updates_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_float_compounds COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/float_compounds.php)
  scpp_fixture_test(float_compounds php)
  set_tests_properties(compiler_float_compounds_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_float_arithmetic COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/float_arithmetic.php)
  add_test(NAME compiler_float_arithmetic_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/float_arithmetic_native.py)
  set_tests_properties(compiler_float_arithmetic_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_arithmetic COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/arithmetic.php)
  add_test(NAME compiler_logical COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/logical.php)
  add_test(NAME compiler_transfers COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/transfers.php)
  add_test(NAME compiler_for COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/for.php)
  add_test(NAME compiler_unary COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/unary.php)
  add_test(NAME compiler_unary_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/unary_native.py)
  set_tests_properties(compiler_unary_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_updates COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/updates.php)
  scpp_fixture_test(updates php)
  set_tests_properties(compiler_updates_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_compounds COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/compounds.php)
  scpp_fixture_test(compounds php)
  set_tests_properties(compiler_compounds_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_type_bindings COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/type_bindings.php)
  add_test(NAME compiler_casts COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/casts.php)
  scpp_fixture_test(casts php php_byte_range)
  set_tests_properties(compiler_casts_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_constants COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/constants.php)
  scpp_fixture_test(constants php)
  add_test(NAME compiler_computed_constants_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/computed_constants_native.py)
  set_tests_properties(compiler_constants_native PROPERTIES TIMEOUT 60)
  scpp_fixture_test(for php)
  set_tests_properties(compiler_for_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_transfers_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/transfers_native.py)
  set_tests_properties(compiler_transfers_native PROPERTIES TIMEOUT 60)
  scpp_fixture_test(logical php)
  set_tests_properties(compiler_logical_native PROPERTIES TIMEOUT 60)
  scpp_fixture_test(arithmetic php php_division_by_zero)
  set_tests_properties(compiler_arithmetic_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_arithmetic_argument_order COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/arithmetic_argument_order.py --quick --cxx ${CMAKE_CXX_COMPILER})
  add_test(NAME arithmetic_runtime COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/tests/arithmetic_runtime.py)
  add_test(NAME compiler_comparisons COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/comparisons.php)
  add_test(NAME compiler_comparisons_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/comparisons_native.py)
  set_tests_properties(compiler_comparisons_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_loops COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/loops.php)
  scpp_fixture_test(loops php)
  set_tests_properties(compiler_loops_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_truthiness_bindings COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/truthiness_bindings.php)
  add_test(NAME compiler_conditions COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/conditions.php)
  scpp_fixture_test(conditions php php_checked_assignment)
  add_test(NAME compiler_reference_parameters COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/reference_parameters.php)
  add_test(NAME compiler_count_reader_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/count_reader_native.py --quick)
  add_test(NAME compiler_readonly_parameters_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/readonly_parameters_native.py)
  add_test(NAME compiler_reference_parameters_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/reference_parameters_native.py --quick)
  add_test(NAME compiler_parameter_defaults COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/parameter_defaults.php)
  add_test(NAME compiler_parameter_defaults_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/parameter_defaults_native.py)
  add_test(NAME compiler_typed_functions COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/typed_functions.php)
  add_test(NAME compiler_typed_functions_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/typed_functions_native.py)
  add_test(NAME compiler_integer_radix_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/integer_radix_native.py --quick)
  add_test(NAME compiler_fixed_array_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/fixed_array_native.py --quick)
  if(SCPP_ENABLE_FCGI)
    find_program(SCPP_APACHE_EXECUTABLE NAMES apache2 httpd)
    find_path(SCPP_APACHE_MODULE_DIR mod_proxy_fcgi.so PATHS /usr/lib/apache2/modules /usr/lib64/httpd/modules /usr/lib/httpd/modules)
    if(SCPP_APACHE_EXECUTABLE AND SCPP_APACHE_MODULE_DIR)
      add_test(NAME compiler_fcgi_apache_native COMMAND ${Python3_EXECUTABLE} -B
        ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/fcgi_apache_native.py
        ${CMAKE_CXX_COMPILER} ${SCPP_APACHE_EXECUTABLE} ${SCPP_APACHE_MODULE_DIR})
      set_tests_properties(compiler_fcgi_apache_native PROPERTIES TIMEOUT 60)
    endif()
  endif()
  foreach(family IN ITEMS io process curl)
    string(TOUPPER "${family}" upper)
    if(SCPP_ENABLE_${upper})
      add_test(NAME compiler_${family}_headers COMMAND ${Python3_EXECUTABLE} -B
        ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/resource_modules_native.py ${CMAKE_CXX_COMPILER} headers ${family})
      add_test(NAME compiler_${family}_native COMMAND ${Python3_EXECUTABLE} -B
        ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/resource_modules_native.py ${CMAKE_CXX_COMPILER} ${family})
    endif()
  endforeach()
  foreach(family IN ITEMS mysqli ui webview)
    string(TOUPPER "${family}" upper)
    if(SCPP_ENABLE_${upper})
      add_test(NAME compiler_${family}_native COMMAND ${Python3_EXECUTABLE} -B
        ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/database_desktop_native.py ${family} ${CMAKE_CXX_COMPILER})
      add_test(NAME compiler_${family}_headers COMMAND ${Python3_EXECUTABLE} -B
        ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/resource_modules_native.py ${CMAKE_CXX_COMPILER} headers ${family})
    endif()
  endforeach()
  if(SCPP_ENABLE_CURL)
    add_test(NAME compiler_curl_http COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/resource_modules_native.py ${CMAKE_CXX_COMPILER} http)
  endif()
  if(SCPP_ENABLE_CURL AND SCPP_ENABLE_PROCESS AND SCPP_ENABLE_IO AND CMAKE_SYSTEM_NAME STREQUAL "Linux")
    add_test(NAME compiler_resource_modules_combined COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/resource_modules_native.py ${CMAKE_CXX_COMPILER} all)
  endif()
  if(SCPP_ENABLE_JSON)
    add_test(NAME compiler_json_native COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/json_native.py ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER})
  endif()
  if(SCPP_ENABLE_DATETIME)
    if(datetime_php_strtotime)
      add_test(NAME compiler_strtotime_native COMMAND ${Python3_EXECUTABLE} -B
        ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strtotime_native.py
        ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER} ${CMAKE_BINARY_DIR})
    endif()
    add_test(NAME compiler_datetime_native COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/datetime_native.py
      ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER} ${CMAKE_BINARY_DIR})
  endif()
  if(SCPP_ENABLE_REGEX)
    add_test(NAME compiler_regex_native COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/regex_native.py
      ${PHP_EXECUTABLE} ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER}
      ${CMAKE_CURRENT_BINARY_DIR})
  endif()
  # The bundled application manifests select archives from the default preset.
  if(CMAKE_BINARY_DIR STREQUAL "${CMAKE_SOURCE_DIR}/build/clang")
    if(SCPP_ENABLE_FILESYSTEM)
      add_test(NAME example_queue_report COMMAND ${Python3_EXECUTABLE} -B
        ${CMAKE_CURRENT_SOURCE_DIR}/examples/queue-report/check.py --configured)
    else()
      add_test(NAME example_queue_report_setup COMMAND ${Python3_EXECUTABLE} -B
        ${CMAKE_CURRENT_SOURCE_DIR}/examples/queue-report/check.py --check-disabled)
    endif()
  endif()
  if(SCPP_ENABLE_FILESYSTEM)
    if(CMAKE_SYSTEM_NAME STREQUAL "Linux" AND filesystem_locks STREQUAL "OBJECT")
      add_test(NAME compiler_filesystem_locks_native COMMAND ${Python3_EXECUTABLE} -B
        ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/filesystem_locks_native.py ${CMAKE_CXX_COMPILER})
      set_tests_properties(compiler_filesystem_locks_native PROPERTIES TIMEOUT 45)
    endif()
    add_test(NAME compiler_filesystem_native COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/filesystem_native.py
      ${PHP_EXECUTABLE} ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER}
      $<TARGET_FILE:scpp_filesystem> $<TARGET_FILE:scpp_runtime>)
    add_test(NAME compiler_filesystem_publication COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/filesystem_native.py
      ${PHP_EXECUTABLE} ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER}
      $<TARGET_FILE:scpp_filesystem> $<TARGET_FILE:scpp_runtime> --publication)
    if(UNIX)
      add_test(NAME compiler_filesystem_family COMMAND ${Python3_EXECUTABLE} -B
        ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/filesystem_family.py
        ${PHP_EXECUTABLE} ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER}
        $<TARGET_FILE:scpp_filesystem> $<TARGET_FILE:scpp_runtime>)
    endif()
    add_test(NAME compiler_translated_path COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_compiler_path.py ${decimal_adapter}
      ${PHP_EXECUTABLE} ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER}
      $<TARGET_FILE:scpp_filesystem> $<TARGET_FILE:scpp_runtime>)
    if(UNIX)
      add_test(NAME compiler_translated_publisher_cleanup COMMAND ${Python3_EXECUTABLE} -B
        ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_publisher_cleanup.py
        ${PHP_EXECUTABLE} ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER}
        $<TARGET_FILE:scpp_filesystem> $<TARGET_FILE:scpp_runtime>)
    endif()
  endif()
  add_test(NAME compiler_module_availability COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/module_availability.py)
  if(async_frames)
    set(async_fixture_dir ${CMAKE_CURRENT_BINARY_DIR}/async-native-fixture)
    set(async_fixture_headers)
    foreach(task int64 void string result_int64 tracked)
      list(APPEND async_fixture_headers ${async_fixture_dir}/tasks/${task}.hpp)
    endforeach()
    add_custom_command(OUTPUT ${async_fixture_headers}
      COMMAND ${CMAKE_COMMAND} -E make_directory ${async_fixture_dir}/tasks
      COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/tests/async_runtime/tasks.php
        ${runtime_metadata} ${runtime_publication} ${native_facts} ${native_context} ${async_fixture_dir}/tasks
      DEPENDS ${generator_sources} tests/async_runtime/tasks.php ${runtime_metadata} ${runtime_publication} ${native_facts} ${native_context}
      VERBATIM)
    add_custom_target(async_fixture_tasks DEPENDS ${async_fixture_headers})
    add_executable(async_runtime_test tests/async_runtime/caller.cpp tests/async_runtime/parent.cpp tests/async_runtime/child.cpp)
    target_link_libraries(async_runtime_test PRIVATE scpp_runtime)
    add_test(NAME async_runtime COMMAND async_runtime_test)
    add_executable(async_frame_boundary_test tests/async_runtime/frame_boundary.cpp)
    foreach(target async_runtime_test async_frame_boundary_test)
      add_dependencies(${target} async_fixture_tasks)
      target_include_directories(${target} PRIVATE ${async_fixture_dir})
    endforeach()
    target_include_directories(async_frame_boundary_test PRIVATE tests/async_runtime)
    target_link_libraries(async_frame_boundary_test PRIVATE scpp_runtime)
    add_test(NAME async_frame_boundary COMMAND async_frame_boundary_test)
    add_test(NAME async_header_isolation COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/tests/async_runtime/header_isolation.py
      ${CMAKE_CXX_COMPILER} ${runtime_generated}/public ${async_fixture_dir})
  endif()
  add_executable(compiler_fixed_array_storage_test compiler/tests/fixed_array_storage.cpp)
  target_compile_features(compiler_fixed_array_storage_test PRIVATE cxx_std_20)
  target_include_directories(compiler_fixed_array_storage_test PRIVATE ${runtime_generated}/public)
  target_link_libraries(compiler_fixed_array_storage_test PRIVATE scpp_runtime)
  add_test(NAME compiler_fixed_array_storage COMMAND compiler_fixed_array_storage_test)

  add_executable(compiler_scalar_result_test compiler/tests/scalar_result.cpp)
  target_compile_features(compiler_scalar_result_test PRIVATE cxx_std_20)
  add_test(NAME compiler_scalar_result_transport COMMAND compiler_scalar_result_test)
  add_test(NAME compiler_scalar_fact_edits COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/scalar_fact_edits.py --quick)
  add_test(NAME compiler_inspection_scalars COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/inspection_scalars.py)
  add_test(NAME compiler_constant_evaluation COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/constant_evaluation.php)
  add_test(NAME compiler_constant_fact_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/constant_fact_native.py)
  add_test(NAME compiler_constant_fact_cast_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/constant_fact_cast_native.py)
  add_test(NAME compiler_literal_families COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/literal_families.php)
  add_test(NAME compiler_declaration_policy COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/declaration_policy.php)
  add_test(NAME compiler_inference COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/inference.php)
  add_test(NAME compiler_inference_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/inference_native.py)
  add_test(NAME compiler_assignments COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/assignments.php)
  add_test(NAME compiler_assignments_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/assignments_native.py)
  add_test(NAME compiler_php_assignment_chain COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/assignment_chain.py php)
  add_test(NAME compiler_cpp_assignment_chain COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/assignment_chain.py cpp)
  add_test(NAME compiler_js_assignment_chain COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/assignment_chain.py js)
  add_test(NAME compiler_python_assignment_chain COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/python_assignment_chain.py)
  if(async_frames)
    add_test(NAME compiler_nested_append_await COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/nested_append.py await)
  endif()
  add_test(NAME compiler_deep_boxed_store_field_type_edit COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/deep_boxed_store.py field_type_edit)
  foreach(flavor php cpp js)
    add_test(NAME compiler_nested_boxed_mutation_${flavor} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/nested_boxed_mutation.py ${flavor})
    foreach(storage local field)
      add_test(NAME compiler_deep_boxed_mutation_${flavor}_${storage} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/deep_boxed_mutation.py ${flavor} ${storage})
    endforeach()
    add_test(NAME compiler_deep_boxed_append_${flavor} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/deep_boxed_store.py append ${flavor})
    add_test(NAME compiler_deep_boxed_store_${flavor} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/deep_boxed_store.py ${flavor})
    add_test(NAME compiler_nested_boxed_field_append_${flavor} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/nested_boxed_field_write.py append ${flavor})
    add_test(NAME compiler_nested_boxed_append_${flavor} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/nested_boxed_append.py ${flavor})
    add_test(NAME compiler_nested_boxed_write_${flavor} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/nested_boxed_write.py ${flavor})
  endforeach()
  foreach(family mixed dynamic)
    foreach(case missing removed)
      add_test(NAME compiler_deep_boxed_mutation_${family}_${case} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/deep_boxed_mutation.py ${family} ${case})
    endforeach()
    foreach(case missing scalar)
      add_test(NAME compiler_deep_boxed_append_${family}_${case} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/deep_boxed_store.py append ${family} ${case})
      add_test(NAME compiler_deep_boxed_store_${family}_${case} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/deep_boxed_store.py ${family} ${case})
    endforeach()
    foreach(case missing_leaf removed_leaf)
      add_test(NAME compiler_nested_boxed_field_compound_${family}_${case} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/nested_boxed_field_write.py ${case} ${family})
    endforeach()
    add_test(NAME compiler_nested_boxed_field_append_${family}_removed COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/nested_boxed_field_write.py removed ${family})
    foreach(case missing_parent missing_leaf removed)
      add_test(NAME compiler_nested_boxed_mutation_${family}_${case} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/nested_boxed_mutation.py ${family} ${case})
    endforeach()
    foreach(case removed null scalar)
      add_test(NAME compiler_nested_boxed_append_${family}_${case} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/nested_boxed_append.py ${family} ${case})
      add_test(NAME compiler_nested_boxed_write_${family}_${case} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/nested_boxed_write.py ${family} ${case})
    endforeach()
  endforeach()
  foreach(case php cpp js last_owner field_access)
    add_test(NAME compiler_nested_boxed_field_write_${case} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/nested_boxed_field_write.py ${case})
  endforeach()
  foreach(flavor php cpp js)
    foreach(case exercise missing)
      add_test(NAME compiler_dynamic_arrow_${case}_${flavor} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/dynamic_arrow.py ${case} ${flavor})
    endforeach()
  endforeach()
  foreach(case computed computed_write computed_admission computed_mutation computed_mutation_failure computed_nested computed_nested_missing
      computed_append computed_append_missing computed_nested_mutation computed_nested_mutation_failure computed_deep computed_deep_missing computed_field_store
      computed_field_mutation computed_field_readonly computed_field_missing)
    add_test(NAME compiler_dynamic_arrow_${case}_php COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/dynamic_arrow.py ${case} php)
  endforeach()
  foreach(effect replace_child replace_holder remove_leaf index_replace_present index_replace_missing)
    add_test(NAME compiler_php_dynamic_compound_${effect} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/indexed_dynamic_compound.py ${effect})
  endforeach()
  foreach(case await_replace await_remove await_both suspended_owner empty_callable throw_key throw_arg
      failed_store shallow_failure conversion_failure selection call_modes anchor bounds_string bounds_vector
      bounds_fixed bounds_hash terminal_void mixed_read grouped_absent grouped_present factory_match factory_ternary
      arithmetic_failure predicate_after_write qualified_receiver declared_match declared_ternary signatures_match signatures_ternary
      wrapper_coalesce handle_coalesce awaited_coalesce python_nested_write scalar_owner)
    add_test(NAME compiler_expression_path_${case} COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/expression_access_paths.py ${case})
  endforeach()
  add_test(NAME compiler_php_indexed_owner_target COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/indexed_owner_target.py)
  foreach(case replacement lifetime admission append mutations mutation_access missing_before missing_after missing_update hash_reinsert hash_missing hash_inline)
    add_test(NAME compiler_php_indexed_owner_${case} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/indexed_owner_target.py ${case})
  endforeach()
  foreach(case php cpp js ownership last_owner missing readonly)
    add_test(NAME compiler_nested_field_write_${case} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/nested_field_write.py ${case})
  endforeach()
  foreach(case php cpp js reinsert removed conversion)
    add_test(NAME compiler_nested_append_${case} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/nested_append.py ${case})
  endforeach()
  foreach(case php cpp js reinsert missing_parent missing_leaf removed_parent removed_leaf)
    add_test(NAME compiler_nested_hash_write_${case} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/nested_hash_write.py ${case})
  endforeach()
  foreach(case php cpp js replacement outer inner cleared compound_outer compound_inner compound_cleared middle_removed)
    add_test(NAME compiler_nested_vector_write_${case} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/nested_vector_write.py ${case})
  endforeach()
  add_test(NAME compiler_python_parallel_assignment COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/python_parallel_assignment.py --quick)
  add_test(NAME compiler_text_casts COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/text_casts.php)
  scpp_fixture_test(text_casts php php-mixed-invalid-0)
  add_test(NAME compiler_string_index COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/string_index.php)
  scpp_fixture_test(string_index php)
  add_test(NAME compiler_interpolation COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/interpolation.php)
  foreach(case fields order)
    add_test(NAME compiler_interpolation_php_${case} COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/interpolation_field_native.py ${case})
  endforeach()
  scpp_fixture_test(interpolation php php-keys php-typed-miss php-mixed-miss)
  add_test(NAME compiler_shared_upcasts_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/shared_upcasts_quick.py)
  set_tests_properties(compiler_shared_upcasts_native PROPERTIES TIMEOUT 300)
  add_test(NAME compiler_inline_adapters_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/inline_adapters_native.py --quick)
  set_tests_properties(compiler_inline_adapters_native PROPERTIES TIMEOUT 300)
  add_test(NAME compiler_called_methods_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/called_methods_native.py --quick)
  set_tests_properties(compiler_called_methods_native PROPERTIES TIMEOUT 180 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_expression_static_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/expression_static_native.py --quick)
  set_tests_properties(compiler_expression_static_native PROPERTIES TIMEOUT 180 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_readonly_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/readonly_native.py --quick)
  set_tests_properties(compiler_readonly_native PROPERTIES TIMEOUT 600 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_coalesce_assignment COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/coalesce_assignment.php)
  add_test(NAME compiler_coalesce_field_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/coalesce_field_native.py)
  add_test(NAME compiler_coalesce_hash_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/coalesce_hash_native.py)
  foreach(case IN ITEMS mixed dynamic null_dynamic)
    add_test(NAME compiler_coalesce_boxed_${case} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/coalesce_boxed_native.py ${case})
  endforeach()
  add_test(NAME compiler_coalesce_nested_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/coalesce_nested_native.py)
  add_test(NAME compiler_coalesce_deep_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/coalesce_nested_native.py deep)
  foreach(kind IN ITEMS hash vector)
    add_test(NAME compiler_coalesce_${kind}_field_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/coalesce_collection_field_native.py ${kind})
  endforeach()
  foreach(case IN ITEMS replace clear negative end empty)
    add_test(NAME compiler_coalesce_vector_${case} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/coalesce_vector_native.py ${case})
  endforeach()
  add_test(NAME compiler_coalesce_assignment_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/coalesce_assignment_native.py --quick)
  set_tests_properties(compiler_coalesce_assignment_native PROPERTIES TIMEOUT 300)
  add_test(NAME compiler_coalesce COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/coalesce.php)
  foreach(flavor cpp php js)
    foreach(pair integer empty handles)
      add_test(NAME compiler_coalesce_${flavor}_${pair} COMMAND ${Python3_EXECUTABLE} -B
        ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/coalesce_native.py --flavor ${flavor} --pair ${pair})
    endforeach()
  endforeach()
  add_test(NAME compiler_coalesce_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/coalesce_native.py --quick)
  set_tests_properties(compiler_coalesce_native PROPERTIES TIMEOUT 300)
  add_test(NAME compiler_shared_lookup_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/shared_lookup_native.py --quick)
  set_tests_properties(compiler_shared_lookup_native PROPERTIES TIMEOUT 180)
  add_test(NAME compiler_hash_find_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/hash_find_native.py --quick)
  set_tests_properties(compiler_hash_find_native PROPERTIES TIMEOUT 300)
  add_test(NAME compiler_hash_contains COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/hash_contains.php)
  add_test(NAME compiler_php_hash_isset COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/php_hash_isset.py)
  add_test(NAME compiler_hash_contains_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/hash_contains_native.py --quick)
  set_tests_properties(compiler_hash_contains_native PROPERTIES TIMEOUT 300)
  add_test(NAME compiler_match_conditions COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/match_conditions.php)
  add_test(NAME compiler_match_conditions_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/match_conditions_native.py --quick)
  set_tests_properties(compiler_match_conditions_native PROPERTIES TIMEOUT 300)
  add_test(NAME compiler_switch_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/switch_native.py --quick)
  set_tests_properties(compiler_switch_native PROPERTIES TIMEOUT 240)
  add_test(NAME compiler_string_label_extraction_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/string_label_extraction_native.py)
  set_tests_properties(compiler_string_label_extraction_native PROPERTIES TIMEOUT 120 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_typed_label_extraction_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/typed_label_extraction_native.py)
  set_tests_properties(compiler_typed_label_extraction_native PROPERTIES TIMEOUT 120 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_carrier_dispatch_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/carrier_dispatch_native.py)
  set_tests_properties(compiler_carrier_dispatch_native PROPERTIES TIMEOUT 120 ENVIRONMENT "XDEBUG_MODE=off")
  foreach(case named lifetime suspended dispatch admission chain indexed_callable indexed_callable_missing ordinary_suffix empty_intermediate suspended_suffix chained_indexed_callable ordinary_indexed_callable ordinary_indexed_empty mixed_chain mixed_failure mixed_suspended terminal_void terminal_void_empty terminal_void_suspended index_vector index_hash index_string_vector index_suspended index_owner index_admission enum_method enum_selected_case enum_properties enum_property_admission index_member_vector index_member_hash hash_vector vector_hash hash_vector_missing vector_hash_missing repeated_index_owner index_ordinary index_ordinary_failures index_ordinary_owner terminal_index_call_vector terminal_index_call_hash terminal_index_call_missing_vector terminal_index_call_missing_hash terminal_index_call_admission callable_field_absent callable_field_empty callable_field_recovery)
    add_test(NAME compiler_php_nullsafe_call_${case} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/php_nullsafe_call.py ${case})
  endforeach()
  foreach(case reads admission nominal index_key)
    add_test(NAME compiler_php_nullsafe_field_${case} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/php_nullsafe_field.py ${case})
  endforeach()
  foreach(case selection inferred suspended index_key)
    add_test(NAME compiler_php_short_ternary_${case} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/php_short_ternary.py ${case})
  endforeach()
  add_test(NAME compiler_ternary_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/ternary_native.py --quick)
  add_test(NAME compiler_match_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/match_native.py --quick)
  set_tests_properties(compiler_match_native PROPERTIES TIMEOUT 300)
  add_test(NAME compiler_staged_ownership_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/staged_ownership_native.py --quick)
  set_tests_properties(compiler_staged_ownership_native PROPERTIES TIMEOUT 120 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_duplicate_links_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/duplicate_links_native.py)
  set_tests_properties(compiler_duplicate_links_native PROPERTIES TIMEOUT 120 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_ast_parent_lifetime_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/ast_parent_lifetime_native.py --quick-runtime $<TARGET_FILE:scpp_runtime>)
  set_tests_properties(compiler_ast_parent_lifetime_native PROPERTIES TIMEOUT 300 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_ast_type_ownership_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/ast_type_ownership_native.py)
  add_test(NAME compiler_stringable_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/stringable_native.py)
  add_test(NAME compiler_interface_access_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/interface_access_quick.py)
  set_tests_properties(compiler_interface_access_native PROPERTIES TIMEOUT 300 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_object_access_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/object_access_native.py --quick-runtime $<TARGET_FILE:scpp_runtime>)
  set_tests_properties(compiler_object_access_native PROPERTIES TIMEOUT 300)
  add_test(NAME compiler_source_loader_callbacks_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/source_loader_callbacks_native.py --quick)
  set_tests_properties(compiler_source_loader_callbacks_native PROPERTIES TIMEOUT 180 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_lambda_parser_state COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/lambda_parser_state.php)
  add_test(NAME compiler_static_owned_callables_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/static_owned_callables_native.py --quick)
  set_tests_properties(compiler_static_owned_callables_native PROPERTIES TIMEOUT 120 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_instance_owned_callables_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/instance_owned_callables_native.py --quick)
  set_tests_properties(compiler_instance_owned_callables_native PROPERTIES TIMEOUT 120 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_owned_callable_fields_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/owned_callable_fields_native.py --quick)
  set_tests_properties(compiler_owned_callable_fields_native PROPERTIES TIMEOUT 120 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_absent_callable_native COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/static_callable_properties_native.py --absent)
  add_test(NAME compiler_static_callable_properties_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/static_callable_properties_native.py --quick)
  set_tests_properties(compiler_static_callable_properties_native PROPERTIES TIMEOUT 120 ENVIRONMENT "XDEBUG_MODE=off")
  foreach(flavor cpp js py)
    foreach(case selection missing empty)
      add_test(NAME compiler_indexed_call_${flavor}_${case} COMMAND ${Python3_EXECUTABLE} -B
        ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/flavor_indexed_call.py ${flavor} ${case})
    endforeach()
  endforeach()
  foreach(case selection references local_arguments factory_arguments absent suspended ternary match)
    add_test(NAME compiler_php_callable_expression_${case} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/php_callable_expression.py ${case})
  endforeach()
  add_test(NAME compiler_erased_reference_returns_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/erased_reference_returns_native.py --quick)
  set_tests_properties(compiler_erased_reference_returns_native PROPERTIES TIMEOUT 120 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_closure_reference_returns_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/closure_reference_returns_native.py)
  set_tests_properties(compiler_closure_reference_returns_native PROPERTIES TIMEOUT 120 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_callable_reference_inputs_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/callable_reference_inputs_native.py --quick)
  set_tests_properties(compiler_callable_reference_inputs_native PROPERTIES TIMEOUT 120 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_callable_collections_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/callable_collections_native.py --quick)
  foreach(selection direct fallback)
    add_test(NAME compiler_php_indexed_callable_${selection} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/callable_collections_native.py --nullsafe-index ${selection})
  endforeach()
  foreach(selection present missing)
    add_test(NAME compiler_php_indexed_callable_awaited_${selection} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/callable_collections_native.py --awaited-argument ${selection})
  endforeach()
  set_tests_properties(compiler_callable_collections_native PROPERTIES TIMEOUT 120 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_signature_dependency_visitor COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/signature_dependency_visitor.php)
  add_test(NAME compiler_nullable_scalar_fields_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/nullable_scalar_fields_native.py)
  set_tests_properties(compiler_nullable_scalar_fields_native PROPERTIES TIMEOUT 120 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_nullable_string_fields_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/nullable_string_fields_native.py)
  set_tests_properties(compiler_nullable_string_fields_native PROPERTIES TIMEOUT 120 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_bootstrap_donor_verification_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/bootstrap_donor_verification_native.py --quick)
  set_tests_properties(compiler_bootstrap_donor_verification_native PROPERTIES TIMEOUT 180 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_named_callbacks_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/named_callbacks_native.py --quick)
  set_tests_properties(compiler_named_callbacks_native PROPERTIES TIMEOUT 180 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_translated_preparation_recovery COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_preparation_recovery.py)
  add_test(NAME compiler_translated_runtime_descriptor COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_runtime_descriptor.py)
  add_test(NAME compiler_translated_construction COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_construction.py)
  add_test(NAME compiler_translated_blocking_wait COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_blocking_wait.py)
  add_test(NAME compiler_translated_closure_dependencies COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_closure_dependencies.py)
  if(CMAKE_SYSTEM_NAME STREQUAL "Linux" AND SCPP_ENABLE_FILESYSTEM)
    add_test(NAME compiler_native_frontend_socket COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/native_frontend_socket.py ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER})
  endif()
  if(CMAKE_SYSTEM_NAME STREQUAL "Linux" AND SCPP_ENABLE_FILESYSTEM AND SCPP_ENABLE_JSON AND SCPP_ENABLE_DATETIME)
    add_test(NAME compiler_native_toolchain_qualification COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/native_toolchain_qualification.py ${CMAKE_CURRENT_BINARY_DIR}/native-frontend/native/program ${CMAKE_CXX_COMPILER})
    add_test(NAME compiler_native_ninja_publication COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/native_ninja_publication.py ${CMAKE_CURRENT_BINARY_DIR}/native-frontend/native/program ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER})
    add_test(NAME compiler_native_link_inputs COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/native_link_inputs.py ${CMAKE_CURRENT_BINARY_DIR}/native-frontend/native/program)
    add_test(NAME compiler_native_manifest_replacement COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/native_manifest_replacement.py ${CMAKE_CURRENT_BINARY_DIR}/native-frontend/native/program ${CMAKE_CXX_COMPILER})
    add_test(NAME compiler_native_decimal_literal COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/native_decimal_literal.py ${CMAKE_CURRENT_BINARY_DIR}/native-frontend/native/program ${PHP_EXECUTABLE})
    add_test(NAME compiler_native_module_graph COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/native_module_graph.py ${CMAKE_CURRENT_BINARY_DIR}/native-frontend/native/program)
    add_test(NAME compiler_native_source_inventory COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/native_source_inventory.py ${CMAKE_CURRENT_BINARY_DIR}/native-frontend/native/program)
    add_test(NAME compiler_native_frontend_protocol COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/native_frontend_protocol.py ${CMAKE_CURRENT_BINARY_DIR}/native-frontend/native/program)
  endif()
  if(CMAKE_SYSTEM_NAME STREQUAL "Linux" AND SCPP_ENABLE_FILESYSTEM AND SCPP_ENABLE_IO)
    add_test(NAME compiler_translated_retained_frontend COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_retained_frontend.py ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER} ${decimal_adapter})
    add_test(NAME compiler_retained_checked_return COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_retained_frontend.py ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER} ${decimal_adapter} --checked-return)
    add_test(NAME compiler_retained_literal_returns COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_retained_frontend.py ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER} ${decimal_adapter} --literal-returns)
    add_test(NAME compiler_retained_radix_returns COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_retained_frontend.py ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER} ${decimal_adapter} --radix-returns)
    add_test(NAME compiler_retained_parameter_incremental COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_retained_frontend.py ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER} ${decimal_adapter} --parameter-incremental)
    add_test(NAME compiler_retained_parameter_forwarding COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_retained_frontend.py ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER} ${decimal_adapter} --parameter-forwarding)
    add_test(NAME compiler_retained_multiple_parameters COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_retained_frontend.py ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER} ${decimal_adapter} --multiple-parameters)
    add_test(NAME compiler_retained_named_arguments COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_retained_frontend.py ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER} ${decimal_adapter} --named-arguments)
    add_test(NAME compiler_retained_numeric_defaults COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_retained_frontend.py ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER} ${decimal_adapter} --numeric-defaults)
    add_test(NAME compiler_retained_default_rebinding COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_retained_frontend.py ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER} ${decimal_adapter} --default-rebinding)
    add_test(NAME compiler_retained_call_retargeting COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_retained_frontend.py ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER} ${decimal_adapter} --call-retargeting)
    foreach(sequence shared zero ownership publication entry)
      add_test(NAME compiler_retained_edits_${sequence} COMMAND ${Python3_EXECUTABLE} -B
        ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_retained_frontend.py ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER} ${decimal_adapter} --edit-sequence=${sequence})
    endforeach()
    add_test(NAME compiler_retained_missing_call_repair COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_retained_frontend.py ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER} ${decimal_adapter} --missing-call-repair)
  endif()
  if(CMAKE_SYSTEM_NAME STREQUAL "Linux" AND SCPP_ENABLE_PROCESS AND SCPP_ENABLE_FILESYSTEM
     AND SCPP_ENABLE_JSON AND SCPP_ENABLE_IO AND SCPP_ENABLE_DATETIME)
    add_test(NAME compiler_translated_native_ninja COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_native_ninja.py ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER})
    add_test(NAME compiler_translated_native_driver COMMAND ${Python3_EXECUTABLE} -B
      ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_native_driver.py ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER})
  endif()
  add_test(NAME compiler_translated_vector_recipe COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_vector_recipe.py ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER})
  add_test(NAME compiler_translated_punctuation COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_punctuation.py)
  add_test(NAME compiler_translated_token_capacity COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_token_capacity.py)
  add_test(NAME compiler_translated_quotes COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_quotes.py ${CMAKE_CXX_COMPILER} ${SCPP_MOLD_LINKER})
  add_test(NAME compiler_translated_source_utf8 COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_source_utf8.py)
  add_test(NAME compiler_translated_callable_signatures COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/translated_callable_signatures.py)
  set_tests_properties(compiler_translated_closure_dependencies compiler_translated_callable_signatures PROPERTIES TIMEOUT 60 ENVIRONMENT "XDEBUG_MODE=off")
  set_tests_properties(compiler_translated_preparation_recovery compiler_translated_runtime_descriptor compiler_translated_construction compiler_translated_blocking_wait PROPERTIES TIMEOUT 120 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_failure_expressions_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/failure_expressions_native.py --quick)
  set_tests_properties(compiler_failure_expressions_native PROPERTIES TIMEOUT 120 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_finally_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/finally_native.py --quick)
  set_tests_properties(compiler_finally_native PROPERTIES TIMEOUT 120 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_exceptions_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/exceptions_native.py --quick)
  set_tests_properties(compiler_exceptions_native PROPERTIES TIMEOUT 120 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_required_fields_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/required_fields_native.py --quick)
  set_tests_properties(compiler_required_fields_native PROPERTIES TIMEOUT 300 ENVIRONMENT "XDEBUG_MODE=off")
  add_test(NAME compiler_struct_constants_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/struct_constants_native.py --quick)
  add_test(NAME compiler_clean_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/clean_native.py)
  add_test(NAME compiler_unset_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/unset_native.py)
  add_test(NAME compiler_weak_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/weak_native.py --quick)
  add_test(NAME compiler_shared_references_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/shared_references_native.py --quick)
  add_test(NAME compiler_app_namespace_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/app_namespace_native.py --quick)
  add_test(NAME compiler_strlen COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strlen.php)
  scpp_fixture_test(strlen php php-split-join php-count-window)
  add_test(NAME compiler_php_source_paths COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/php_source_paths.py)
  add_test(NAME compiler_php_htmlspecialchars COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/php_htmlspecialchars.py)
  add_test(NAME compiler_php_string_search_slices COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/php_string_search_slices.py)
  add_test(NAME compiler_strings_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings_native.py)
  add_test(NAME compiler_strings COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings.php)
  add_test(NAME compiler_echo_chain COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/echo_chain.php)
  scpp_fixture_test(echo_chain php_order php_failure)
  set_tests_properties(compiler_echo_chain_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_output COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/output.php)
  add_test(NAME compiler_modules COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/modules.php)
  add_test(NAME compiler_source_changes COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/source_changes.php)
  add_test(NAME compiler_loading_plumbing COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/loading_plumbing.php)
  add_test(NAME compiler_input_records_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/input_records_native.py --quick)
  add_test(NAME compiler_global_declaration_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/global_declaration_native.py)
  add_test(NAME compiler_escape_selection_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/escape_selection_native.py)
  add_test(NAME compiler_string_scanner_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/string_scanner_native.py)
  add_test(NAME compiler_cpp_literal_bytes_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/cpp_literal_bytes_native.py)
  add_test(NAME compiler_runtime_operand_projection_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/runtime_operand_projection_native.py --quick)
  add_test(NAME compiler_project COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/project_compiler.php)
  add_test(NAME compiler_incremental_publication COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/incremental_publication.php)
  add_test(NAME compiler_unit_headers_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/unit_headers_native.py)
  set_tests_properties(compiler_unit_headers_native PROPERTIES TIMEOUT 90)
  add_test(NAME compiler_incremental_executable_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/incremental_executable_native.py --quick)
  set_tests_properties(compiler_incremental_executable_native PROPERTIES TIMEOUT 90)
  add_test(NAME compiler_incremental_frontend COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/incremental_frontend.php)
  add_test(NAME compiler_preparation_work COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/preparation_work.php)
  add_test(NAME compiler_incremental_preparation COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/incremental_preparation.php)
  add_test(NAME compiler_grouped_observers COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/grouped_observers.php)
  add_test(NAME compiler_publication_gates COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/retention/gates.php)
  add_test(NAME compiler_symbol_retirement COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/retention/retirement.php)
  add_test(NAME compiler_failure_cleanup COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/retention/failures.php)
  add_test(NAME compiler_incremental_equivalence COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/retention/equivalence.py --quick)
  set_tests_properties(compiler_incremental_equivalence PROPERTIES TIMEOUT 120 ENVIRONMENT "PHP_EXECUTABLE=${PHP_EXECUTABLE}")
  add_test(NAME compiler_dependency_watches COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/dependency_watches.php)
  add_test(NAME compiler_namespaces COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/namespaces.php)
  add_test(NAME compiler_imports COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/imports.php)
  add_test(NAME compiler_globals COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/globals.php)
  add_test(NAME compiler_scalars_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/scalars_native.py)
  add_test(NAME compiler_floating_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/floating_native.py)
  add_test(NAME compiler_floating COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/floating.php)
  add_test(NAME compiler_scalars COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/scalars.php)
  add_test(NAME compiler_types COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/types.php)
  add_test(NAME compiler_frontend_service COMMAND ${CMAKE_COMMAND} -E env PHP_EXECUTABLE=${PHP_EXECUTABLE}
    ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/frontend_service.py --quick)
  add_test(NAME compiler_frontend_service_idle COMMAND ${CMAKE_COMMAND} -E env PHP_EXECUTABLE=${PHP_EXECUTABLE}
    ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/frontend_service.py --idle-only)
  set_tests_properties(compiler_frontend_service compiler_frontend_service_idle PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_manifests COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/manifests.php)
  add_test(NAME compiler_program COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/program.php)
  add_test(NAME generator_cleanup COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/publication.php)
  add_test(NAME compiler_lowering_generation COMMAND ${PHP_EXECUTABLE}
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/compiler.php ${native_facts} ${native_context})
  add_test(NAME collection_access_contracts COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/collection_access.py)
  add_test(NAME collection_argument_targets COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/collection_arguments.py ${native_facts} ${native_context})
  add_test(NAME iterator_contract_metadata COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/iterator_contract.py)
  add_test(NAME hash_provider_exports COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/hash_providers.py --quick)
  add_test(NAME carrier_provider_exports COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/carrier_providers.py)
  add_test(NAME vector_provider_exports COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/vector_providers.py --quick)
  add_test(NAME runtime_provider_application_cache COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/submodules.py --cache-only)
  add_test(NAME runtime_submodule_providers COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/submodules.py --quick)
  add_test(NAME numeric_contract COMMAND ${PHP_EXECUTABLE}
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/numeric.php ${native_facts} ${native_context})
  add_test(NAME generator_input_contract COMMAND ${PHP_EXECUTABLE}
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/contracts.php ${native_facts} ${native_context})
  add_test(NAME generator_parity COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tests/generator_parity/parity.py --quick
    --reference ${generator_launcher}
    --source ${CMAKE_CURRENT_SOURCE_DIR}
    --work ${CMAKE_CURRENT_BINARY_DIR}/generator-parity)
  add_test(NAME runtime_family_contract COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tests/generator_parity/runtime_parity.py --quick
    --generator ${api_tooling} --facts ${native_facts} --context ${native_context}
    --work ${CMAKE_CURRENT_BINARY_DIR}/runtime-family-contract)
  add_test(NAME scalar_contract COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tests/generator_parity/scalar_parity.py --quick
    --generator ${api_tooling} --facts ${native_facts} --context ${native_context}
    --work ${CMAKE_CURRENT_BINARY_DIR}/scalar-contract --emit
    --cxx ${CMAKE_CXX_COMPILER} --flag=-stdlib=libc++)
  add_test(NAME native_qualification_cache COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/layout_probe/test_qualification.py --quick --cxx ${CMAKE_CXX_COMPILER})
  add_test(NAME native_probe_contract COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/layout_probe/test_integration.py --seed-cache ${CMAKE_CURRENT_BINARY_DIR}/native-layout
    --generator ${api_tooling} --cxx ${CMAKE_CXX_COMPILER}
    ${probe_flag_args} --flag=--ld-path=${SCPP_MOLD_LINKER}
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
  add_test(NAME concrete_templates COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/concrete.py --quick)
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
  add_test(NAME scalar_alias_publication COMMAND ${CMAKE_COMMAND}
    -DGENERATOR=${generator_launcher}
    -DMETADATA=${runtime_metadata} -DPUBLICATION=${numeric_proof_publication}
    -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
    -DWORK=${CMAKE_CURRENT_BINARY_DIR}/native-publication-proof
    -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/scalar_alias_publication.cmake)
endif()


if(BUILD_TESTING)
  option(SCPP_ON_DEMAND_TESTS "Register exhaustive regression matrices" OFF)
  if(SCPP_ON_DEMAND_TESTS)
    foreach(case terminal_index_call_recovery_vector terminal_index_call_recovery_hash)
      add_test(NAME compiler_php_nullsafe_call_${case} COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/php_nullsafe_call.py ${case})
    endforeach()
    add_subdirectory(tests/on_demand)
  endif()
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
