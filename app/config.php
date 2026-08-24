<?php

return [

    //Database connexion
    'db_host' => 'localhost',
    'db_name' => 'secrets2',
    'db_user' => 'secrets2',
    'db_pass' => '7p(2e:7KwNw+6J',

    // Webapp URL (location to find view.php)
    'base_url' => 'http://dev.officecenter.fr',

    //Key to encrypt and decrypt passwords
    'app_key' => '44B2OFbrNTSx1be+U4UXc0QNSGuzG0GvpRqOB3xjw2E=',

    //Random key for IP Anonymisation
    'log_salt' => 'ce4779dd6e43043d25ce7e00c22f1275c2b76bb6b6e94ac81238355824cf3397',

    //Default TTL (1 day)
    'default_ttl_minutes' => 1440,

];