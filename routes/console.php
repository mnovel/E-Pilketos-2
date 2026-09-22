<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('pilketos:auto-update')->everyMinute();
