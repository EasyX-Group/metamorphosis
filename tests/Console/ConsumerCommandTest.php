<?php
namespace Tests\Console;

use Metamorphosis\ConsumerRunner;
use Metamorphosis\Consumers\HighLevel;
use Metamorphosis\Consumers\LowLevel;
use Metamorphosis\Exceptions\ConfigurationException;
use Mockery as m;
use RuntimeException;
use Tests\Dummies\ConsumerHandlerDummy;
use Tests\LaravelTestCase;

class ConsumerCommandTest extends LaravelTestCase
{
    public function setUp()
    {
        parent::setUp();

        config([
            'kafka' => [
                'brokers' => [
                    'default' => [
                        'connections' => 'test-kafka:6680',
                        'auth' => [],
                    ],
                ],
                'topics' => [
                    'topic-key' => [
                        'topic' => 'topic-name',
                        'broker' => 'default',
                        'consumer-groups' => [
                            'default' => [
                                'offset-reset' => 'earliest',
                                'handler' => ConsumerHandlerDummy::class,
                                'timeout' => 123,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function testItCallsCommandWithInvalidTopic()
    {
        $command = 'kafka:consume';
        $parameters = [
            'topic' => 'some-topic',
        ];

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Topic \'some-topic\' not found');

        $this->artisan($command, $parameters);
    }

    public function testItCallsCommandWithOffsetWithoutPartition()
    {
        $command = 'kafka:consume';
        $parameters = [
            'topic' => 'some-topic',
            '--offset' => 1,
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough options ("partition" is required when "offset" is supplied).');

        $this->artisan($command, $parameters);
    }

    public function testItCallsWithHighLevelConsumer()
    {
        // Set
        $runner = $this->instance(ConsumerRunner::class, m::mock(ConsumerRunner::class));
        $command = 'kafka:consume';
        $parameters = [
            'topic' => 'topic-key',
            'consumer-group' => 'default'
        ];

        // Expectations
        $runner->expects()
            ->run()
            ->once();

        // Actions
        $this->artisan($command, $parameters);
    }

    public function testItCallsWithLowLevelConsumer()
    {
        $runner = $this->createMock(ConsumerRunner::class);
        $this->instance(ConsumerRunner::class, $runner);

        $runner->expects($this->once())
            ->method('run')
            ->with($this->anything(), $this->callback(function ($subject) {
                return $subject instanceof LowLevel;
            }));

        $command = 'kafka:consume';
        $parameters = [
            'topic' => 'topic-key',
            '--partition' => 1,
            '--offset' => 5,
        ];

        $this->artisan($command, $parameters);
    }

    public function testItAcceptsTimeoutWhenCallingCommand()
    {
        $runner = $this->createMock(ConsumerRunner::class);
        $this->instance(ConsumerRunner::class, $runner);

        $runner->expects($this->once())
            ->method('run')
            ->with($this->anything(), $this->callback(function ($subject) {
                return $subject instanceof HighLevel;
            }));

        $command = 'kafka:consume';
        $parameters = [
            'topic' => 'topic-key',
            '--timeout' => 1,
        ];

        $this->artisan($command, $parameters);
    }

    public function testItOverridesBrokerConnectionWhenCallingCommand()
    {
        config([
            'kafka.brokers.some-broker' => [
                'connections' => '',
                'auth' => [],
            ],
        ]);

        $runner = $this->createMock(ConsumerRunner::class);
        $this->instance(ConsumerRunner::class, $runner);

        $runner->expects($this->once())
            ->method('run')
            ->with($this->anything(), $this->callback(function ($subject) {
                return $subject instanceof HighLevel;
            }));

        $command = 'kafka:consume';
        $parameters = [
            'topic' => 'topic-key',
            '--timeout' => 1,
            '--broker' => 'some-broker',
        ];

        $this->artisan($command, $parameters);
    }
}
