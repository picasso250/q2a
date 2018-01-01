<?php

define('ROOT', dirname(__DIR__));

require ROOT."/vendor/autoload.php";
require ROOT."/lib/autoload.php";
require ROOT."/model.php";
require_once ROOT.'/question2answer/qa-include/qa-base.php';
require_once QA_INCLUDE_DIR.'qa-app-users.php';
require_once QA_INCLUDE_DIR.'qa-app-posts.php';

define('BASE_URL', 'https://www.zhihu.com');
define('IMG_ROOT_URL_OLD', '#https://pic\d+.zhimg.com/#');

define('TQ', 1);
define('TA', 2);

env_load(dirname(__DIR__));

define('IMG_ROOT_URL', $_ENV['IMG_ROOT_URL']);

error2exception();
