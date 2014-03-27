<?php namespace Mocavo\QueuedHttpClient;

use Illuminate\Support\ServiceProvider;

class QueuedHttpClientServiceProvider extends ServiceProvider {

    public function register()
    {
        $this->app->bind('queuedhttpclient', function()
        {
            return new \mocavo\QueuedHttpClient\QueuedHttpClient;
        });
    }

}

