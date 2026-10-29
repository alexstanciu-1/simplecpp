# JSON depends on its publication's core; core never depends on JSON.
function(scpp_add_json target core generated)
  add_library(${target} STATIC ${generated}/src/json.cpp
    ${PROJECT_SOURCE_DIR}/runtime/src/json/json_native.cpp)
  target_link_libraries(${target} PUBLIC ${core} PRIVATE scpp_boxed_storage)
  target_include_directories(${target} PRIVATE
    ${PROJECT_SOURCE_DIR}/runtime/src/json ${PROJECT_SOURCE_DIR}/runtime/src/boxed
    ${PROJECT_SOURCE_DIR}/runtime/src/hash ${PROJECT_SOURCE_DIR}/runtime/src/ownership
    ${PROJECT_SOURCE_DIR}/runtime/src/numeric)
  target_compile_features(${target} PUBLIC cxx_std_23)
endfunction()
