<?php

require_once __DIR__ . '/strain.php';

echo json_encode(\Buddy\Strain::get('all','outlaw'));
