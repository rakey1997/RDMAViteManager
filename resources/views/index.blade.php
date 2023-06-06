<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="csrf-token" content="{{csrf_token()}}">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            /* 页面变灰 */
            /* html{line-height:1.15;-webkit-text-size-adjust:100%;-webkit-filter:grayscale(100%)}} */
        </style>
        @vite('resources/js/app.js')
    </head>
    <body>
        <div id="app">
            @vite('resources/js/app.js')
        </div>
    </body>
</html>