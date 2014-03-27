<?php namespace mocavo\QueuedHttpClient;

use Illuminate\Support\ServiceProvider;

class QueuedHTTPClientServiceProvider extends ServiceProvider {

    public function register()
    {
        $this->app->bind('queuedhttpclient', function()
        {
            return new \mocavo\QueuedHttpClient\QueuedHTTPClient;
        });
    }

}

