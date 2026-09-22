<?php
declare(strict_types=1);
namespace manifest_test;

final class Probe {
    public static function read(string $path): void {
        try {
            $result = \read_manifest\Manifest_Reader::read($path);
            echo '{"status":"ok","single":', $result->single_source ? 'true' : 'false';
            echo ',"entry":', json_quote($result->entry), ',"directory":', json_quote($result->directory);
            echo ',"path":', json_quote($result->path), ',"content":', json_quote($result->content);
            echo ',"folders":[';
            $first /** bool */ = true;
            foreach ($result->source_folders as $folder) {
                if (!$first) { echo ','; }
                echo json_quote($folder);
                $first = false;
            }
            echo '],"files":[';
            $first = true;
            foreach ($result->source_files as $file) {
                if (!$first) { echo ','; }
                echo json_quote($file);
                $first = false;
            }
            echo "]}\n";
        } catch (\RuntimeException $error) {
            echo "{\"status\":\"io\"}\n";
        } catch (\Exception $error) {
            echo "{\"status\":\"invalid\"}\n";
        }
    }
    public static function retention(string $path): void {
        $first = \read_manifest\Manifest_Reader::read($path);
        $second = \read_manifest\Manifest_Reader::read($path);
        $second->entry = 'changed';
        echo '{"retained":', json_quote($first->entry), "}\n";
    }
    public static function json_view(): void {
        $root = json_read('{"0":null,"01":false,"text":"old","0":true}');
        $retained = $root->member('text');
        echo '{"key":', json_quote($root->key(0)), ',"key2":', json_quote($root->key(1));
        echo ',"kind":', json_quote($root->member('0')->kind()), ',"count":', $root->size();
        echo ',"missing":', $root->has('absent') ? 'false' : 'true';
        $root = json_read('[]');
        echo ',"retained":', json_quote($retained->text()), "}\n";
        try { $wrong = $root->at(0); }
        catch (\RuntimeException $error) { echo "{\"adapter_error\":\"index\"}\n"; }
        try { $wrong_text = $root->text(); }
        catch (\RuntimeException $error) { echo "{\"adapter_error\":\"kind\"}\n"; }
        $object = json_read('{}');
        try { $wrong = $object->member('absent'); }
        catch (\RuntimeException $error) { echo "{\"adapter_error\":\"missing\"}\n"; }
    }
}
