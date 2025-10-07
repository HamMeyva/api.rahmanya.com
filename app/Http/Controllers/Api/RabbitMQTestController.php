<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\TestQueueJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQTestController extends Controller
{
    public function testConnection()
    {
        try {
            $connection = new AMQPStreamConnection(
                config('queue.connections.rabbitmq.host'),
                config('queue.connections.rabbitmq.port'),
                config('queue.connections.rabbitmq.login'),
                config('queue.connections.rabbitmq.password'),
                config('queue.connections.rabbitmq.vhost')
            );

            $channel = $connection->channel();

            $queue = 'test_queue';
            $message = 'RabbitMQ test message at ' . now()->toDateTimeString();

            // Declare a queue
            $channel->queue_declare($queue, false, true, false, false);

            // Publish a test message
            $msg = new AMQPMessage($message);
            $channel->basic_publish($msg, '', $queue);

            // Close the channel and connection
            $channel->close();
            $connection->close();

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully connected to RabbitMQ and published a test message',
                'queue' => $queue,
                'message_sent' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'RabbitMQ connection failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function consumeMessage()
    {
        try {
            $connection = new AMQPStreamConnection(
                config('queue.connections.rabbitmq.host'),
                config('queue.connections.rabbitmq.port'),
                config('queue.connections.rabbitmq.login'),
                config('queue.connections.rabbitmq.password'),
                config('queue.connections.rabbitmq.vhost')
            );

            $channel = $connection->channel();
            $queue = 'test_queue';
            
            $channel->queue_declare($queue, false, true, false, false);
            
            $message = null;
            
            $callback = function ($msg) use (&$message) {
                $message = $msg->body;
                $msg->ack();
            };

            $channel->basic_consume($queue, '', false, false, false, false, $callback);

            // Wait for one message
            if (count($channel->callbacks)) {
                $channel->wait(null, false, 5);
            }

            $channel->close();
            $connection->close();

            if ($message) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Successfully consumed a message from RabbitMQ',
                    'content' => $message
                ]);
            } else {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'No messages available in the queue'
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to consume message from RabbitMQ',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Test queue with fallback mechanism
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testQueueWithFallback(Request $request)
    {
        $message = $request->input('message', 'Test message at ' . now()->toDateTimeString());
        $queueName = $request->input('queue', 'default');
        
        try {
            // Try to dispatch the job to the specified queue
            $job = new TestQueueJob($message);
            
            // Specify the queue connection and queue name
            // Laravel will automatically use the fallback connection if the primary fails
            Queue::connection('rabbitmq')->pushOn($queueName, $job);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Job dispatched to queue system',
                'details' => [
                    'primary_connection' => 'rabbitmq',
                    'fallback_connection' => config('queue.connections.rabbitmq.options.fallback', 'redis'),
                    'queue' => $queueName,
                    'job_message' => $message
                ]
            ]);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Queue dispatch failed: ' . $e->getMessage(), [
                'exception' => $e,
                'queue' => $queueName
            ]);
            
            // Try with fallback queue directly
            try {
                $fallbackConnection = config('queue.connections.rabbitmq.options.fallback', 'redis');
                Queue::connection($fallbackConnection)->pushOn($queueName, new TestQueueJob($message));
                
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Primary queue system failed, job dispatched to fallback system',
                    'details' => [
                        'primary_connection' => 'rabbitmq',
                        'fallback_connection' => $fallbackConnection,
                        'queue' => $queueName,
                        'job_message' => $message,
                        'error' => $e->getMessage()
                    ]
                ]);
            } catch (\Exception $fallbackException) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Both primary and fallback queue systems failed',
                    'details' => [
                        'primary_error' => $e->getMessage(),
                        'fallback_error' => $fallbackException->getMessage()
                    ]
                ], 500);
            }
        }
    }
    
    /**
     * Get queue status information
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function queueStatus()
    {
        $status = [
            'connections' => [],
            'default_connection' => config('queue.default'),
            'fallback_enabled' => false
        ];
        
        // Check RabbitMQ
        try {
            $connection = new AMQPStreamConnection(
                config('queue.connections.rabbitmq.host'),
                config('queue.connections.rabbitmq.port'),
                config('queue.connections.rabbitmq.login'),
                config('queue.connections.rabbitmq.password'),
                config('queue.connections.rabbitmq.vhost')
            );
            
            $channel = $connection->channel();
            $status['connections']['rabbitmq'] = [
                'status' => 'connected',
                'details' => [
                    'host' => config('queue.connections.rabbitmq.host'),
                    'port' => config('queue.connections.rabbitmq.port'),
                    'vhost' => config('queue.connections.rabbitmq.vhost')
                ]
            ];
            
            $channel->close();
            $connection->close();
        } catch (\Exception $e) {
            $status['connections']['rabbitmq'] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
        
        // Check Redis if it's configured
        if (config('queue.connections.redis')) {
            try {
                $redis = app('redis');
                $redis->ping();
                
                $status['connections']['redis'] = [
                    'status' => 'connected',
                    'details' => [
                        'host' => config('database.redis.default.host'),
                        'port' => config('database.redis.default.port')
                    ]
                ];
            } catch (\Exception $e) {
                $status['connections']['redis'] = [
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Check Database if it's configured
        if (config('queue.connections.database')) {
            try {
                $tableExists = \Illuminate\Support\Facades\Schema::hasTable(
                    config('queue.connections.database.table', 'jobs')
                );
                
                $status['connections']['database'] = [
                    'status' => 'connected',
                    'details' => [
                        'connection' => config('queue.connections.database.connection'),
                        'table' => config('queue.connections.database.table', 'jobs'),
                        'table_exists' => $tableExists
                    ]
                ];
            } catch (\Exception $e) {
                $status['connections']['database'] = [
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Check fallback configuration
        if (config('queue.connections.rabbitmq.options.fallback')) {
            $status['fallback_enabled'] = true;
            $status['fallback_connection'] = config('queue.connections.rabbitmq.options.fallback');
        }
        
        return response()->json($status);
    }
}
