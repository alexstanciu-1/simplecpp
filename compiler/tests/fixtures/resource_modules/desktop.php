$app ui_app=ui_app_create();
$window ui_window=ui_window_create($app,"Simple C++",380,260);
$view webview=webview_create($window);
$shown bool=ui_window_show($window);
if(!$shown){return 1;}
if(isset(webview_load_app($view,"missing"))){return 2;}
$loaded bool=webview_load_app($view,"assets");
if(!$loaded){return 3;}
$ok=false;$bad=false;$ready=false;$site="";$ticket=0;
for($i=0;$i<2000000;$i++){
    ui_app_poll($app);
    $next=ui_app_next_event($app);
    if(isset($next)){
        $event ui_event=$next;
        $kind=ui_event_type($event);
        if($kind==="webview_ready"){$ready=true;}
        if($kind==="webview_message"){
            $id=webview_message_id($event);
            $cmd=webview_message_command($event);
            if(!isset(ui_event_webview($event)) || $id<=0){return 4;}
            if($cmd==="ok"){
                if(webview_message_payload_json($event)!=="42"){return 5;}
                $reply bool=webview_reply_ok($view,$id,"43");
                if(!$reply || isset(webview_reply_ok($view,$id,"43"))){return 6;}
            }
            if($cmd==="bad"){$reject bool=webview_reply_error($view,$id,"expected","fail");}
            if(str_starts_with($cmd,"http://127.0.0.1:")){$site=$cmd;$ticket=$id;}
            if($cmd==="ok_ack"){$ok=true;}
            if($cmd==="bad_ack"){$bad=true;}
        }
        if($ok && $bad && $ready && $site!==""){break;}
    }
}
if(!$ok || !$bad || !$ready){return 7;}
if($site===""){return 11;}
$opened bool=webview_load_url($view,$site);
if(!$opened || isset(webview_eval($view,"1")) || isset(webview_reply_ok($view,$ticket,"null"))){return 12;}
$display=false;
for($j=0;$j<2000000;$j++){
    ui_app_poll($app);
    $guest=ui_app_next_event($app);
    if(isset($guest)){
        $g ui_event=$guest;
        if(ui_event_type($g)==="webview_message"){return 13;}
        if(ui_event_type($g)==="webview_navigation_finished" && str_ends_with(ui_event_url($g),"/passed")){
            $display=true;break;
        }
    }
}
if(!$display){return 14;}
$alias=$view;
webview_close($view);
if(isset(webview_load_html($alias,"closed"))){return 8;}
$closed bool=ui_window_close($window);
if(!$closed){return 9;}
$received=false;
$e=ui_app_next_event($app);
while(isset($e)){
    $event2 ui_event=$e;
    if(ui_event_type($event2)==="window_close"){$received=true;}
    $e=ui_app_next_event($app);
}
ui_app_exit($app);
if(!$received || ui_app_poll($app)){return 10;}
return 0;
