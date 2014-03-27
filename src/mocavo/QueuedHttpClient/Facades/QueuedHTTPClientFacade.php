<?php namespace mocavo\QueuedHttpClient\Facades;

use Illuminate\Support\Facades\Facade;

class QueuedHTTPClientFacade extends Facade {

	protected static function getFacadeAccessor() { return 'queuedhttpclient'; }

	public static function dequeue($job, $data) {
		HttpClient::dequeue($job, $data);
	}

}
