<?php
use Slim\App;

return function (App $app) {
    (require __DIR__ . '/api.php')($app);
    (require __DIR__ . '/seeds.php')($app);
};
