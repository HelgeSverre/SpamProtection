<?php

declare(strict_types=1);

http_response_code(isset($_GET['unavailable']) ? 503 : 200);
header('Content-Type: application/json');
echo '{"success":1,"ip":{"appears":0}}';
