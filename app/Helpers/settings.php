<?php

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        $settings = \Cache::remember('gescolab_settings', 3600, function () {
            return \DB::table('settings')->pluck('value', 'key')->toArray();
        });

        // Priorité : DB → config/gescolab.php → $default
        return $settings[$key]
            ?? config('gescolab.' . $key)
            ?? $default;
    }
}
