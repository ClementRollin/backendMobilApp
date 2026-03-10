<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment('Stay pragmatic and ship clean code.');
})->purpose('Display a short motivational quote');
