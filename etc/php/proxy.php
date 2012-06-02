<?php
$context = stream_context_get_default();
stream_context_set_option($context, 'http', 'proxy', 'tcp://localhost:8888');
stream_context_set_option($context, 'http', 'request_fulluri', true);
stream_context_set_option($context, 'ssl', 'SNI_enabled', false);
stream_context_set_option($context, 'curl', 'proxy', 'tcp://localhost:8888');
libxml_set_streams_context($context);
unset($context);
