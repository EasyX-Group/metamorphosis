<?php
namespace Tests\Middlewares\Handler;

use Metamorphosis\Connectors\Producer\Connector;
use Metamorphosis\Middlewares\Handler\MiddlewareHandler;
use Metamorphosis\Middlewares\Handler\Producer;
use Metamorphosis\Record\ProducerRecord;
use Tests\LaravelTestCase;

class ProducerTest extends LaravelTestCase
{
    /** @test */
    public function it_should_process()
    {
        // Set
        $connector = $this->createMock(Connector::class);
        $this->app->instance(Connector::class, $connector);
        $record = json_encode(['message' => 'original record']);

        $producerHandler = new Producer($connector);
        $middlewareHandler = $this->createMock(MiddlewareHandler::class);

        $record = new ProducerRecord($record, 'topic-key');

        $this->assertNull($producerHandler->process($record, $middlewareHandler));
    }

    public function setUp()
    {
        parent::setUp();

        config([
            'kafka' => [
                'brokers' => [
                    'default' => [
                        'connections' => '',
                        'auth' => [],
                    ],
                ],
                'topics' => [
                    'topic-key' => [
                        'topic' => 'topic-name',
                        'broker' => 'default',
                    ],
                ],
            ],
        ]);
    }
}
