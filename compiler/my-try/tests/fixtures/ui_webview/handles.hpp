#pragma once
#include "modules/ui/ui.hpp"
namespace ui_webview_probe {
inline scpp::shared_p<scpp::ui::app> app(const scpp::string_t &label) {
    auto value = scpp::shared<scpp::ui::app>();
    value->backend = label;
    return value;
}
inline scpp::string_t label(const scpp::shared_p<scpp::ui::app> &value) {
    return value == scpp::null ? scpp::string_t("absent") : value->backend;
}
inline scpp::bool_t snapshots(const scpp::shared_p<scpp::ui::app> &left,
    const scpp::int_t<> &effect, const scpp::shared_p<scpp::ui::app> &right) {
    return scpp::bool_t(left != scpp::null && right != scpp::null
        && left->backend.native_value() == "old" && right->backend.native_value() == "new"
        && effect.native_value() == 1);
}
}
