<?php namespace mocavo\QueuedHttpClient;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;
use \Guzzle\Service\Client as HttpClient;

class QueuedHttpClient {

    public static function get($url, $payload = null)
    {
        QueuedHttpClient::enqueue($url, 'GET', $payload);
    }

    public static function post($url, $payload)
    {
        QueuedHttpClient::enqueue($url, 'POST', $payload);
    }

    public static function put($url, $payload)
    {
        QueuedHttpClient::enqueue($url, 'PUT', $payload);
    }

    public static function patch($url, $payload)
    {
        QueuedHttpClient::enqueue($url, 'PATCH', $payload);
    }

    public static function update($url, $payload)
    {
        QueuedHttpClient::enqueue($url, 'UPDATE', $payload);
    }

    public static function delete($url, $payload = null)
    {
        QueuedHttpClient::enqueue($url, 'DELETE', $payload);
    }


    public static function enqueue($url, $method, $payload)
    {

        $callback_data = [
            'callback_url' => $url,
            'callback_method' => $method,
            'callback_payload_base64' => base64_encode($payload),
            'callback_timestamp' => time()
        ];

       //Queue::push('QueuedHttp@dequeue', $callback_data, 'queued_http_requests');
       Queue::push('queuedhttpclient@dequeue', $callback_data, 'queued_http_requests');

    }

    public static function dequeue($job, $data)
    {
        $callback_url = $data['callback_url'];
        $method =  strtoupper($data['callback_method']);
        $payload = base64_decode($data['callback_payload_base64']);

        if(empty($callback_url)) {
            Log::warning('Empty callback URL');
            return FALSE;
        }

        $client = new HttpClient();

        if($method == 'GET') {
            $request = $client->get($callback_url);
        } elseif($method == 'POST') {
            $request = $client->post($callback_url, array(), $payload);
        } elseif ($method == 'PUT') {
            $request = $client->put($callback_url, array(), $payload);
        } elseif ($method == 'DELETE') {
            $request = $client->delete($callback_url);
        } elseif ($method == 'UPDATE') {
            $request = $client->update($callback_url);
        } elseif ($method == 'PATCH') {
            $request = $client->patch($callback_url);
        } else {
            QueuedHttpClient::logFailedCallback($job, $data, ['success' => false, 'message' => 'Unsupported method: '.$method], 'hard');
            return FALSE;
        }

        if(empty($response)) {
            if ($job->attempts() > 60) {
                QueuedHttpClient::logFailedCallback($job, $data, null, 'hard');
                $job->delete();
            } else {
                QueuedHttpClient::logFailedCallback($job, $data, null, 'soft');
                $job->release(60);
            }
            return FALSE;
        }

        try {
            $response = $request->send();

            $response = $response->json();

            if($response->success == false) {

                if ($job->attempts() > 60) {
                    QueuedHttpClient::logFailedCallback($job, $data, $response, 'hard');
                    $job->delete();
                } else {
                    QueuedHttpClient::logFailedCallback($job, $data, $response, 'soft');
                    $job->release(60);
                }

                return $response;
            }

            // request probably successful, delete from queue
            $job->delete();
            return $response;

            // these exceptions are broken out here because perhaps we want to
            // treat them differently in deciding whether to requeue or not
        } catch (CurlException $e) {

            // curl network and low-level exceptions
	    QueuedHttpClient::retryLaterOrDelete($job, $data, get_class($e) . ": " . $e->getResponse(), 60, 60*24); // retry every minute for up to 24 hours

        } catch (Guzzle\Http\Exception\ClientErrorResponseException $e) {

            // server 4xx exceptions
	    QueuedHttpClient::retryLaterOrDelete($job, $data, get_class($e) . ": " . $e->getResponse(), 60, 60); // retry every minute for up to an hour

        } catch (Guzzle\Http\Exception\ServerErrorResponseException $e) {

            // server 5xx exceptions
	    QueuedHttpClient::retryLaterOrDelete($job, $data, get_class($e) . ": " . $e->getResponse(), 60, 60*24); // retry every minute for up to 24 hours

        } catch (Guzzle\Common\Exception\RuntimeException $e) {
            // result most likely wasn't in JSON format
	    QueuedHttpClient::retryLaterOrDelete($job, $data, get_class($e) . ": " . $e->getMessage(), 60, 10); // retry every minute for up to ten minutes

        } catch (Exception $e) {

            // unknown other error
	    QueuedHttpClient::retryLaterOrDelete($job, $data, get_class($e) . ": " . $e->getMessage(), 60, 60*24); // retry every minute for up to 24 hours

        }
    }

    private static function retryLaterOrDelete($job, $data, $message, $retry_secs = 60, $max_attempts = 60) {
       if ($job->attempts() > $max_attempts) {
           QueuedHttpClient::logFailedCallback($job, $data, $message, 'hard');
           $job->delete();
       } else {
           QueuedHttpClient::logFailedCallback($job, $data, $message, 'soft');
           $job->release($retry_secs);
       }
    }

    private static function logFailedCallback($job, $data, $response, $failure_mode) {
        DB::table('failed_jobs')->insert(
            [
                'queue' => $job->getQueue(),
                'connection' => 'QueuedHttpClient',
                'payload' => json_encode([
                    'job_id' => $job->getJobId(),
                    'job_data' => $data,
                    'response' => $response,
                    'attempts' => $job->attempts(),
                    'failure_mode' => $failure_mode,
                ]),
                'failed_at' => date('Y-m-d H:i:s'),
            ]
        );
    }
}
