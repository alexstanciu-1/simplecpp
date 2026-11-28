# Assemble the bounded frontend from its canonical sources. Only the adapter is native C++.
set(app ${CMAKE_CURRENT_BINARY_DIR}/app)
set(examples ${CMAKE_CURRENT_SOURCE_DIR}/../..)
foreach(source IN ITEMS translated_path/frontend translated_path/function_ast retained_frontend/session
    retained_frontend/frontend_requests tokenizer/utf8 native_driver/build_inputs)
  get_filename_component(name ${source} NAME)
  configure_file(${examples}/${source}.phs ${app}/${name}.phs COPYONLY)
endforeach()
configure_file(qualification.phs ${app}/qualification.phs COPYONLY)
configure_file(ninja.phs ${app}/ninja.phs COPYONLY)
configure_file(graph.phs ${app}/graph.phs COPYONLY)
# Escape a PHP single-quoted source literal for checkout paths containing quotes or backslashes.
string(REPLACE "\\" "\\\\" inventory_runtime_path "${PROJECT_SOURCE_DIR}/runtime")
string(REPLACE "'" "\\'" inventory_runtime_path "${inventory_runtime_path}")
configure_file(runtime_path.phs.in ${app}/runtime_path.phs @ONLY)
configure_file(discovery.phs ${app}/discovery.phs COPYONLY)
configure_file(inventory.phs ${app}/inventory.phs COPYONLY)
configure_file(protocol.phs ${app}/main.phs COPYONLY)
configure_file(module.json ${app}/module.json COPYONLY)

find_package(OpenSSL REQUIRED COMPONENTS Crypto)
add_library(scpp_frontend_host STATIC host.cpp client.cpp sha256.cpp qualification_host.cpp)
target_link_libraries(scpp_frontend_host PRIVATE scpp_runtime OpenSSL::Crypto)
file(GENERATE OUTPUT "${CMAKE_CURRENT_BINARY_DIR}/adapter/module.json" CONTENT
  "{\"name\":\"compiler-private-frontend\",\"type\":\"c++\",\"artifacts\":[\"$<TARGET_FILE:scpp_frontend_host>\",\"$<TARGET_FILE:scpp_compiler_binary64>\",\"$<TARGET_FILE:OpenSSL::Crypto>\"]}\n")
configure_file(toolchain.json.in toolchain.json @ONLY)

add_custom_target(scpp_native_frontend ALL
  COMMAND ${CMAKE_COMMAND} -DPHP=${PHP_EXECUTABLE} -DEMIT=${examples}/tokenizer/emit_punctuation.php
    -DOUT=${app}/punctuation.phs -P ${CMAKE_CURRENT_SOURCE_DIR}/punctuation.cmake
  COMMAND ${PHP_EXECUTABLE} ${PROJECT_SOURCE_DIR}/compiler/main.php build
    ${app} ${native_facts} ${CMAKE_CURRENT_BINARY_DIR}/bundle ${CMAKE_CURRENT_BINARY_DIR}/native
    ${CMAKE_CURRENT_BINARY_DIR}/toolchain.json
  DEPENDS scpp_frontend_host scpp_compiler_binary64 scpp_filesystem scpp_json scpp_datetime
  BYPRODUCTS ${CMAKE_CURRENT_BINARY_DIR}/native/program
  VERBATIM)
