<?php
require 'vendor/autoload.php';

$r = new ReflectionClass('App\\Http\\Controllers\\AiChatController');
echo "Methods in AiChatController:\n";
foreach($r->getMethods() as $m) {
    if ($m->class === 'App\\Http\\Controllers\\AiChatController') {
        echo "- " . $m->getName() . "\n";
    }
}
