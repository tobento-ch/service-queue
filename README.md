# Queue Service

A queue system for processing jobs in background.

## Table of Contents

- [Getting started](#getting-started)
    - [Requirements](#requirements)
    - [Highlights](#highlights)
- [Documentation](#documentation)
    - [Creating Jobs](#creating-jobs)
        - [Job](#job)
            - [Using A Named Job](#using-a-named-job)
            - [Using A Job Handler](#using-a-job-handler)
        - [Callable Job](#callable-job)
    - [Job Parameters](#job-parameters)
        - [Data Parameter](#data-parameter)
        - [Delay Parameter](#delay-parameter)
        - [Duration Parameter](#duration-parameter)
        - [Encrypt Parameter](#encrypt-parameter)
        - [Failed Parameter](#failed-parameter)
        - [Monitor Parameter](#monitor-parameter)
        - [Priority Parameter](#priority-parameter)
        - [Pushing Parameter](#pushing-parameter)
        - [Queue Parameter](#queue-parameter)
        - [Retry Parameter](#retry-parameter)
        - [Unique Parameter](#unique-parameter)
    - [Dispatching Jobs](#dispatching-jobs)
    - [Queue](#queue)
        - [In Memory Queue](#in-memory-queue)
        - [Null Queue](#null-queue)
        - [Storage Queue](#storage-queue)
        - [Sync Queue](#sync-queue)
    - [Queues](#queues)
        - [Default Queues](#default-queues)
        - [Lazy Queues](#lazy-queues)
    - [Queue Factories](#queue-factories)
        - [Queue Factory](#queue-factory)
        - [Storage Queue Factory](#storage-queue-factory)
    - [Job Processor](#job-processor)
        - [Adding Job Handlers](#adding-job-handlers)
    - [Failed Job Handler](#failed-job-handler)
    - [Failed Job Handler Factory](#failed-job-handler-factory)
    - [Worker](#worker)
        - [Running Worker](#running-worker)
        - [Running Worker Using Commands](#running-worker-using-commands)
    - [Console](#console)
        - [Work Command](#work-command)
        - [Clear Command](#clear-command)
    - [Events](#events)
    - [Learn More](#learn-more)
        - [Creating Custom Job Parameters](#creating-custom-job-parameters)
        - [Chunkable Job Example](#chunkable-job-example)
- [Credits](#credits)
___

# Getting started

Add the latest version of the task queue project running this command.

```
composer require tobento/service-queue
```

## Requirements

- PHP 8.0 or greater

## Highlights

- Framework-agnostic, will work with any project
- Decoupled design

# Documentation

## Creating Jobs

### Job

You may use the ```Job::class``` to create jobs.

#### Using A Named Job

```php
use Tobento\Service\Queue\Job;
use Tobento\Service\Queue\JobInterface;

$job = new Job(
    name: 'sample',
    payload: ['key' => 'value'],
);

var_dump($job instanceof JobInterface);
// bool(true)
```

Next, you will need to add add a [Job Handler](#adding-job-handlers) which handles the job:

#### Using A Job Handler

First, create the job handler:

```php
use Tobento\Service\Queue\JobHandlerInterface;
use Tobento\Service\Queue\JobInterface;

final class Mail implements JobHandlerInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private MessageFacotryInterface $messageFactory,
    ) {}

    public function handleJob(JobInterface $job): void
    {
        $message = $this->messageFactory->createFromArray($job->getPayload());
        
        $this->mailer->send($message);
    }
    
    public static function toPayload(MessageInterface $message): array
    {
        return $message->jsonSerialize();
    }
}
```

Finally, create the job:

```php
use Tobento\Service\Queue\Job;

$job = new Job(
    name: Mail::class,
    payload: Mail::toPayload($message),
);
```

### Callable Job

You may use the ```CallableJob::class``` to create jobs.

Parameters of the class constructor must be optional ```null|(type)``` if they cannot be resolved by the container!

```php
use Tobento\Service\Queue\CallableJob;
use Tobento\Service\Queue\JobInterface;

final class MailJob extends CallableJob
{
    public function __construct(
        private null|MessageInterface $message = null,
    ) {}

    public function handleJob(
        JobInterface $job,
        MailerInterface $mailer,
        MessageFacotryInterface $messageFactory,
    ): void {
        $message = $messageFactory->createFromArray($job->getPayload());
        
        $mailer->send($message);
    }
    
    public function getPayload(): array
    {
        if (is_null($this->message)) {
            return []; // or throw exception
        }
        
        return $this->message->jsonSerialize();
    }
    
    public function renderTemplate(): static
    {
        // render template logic ...
        return $this;
    }
}
```

Creating the job:

```php
$job = (new MailJob($message))
    ->renderTemplate();
```

## Job Parameters

You may use the available parameters providing basic features for jobs or [create custom parameters](#creating-custom-job-parameters) to add new features or customizing existing to suit your needs.

```php
use Tobento\Service\Queue\Job;
use Tobento\Service\Queue\Parameter;

$job = (new Job(name: 'sample'))
    ->parameter(new Parameter\Duration(seconds: 10))
    ->parameter(new Parameter\Retry(max: 2));
```

**Parameter helper methods**

The [Job](#job) and [Callable Job](#callable-job) support the following helper methods:

```php
use Tobento\Service\Queue\Job;
use Tobento\Service\Queue\JobInterface;

$job = (new Job(name: 'sample'))
    ->queue(name: 'secondary')
    ->data(['key' => 'value'])
    ->duration(seconds: 10)
    ->retry(max: 2)
    ->delay(seconds: 5)
    ->unique()
    ->priority(100)
    ->pushing(function() {})
    ->encrypt();
```

If you are using a [Callable Job](#callable-job) you may specify default parameters using the ```__construct``` method:

```php
use Tobento\Service\Queue\JobHandlerInterface;
use Tobento\Service\Queue\Parameter;

final class SampleJob extends CallableJob
{
    public function __construct()
    {
        $this->duration(seconds: 10);
        $this->retry(max: 2);
        
        // or using its classes:
        $this->parameter(new Parameter\Priority(100));
    }

    //...
}
```

### Delay Parameter

Use the delay parameter to set the seconds the job needs to be delayed.

```php
use Tobento\Service\Queue\Job;
use Tobento\Service\Queue\Parameter;

$job = (new Job(name: 'sample'))
    ->parameter(new Parameter\Delay(seconds: 60))
    // or using helper method:
    ->delay(seconds: 60);
```

**Queues supporting delays:**

* [Storage Queue](#storage-queue)

### Data Parameter

Use the data parameter to add additional job data.

```php
use Tobento\Service\Queue\Job;
use Tobento\Service\Queue\Parameter;

$job = (new Job(name: 'sample'))
    ->parameter(new Parameter\Data(['key' => 'value']))
    // or using helper method:
    ->data(['key' => 'value']);
```

### Duration Parameter

Use the duration parameter to set the approximate duration the job needs to process.

```php
use Tobento\Service\Queue\Job;
use Tobento\Service\Queue\Parameter;

$job = (new Job(name: 'sample'))
    ->parameter(new Parameter\Duration(seconds: 10))
    // or using helper method:
    ->duration(seconds: 10);
```

The [Failed Job Handler](#failed-job-handler) will requeue the job if the job could not be run to prevent timing out.

### Encrypt Parameter

The encrypt parameter uses the [Service Encryption](https://github.com/tobento-ch/service-encryption) to encrypt the job data.

It will encrypt the following data:

* job payload
* [Data Parameter](#data-parameter) values

**First, install the service:**

```
composer require tobento/service-encryption
```

**Next, bind the encrypter to your container used by the [Job Processor](#job-processor):**

Example using the [Service Container](https://github.com/tobento-ch/service-container) as container:

```php
use Tobento\Service\Queue\JobProcessor;
use Tobento\Service\Container\Container;
use Tobento\Service\Encryption\EncrypterInterface;

$container = new Container();
$container->set(EncrypterInterface::class, function() {
    // create enrcypter:
    return $encrypter;
});

$jobProcessor = new JobProcessor($container);
```

Check out the [Crypto Implementation](https://github.com/tobento-ch/service-encryption#crypto-implementation) section to learn more.

**Finally, add the parameter to your job:**

```php
use Tobento\Service\Queue\Job;
use Tobento\Service\Queue\Parameter;

$job = (new Job(name: 'sample'))
    ->parameter(new Parameter\Encrypt())
    // or using helper method:
    ->encrypt();
```

You may create a custom encrypt parameter to use another encrypter or to customize the encryption.

### Failed Parameter

The failed parameter may be used by other parameters to signalize that a job failed for a certain reason. For instance, the [Duration Parameter](#duration-parameter) adds the parameter if the jobs's timeout limit is reached.

```php
use Tobento\Service\Queue\Parameter\Failed;

$job->parameter(new Failed(reason: Failed::TIMEOUT_LIMIT));
```

In addition, the [Failed Job Handler](#failed-job-handler) uses the parameter to handle failed jobs based on the reason failed.

### Monitor Parameter

The monitor parameter is added by the [Worker](#worker) and may be used to log data about jobs such as the runtime in seconds and the memory usage. For instance, the parameter is used by the [Work Command](#work-command) to write its data to the console.

```php
use Tobento\Service\Queue\Parameter\Monitor;

if ($job->parameters()->has(Monitor::class)) {
    $monitor = $job->parameters()->get(Monitor::class);
    $runtimeInSeconds = $monitor->runtimeInSeconds();
    $memoryUsage = $monitor->memoryUsage();
}
```

### Priority Parameter

Use the priority parameter to specify the priority of the job. Higher prioritized jobs will be processed first.

```php
use Tobento\Service\Queue\Job;
use Tobento\Service\Queue\Parameter;

$job = (new Job(name: 'sample'))
    ->parameter(new Parameter\Priority(100))
    // or using helper method:
    ->priority(100);
```

### Pushing Parameter

Use the pushing parameter to specify a handler executed before the job gets pushed to the queue.

```php
use Tobento\Service\Queue\Job;
use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\Parameter;
    
$job = (new Job(name: 'sample'))
    ->parameter(new Parameter\Pushing(
        handler: function(JobInterface $job, AnyResolvableClass $foo): void {
            //
        },
        
        // you may set a priority. Higher gets executed first:
        priority: 100, // 0 is default
    ))
    
    // or using helper method:
    ->pushing(handler: function() {}, priority: 100);
```

### Queue Parameter

Use the queue parameter to specify the queue to push the job to.

```php
use Tobento\Service\Queue\Job;
use Tobento\Service\Queue\Parameter;

$job = (new Job(name: 'sample'))
    ->parameter(new Parameter\Queue(name: 'secondary'))
    // or using helper method:
    ->queue(name: 'secondary');
```

The parameter will automatically be added by the [Job Processor](#job-processor) when the job is pushed to the queue.

### Retry Parameter

Use the retry parameter to specify the max number of retries.

```php
use Tobento\Service\Queue\Job;
use Tobento\Service\Queue\Parameter;

$job = (new Job(name: 'sample'))
    ->parameter(new Parameter\Retry(max: 2))
    // or using helper method:
    ->retry(max: 2);
```

The [Failed Job Handler](#failed-job-handler) uses the parameter to handle the retries.

### Unique Parameter

If you add the unique parameter, the job will only be processed once at a time to prevent overlapping.

```php
use Tobento\Service\Queue\Job;
use Tobento\Service\Queue\Parameter;

$job = (new Job(name: 'sample'))
    ->parameter(new Parameter\Unique(
        // A unique id. If null it uses the job id.
        id: null, // null|string
    ))
    // or using helper method:
    ->unique(id: null);
```

The parameter requires a ```CacheInterface::class``` to be binded to your container passed to the [JobProcessor](#job-processor):

Example using the [Cache Service](https://github.com/tobento-ch/service-cache) and [Container Service](https://github.com/tobento-ch/service-container):

```php
use Tobento\Service\Queue\JobProcessor;
use Tobento\Service\Container\Container;
use Psr\SimpleCache\CacheInterface;
use Tobento\Service\Cache\Simple\Psr6Cache;
use Tobento\Service\Cache\ArrayCacheItemPool;
use Tobento\Service\Clock\SystemClock;

$container = new Container();
$container->set(CacheInterface::class, function() {
    // create cache:
    return new Psr6Cache(
        pool: new ArrayCacheItemPool(
            clock: new SystemClock(),
        ),
        namespace: 'default',
        ttl: null,
    );
});

$jobProcessor = new JobProcessor($container);
```

## Dispatching Jobs

```php
use Tobento\Service\Queue\Job;
use Tobento\Service\Queue\QueueInterface;

class SomeService
{
    public function createJob(QueueInterface $queue): void
    {
        $job = new Job(
            name: 'sample',
            payload: ['key' => 'value'],
        );

        $queue->push($job);
    }
}
```

You may consider binding one of the [Queues](#queues) to the container as the default ```QueueInterface``` implementation, otherwise you will need to use the queues in order to dispatch on a certain queue:

```php
use Tobento\Service\Queue\Job;
use Tobento\Service\Queue\QueuesInterface;
use Tobento\Service\Queue\QueueException;

class SomeService
{
    public function createJob(QueuesInterface $queues): void
    {
        $job = new Job(name: 'sample');
        
        $queues->queue(name: 'secondary')->push($job);
        // throws QueueException if not exists.
        
        // or
        $queues->get(name: 'secondary')?->push($job);
        
        // or you may check if queue exists before:
        if ($queues->has(name: 'secondary')) {
            $queues->queue(name: 'secondary')->push($job);
        }
    }
}
```

## Queue

### In Memory Queue

The ```InMemoryQueue::class``` does store jobs in memory.

```php
use Tobento\Service\Queue\InMemoryQueue;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\JobProcessorInterface;

$queue = new InMemoryQueue(
    name: 'inmemeory',
    jobProcessor: $jobProcessor, // JobProcessorInterface
    priority: 100,
);

var_dump($queue instanceof QueueInterface);
// bool(true)
```

### Null Queue

The ```NullQueue::class``` does not queue any job and therefore jobs will not be processed at all.

```php
use Tobento\Service\Queue\NullQueue;
use Tobento\Service\Queue\QueueInterface;

$queue = new NullQueue(
    name: 'null',
    priority: 100,
);

var_dump($queue instanceof QueueInterface);
// bool(true)
```

### Storage Queue

The ```StorageQueue::class``` uses the [Storage Service](https://github.com/tobento-ch/service-storage) to store the jobs.

First, you will need to install the storage service:

```
composer require tobento/service-storage
```

Next, you may install the clock service or use another implementation:

```
composer require tobento/service-clock
```

Finally, create the queue:

```php
use Tobento\Service\Queue\Storage;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\JobProcessorInterface;
use Tobento\Service\Storage\StorageInterface;
use Psr\Clock\ClockInterface;

$queue = new Storage\Queue(
    name: 'storage',
    jobProcessor: $jobProcessor, // JobProcessorInterface
    storage: $storage, // StorageInterface
    clock: $clock, // ClockInterface
    table: 'jobs',
    priority: 100,
);

var_dump($queue instanceof QueueInterface);
// bool(true)
```

The storage needs to have the following table columns:

| Column | Type | Description |
| --- | --- | --- |
| ```id``` | bigint(21) primary key | - |
| ```queue``` | varchar(100) | Used to store the queue name |
| ```job_id``` | varchar(255) | Used to store the job id |
| ```name``` | varchar(255) | Used to store the job name |
| ```payload``` | json | Used to store the job payload |
| ```parameters``` | json | Used to store the job parameters |
| ```priority``` | int(11) | Used to store the job priority |
| ```available_at``` | timestamp | Used to handle the job delay |

### Sync Queue

The ```SyncQueue::class``` does dispatch jobs immediately without queuing.

```php
use Tobento\Service\Queue\SyncQueue;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\JobProcessorInterface;
use Tobento\Service\Queue\FailedJobHandlerFactoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

$queue = new SyncQueue(
    name: 'sync',
    jobProcessor: $jobProcessor, // JobProcessorInterface
    failedJobHandlerFactory: null, // null|FailedJobHandlerFactoryInterface
    eventDispatcher: null, // null|EventDispatcherInterface
    priority: 100,
);

var_dump($queue instanceof QueueInterface);
// bool(true)
```

## Queues

### Default Queues

```php
use Tobento\Service\Queue\Queues;
use Tobento\Service\Queue\QueuesInterface;
use Tobento\Service\Queue\QueueInterface;

$queues = new Queues(
    $queue, // QueueInterface
    $anotherQueue, // QueueInterface
);

var_dump($queues instanceof QueuesInterface);
// bool(true)

var_dump($queue instanceof QueueInterface);
// bool(true)
```

### Lazy Queues

The ```LazyQueues::class``` creates the queues only on demand.

```php
use Tobento\Service\Queue\LazyQueues;
use Tobento\Service\Queue\QueuesInterface;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\QueueFactoryInterface;
use Tobento\Service\Queue\QueueFactory;
use Tobento\Service\Queue\SyncQueue;
use Tobento\Service\Queue\NullQueue;
use Psr\Container\ContainerInterface;

$queues = new LazyQueues(
    container: $container, // ContainerInterface
    queues: [
        // using a factory:
        'primary' => [
            // factory must implement QueueFactoryInterface
            'factory' => QueueFactory::class,
            'config' => [
                'queue' => SyncQueue::class,
                'priority' => 100,
            ],
        ],
        
        // using a closure:
        'secondary' => static function (string $name, ContainerInterface $c): QueueInterface {
            // create queue ...
            return $queue;
        },
        
        // or you may sometimes just create the queue (not lazy):
        'null' => new NullQueue(name: 'null'),
    ],
);

var_dump($queues instanceof QueuesInterface);
// bool(true)

var_dump($queue instanceof QueueInterface);
// bool(true)
```

You may check out the [Queue Factories](#queue-factories) to learn more about it.

## Queue Factories

### Queue Factory

```php
use Tobento\Service\Queue\QueueFactory;
use Tobento\Service\Queue\QueueFactoryInterface;
use Tobento\Service\Queue\JobProcessorInterface;

$factory = new QueueFactory(
    jobProcessor: $jobProcessor // JobProcessorInterface
);

var_dump($factory instanceof QueueFactoryInterface);
// bool(true)
```

Check out the [Job Processor](#job-processor) to learn more about it.

**Create queue**

```php
use Tobento\Service\Queue\InMemoryQueue;
use Tobento\Service\Queue\NullQueue;
use Tobento\Service\Queue\SyncQueue;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\QueueException;

$queue = $factory->createQueue(name: 'primary', config: [
    // specify the queue you want to create:
    'queue' => InMemoryQueue::class,
    //'queue' => NullQueue::class,
    //'queue' => SyncQueue::class,
    
    // you may specify a priority:
    'priority' => 200,
]);

var_dump($queue instanceof QueueInterface);
// bool(true)
// or throws QueueException on failure.
```

### Storage Queue Factory

```php
use Tobento\Service\Queue\Storage\QueueFactory;
use Tobento\Service\Queue\QueueFactoryInterface;
use Tobento\Service\Queue\JobProcessorInterface;
use Tobento\Service\Database\DatabasesInterface;
use Psr\Clock\ClockInterface;

$factory = new QueueFactory(
    jobProcessor: $jobProcessor, // JobProcessorInterface
    clock: $clock, // ClockInterface
    databases: null, // null|DatabasesInterface
);

var_dump($factory instanceof QueueFactoryInterface);
// bool(true)
```

Check out the [Job Processor](#job-processor) to learn more about it.

**Create ```JsonFileStorage::class``` queue**

```php
use Tobento\Service\Storage\JsonFileStorage;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\QueueException;

$queue = $factory->createQueue(name: 'primary', config: [
    // specify the table storage:
    'table' => 'queue',
    
    // specify the storage:
    'storage' => JsonFileStorage::class,
    'dir' => 'home/private/storage/',
    
    // you may specify a priority:
    'priority' => 200,
]);

var_dump($queue instanceof QueueInterface);
// bool(true)
// or throws QueueException on failure.
```

**Create ```InMemoryStorage::class``` queue**

```php
use Tobento\Service\Storage\InMemoryStorage;
use Tobento\Service\Storage\PdoMySqlStorage;
use Tobento\Service\Storage\PdoMariaDbStorage;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\QueueException;

$queue = $factory->createQueue(name: 'primary', config: [
    // specify the table storage:
    'table' => 'queue',
    
    // specify the storage:
    'storage' => InMemoryStorage::class,
    
    // you may specify a priority:
    'priority' => 200,
]);

var_dump($queue instanceof QueueInterface);
// bool(true)
// or throws QueueException on failure.
```

**Create ```PdoMySqlStorage::class``` or ```PdoMariaDbStorage::class``` queue**

```php
use Tobento\Service\Storage\PdoMySqlStorage;
use Tobento\Service\Storage\PdoMariaDbStorage;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\QueueException;

$queue = $factory->createQueue(name: 'primary', config: [
    // specify the table storage:
    'table' => 'queue',
    
    // specify the storage:
    'storage' => PdoMySqlStorage::class,
    //'storage' => PdoMariaDbStorage::class,
    
    // specify the name of the database used:
    'database' => 'name',
    
    // you may specify a priority:
    'priority' => 200,
]);

var_dump($queue instanceof QueueInterface);
// bool(true)
// or throws QueueException on failure.
```

## Job Processor

The ```JobProcessor::class``` is responsible for processing jobs.

```php
use Tobento\Service\Queue\JobProcessor;
use Tobento\Service\Queue\JobProcessorInterface;
use Tobento\Service\Queue\JobHandlerInterface;
use Psr\Container\ContainerInterface;

$jobProcessor = new JobProcessor(
    container: $container // ContainerInterface
);

var_dump($jobProcessor instanceof JobProcessorInterface);
// bool(true)
```

### Adding Job Handlers

You may add job handlers for [Named Jobs](#using-a-named-job):

```php
use Tobento\Service\Queue\JobHandlerInterface;

$jobProcessor->addJobHandler(
    name: 'sample',
    handler: SampleHandler::class, // string|JobHandlerInterface
);
```

Example of handler:

```php
use Tobento\Service\Queue\JobHandlerInterface;
use Tobento\Service\Queue\JobInterface;

final class SampleHandler implements JobHandlerInterface
{
    public function handleJob(JobInterface $job): void
    {
        // handle job
    }
}
```

## Failed Job Handler

The ```FailedJobHandler::class``` is responsible for handling failed jobs.

```php
use Tobento\Service\Queue\FailedJobHandler;
use Tobento\Service\Queue\FailedJobHandlerInterface;
use Tobento\Service\Queue\QueuesInterface;
use Psr\Log\LoggerInterface;

$handler = new FailedJobHandler(
    queues: $queues, // QueuesInterface
    
    // set a logger if you want to log failed jobs:
    logger: $logger, // null|LoggerInterface
);

var_dump($handler instanceof FailedJobHandlerInterface);
// bool(true)
```

You may create a custom handler to fit your requirements.

## Failed Job Handler Factory

You may use the ```FailedJobHandlerFactory::class``` to create failed job handlers:

```php
use Tobento\Service\Queue\FailedJobHandlerFactory;
use Tobento\Service\Queue\FailedJobHandlerFactoryInterface;
use Tobento\Service\Queue\FailedJobHandlerInterface;
use Tobento\Service\Queue\QueuesInterface;
use Psr\Log\LoggerInterface;

$factory = new FailedJobHandlerFactory(
    logger: null, // null|LoggerInterface
);

var_dump($factory instanceof FailedJobHandlerFactoryInterface);
// bool(true)

$handler = $factory->createFailedJobHandler(
    queues: null, // null|QueuesInterface
);

var_dump($handler instanceof FailedJobHandlerInterface);
// bool(true)
```

## Worker

The ```Worker::class``` processes the queued jobs.

```php
use Tobento\Service\Queue\Worker;
use Tobento\Service\Queue\QueuesInterface;
use Tobento\Service\Queue\JobProcessorInterface;
use Tobento\Service\Queue\FailedJobHandlerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

$worker = new Worker(
    queues: $queues, // QueuesInterface
    jobProcessor: $jobProcessor, // JobProcessorInterface
    failedJobHandler: $failedJobHandler, // null|FailedJobHandlerInterface
    eventDispatcher: $eventDispatcher, // null|EventDispatcherInterface
);
```

### Running Worker

```php
use Tobento\Service\Queue\WorkerOptions;

$status = $worker->run(
    // specify the name of the queue you wish to use.
    // If null, it uses all queues by its priority, highest first.
    queue: 'name', // null|string
    
    // specify the options:
    options: new WorkerOptions(
        // The maximum amount of RAM the worker may consume:
        memory: 128,
        
        // The maximum number of seconds a worker may run:
        timeout: 60,
        
        // The number of seconds to wait in between polling the queue:
        sleep: 3,
        
        // The maximum number of jobs to run, 0 (unlimited):
        maxJobs: 0,
        
        // Indicates if the worker should stop when the queue is empty:
        stopWhenEmpty: false,
    ),
);

// you may exit:
exit($status);
```

### Running Worker Using Commands

Check out the [Console](#console) and [Work Command](#work-command) section if you want to run the worker using commands.

## Console

You may using the following commands using the [Console Service](https://github.com/tobento-ch/service-console).

To get quickly started consider using the following two app bundles:

* [App Queue](https://github.com/tobento-ch/app-queue)
* [App Console](https://github.com/tobento-ch/app-console)

Otherwise, you need to install the [Console Service](https://github.com/tobento-ch/service-console) and set up your console by yourself.

### Work Command

**Running jobs from all queues**

```
php app queue:work
```

**Running jobs from specific queue only**

```
php app queue:work --queue=primary
```

**Available Options**

| Option | Description |
| --- | --- |
| ```--name=default``` | The name of the worker. |
| ```--queue=primary``` | The name of the queue to work. |
| ```--memory=128``` | The memory limit in megabytes. |
| ```--timeout=60``` | The number of seconds the worker can run. |
| ```--sleep=3``` | The number of seconds to sleep when no job is available. |
| ```--max-jobs=0``` | The number of jobs to process before stopping (0 unlimited). |
| ```--stop-when-empty``` | Stops the worker when the queue is empty. |

### Clear Command

**Delete all of the jobs from the queues**

```
php app queue:clear
```

**Delete jobs from specific queues only**

```
php app queue:clear --queue=primary --queue=secondary
```

## Events

**Available Events**

```php
use Tobento\Service\Queue\Event;
```

| Event | Description |
| --- | --- |
| ```Event\JobStarting::class``` | The event will dispatch **before** the job is processed |
| ```Event\JobFinished::class``` | The event will dispatch **after** the job is processed |
| ```Event\JobFailed::class``` | The event will dispatch when the job failed. |
| ```Event\WorkerStarting::class``` | The event will dispatch **after** the worker started. |
| ```Event\WorkerStopped::class``` | The event will dispatch **just before** the worker stops. |
| ```Event\PoppingJobFailed::class``` | The event will dispatch when popping a job from a queue failed. |

Just make sure you pass an event dispatcher to your [worker](#worker)!

## Learn More

### Creating Custom Job Parameters

You may create a custom parameter by extending the ```Parameter::class```:

```php
use Tobento\Service\Queue\Parameter\Parameter;

class SampleParameter extends Parameter
{
    //
}
```

**Storable parameter**

By implementing the ```JsonSerializable``` interface your parameter will be stored and available when handling the job.

```php
use Tobento\Service\Queue\Parameter\Parameter;
use JsonSerializable;

class SampleParameter extends Parameter implements JsonSerializable
{
    public function __construct(
        private string $value,
    ) {}
    
    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     * Will be used to create the parameter by the parameters factory.
     * So it must much its __construct method.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return ['value' => $this->value];
    }
}
```

**Failable interface**

By implementing the ```Failable``` interface your can handle failed jobs.

```php
use Tobento\Service\Queue\Parameter\Parameter;
use Tobento\Service\Queue\Parameter\Failable;
use Tobento\Service\Queue\JobInterface;
use Throwable;

class SampleParameter extends Parameter implements Failable
{
    /**
     * Returns the failed job handler.
     *
     * @return callable
     */
    public function getFailedJobHandler(): callable
    {
        return [$this, 'processFailedJob'];
    }
    
    /**
     * Process failed job.
     *
     * @param JobInterface $job
     * @param null|Throwable $e
     * @param ... any parameters resolvable by your container.
     * @return void
     */
    public function processFailedJob(JobInterface $job, null|Throwable $e): void
    {
        //
    }
}
```

Check out the ```Tobento\Service\Queue\Parameter\Delay::class``` to see its implementation.

**Poppable interface**

By implementing the ```Poppable``` interface you can handle jobs after it is popped from the queue.

```php
use Tobento\Service\Queue\Parameter\Parameter;
use Tobento\Service\Queue\Parameter\Poppable;
use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\QueueInterface;
use JsonSerializable;

class SampleParameter extends Parameter implements Poppable, JsonSerializable
{
    /**
     * Returns the popping job handler.
     *
     * @return callable
     */
    public function getPoppingJobHandler(): callable
    {
        return [$this, 'poppingJob'];
    }
    
    /**
     * Popping job.
     *
     * @param JobInterface $job
     * @param QueueInterface $queue
     * @param ... any parameters resolvable by your container.
     * @return null|JobInterface
     */
    public function poppingJob(JobInterface $job, QueueInterface $queue): null|JobInterface
    {
        // called after the job is popped from the queue.
        // If returning null, the job gets not processed.
        return $job;
    }
    
    /**
     * Implemented as the parameter gets stored. Otherwise popping job handler gets not executed.
     */
    public function jsonSerialize(): array
    {
        return [];
    }
}
```

Check out the ```Tobento\Service\Queue\Parameter\Encrypt::class``` to see its implementation.

**Processable interface**

By implementing the ```Processable``` interface you can handle jobs processing.

```php
use Tobento\Service\Queue\Parameter\Parameter;
use Tobento\Service\Queue\Parameter\Processable;
use Tobento\Service\Queue\JobInterface;
use JsonSerializable;

class SampleParameter extends Parameter implements Processable, JsonSerializable
{
    /**
     * Returns the before process job handler.
     *
     * @return null|callable
     */
    public function getBeforeProcessJobHandler(): null|callable
    {
        return [$this, 'beforeProcessJob'];
        // or return null if not required
    }
    
    /**
     * Returns the after process job handler.
     *
     * @return null|callable
     */
    public function getAfterProcessJobHandler(): null|callable
    {
        return [$this, 'afterProcessJob'];
        // or return null if not required
    }
    
    /**
     * Before process job handler.
     *
     * @param JobInterface $job
     * @return null|JobInterface Null if job cannot be processed.
     */
    public function beforeProcessJob(JobInterface $job): null|JobInterface
    {
        return $job;
    }
    
    /**
     * After process job handler.
     *
     * @param JobInterface $job
     * @return JobInterface
     */
    public function afterProcessJob(JobInterface $job): JobInterface
    {
        return $job;
    }
    
    /**
     * Implemented as the parameter gets stored. Otherwise handlers gets not executed.
     */
    public function jsonSerialize(): array
    {
        return [];
    }
}
```

Check out the ```Tobento\Service\Queue\Parameter\Duration::class``` to see its implementation.

**Pushable interface**

By implementing the ```Pushable``` interface you can handle jobs before being pushed to the queue.

```php
use Tobento\Service\Queue\Parameter\Parameter;
use Tobento\Service\Queue\Parameter\Pushable;
use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\QueueInterface;

class SampleParameter extends Parameter implements Pushable
{
    /**
     * Returns the pushing job handler.
     *
     * @return callable
     */
    public function getPushingJobHandler(): callable
    {
        return [$this, 'pushingJob'];
    }
    
    /**
     * Pushing job.
     *
     * @param JobInterface $job
     * @param QueueInterface $queue
     * @param ... any parameters resolvable by your container.
     * @return JobInterface
     */
    public function pushingJob(JobInterface $job, QueueInterface $queue): JobInterface
    {
        return $job;
    }
}
```

Check out the ```Tobento\Service\Queue\Parameter\PushingJob::class``` to see its implementation.

### Chunkable Job Example

This example shows a possible way to create a chunkable job using the data parameter to store its process data.

```php
use Tobento\Service\Queue\CallableJob;
use Tobento\Service\Queue\Parameter;
use Tobento\Service\Queue\QueuesInterface;

final class ChunkableJob extends CallableJob
{
    public function handleJob(
        JobInterface $job,
        QueuesInterface $queues,
        // Repository $repository,
    ): void {
        if (! $job->parameters()->has(Parameter\Data::class)) {
            // first time running job:
            $data = new Parameter\Data([
                //'total' => $repository->count(),
                'total' => 500,
                'number' => 100,
                'offset' => 0,
            ]);
            
            $job->parameters()->add($data);
        } else {
            $data = $job->parameters()->get(Parameter\Data::class);
        }
        
        $total = $data->get(key: 'total', default: 0);
        $number = $data->get(key: 'number', default: 100);
        $offset = $data->get(key: 'offset', default: 0);
                
        // Handle Job:
        //$items = $repository->findAll(limit: [$number, $offset]);
        $items = range($offset, $number); // For demo we use range
        
        foreach($items as $item) {
            // do sth
        }
        
        // Update offset:
        $data->set(key: 'offset', value: $offset+$number);
        
        // Repush to queue if not finished:
        if ($offset < $total) {
            $queues->queue(
                name: $job->parameters()->get(Parameter\Queue::class)->name()
            )->push($job);
        }
    }
    
    public function getPayload(): array
    {
        return [];
    }    
}
```

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)