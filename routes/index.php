<?php
use Slim\App;

return function (App $app) {
    (require __DIR__ . '/api.php')($app);
    (require __DIR__ . '/seeds.php')($app);
    (require __DIR__ . '/products.php')($app);
    (require __DIR__ . '/categories.php')($app);
    (require __DIR__ . '/outlets.php')($app);
    (require __DIR__ . '/orders.php')($app);
    (require __DIR__ . '/delivery_orders.php')($app);
    (require __DIR__ . '/receives.php')($app);
};
