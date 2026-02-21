<?php
try {
  require __DIR__ . '/config/config.php';
  echo "OK: connected to " . db()->host_info;
} catch (Throwable $e) {
  echo "ERR: " . $e->getMessage();
}
