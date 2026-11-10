<?php

/**
 * Bridge antara serverless runtime Vercel dan aplikasi Laravel.
 *
 * Catatan: router script berada di dalam direktori /api. Tanpa penyesuaian
 * ini, Symfony menganggap "/api" sebagai base URL (dari dirname SCRIPT_NAME)
 * dan menguping prefiks tersebut sehingga route API tidak pernah cocok.
 */
$_SERVER['SCRIPT_NAME'] = '/index.php';

require __DIR__.'/../public/index.php';
