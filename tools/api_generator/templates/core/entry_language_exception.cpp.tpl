    } catch (const exception& failure) {
        const auto& message = failure.view->message();
        std::fwrite(message.data(), 1, static_cast<std::size_t>(message.size()), stderr);
