<?php namespace Mocavo\QueuedHttpClient\Facades;

use Illuminate\Support\Facades\Facade;

class QueuedHttpClient extends Facade {

	protected static function getFacadeAccessor() { return 'queuedhttpclient'; }

	public static function dequeue($job, $data) {
		HttpClient::dequeue($job, $data);
	}

}
