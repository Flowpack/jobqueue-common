# Flowpack.JobQueue.Common

Neos Flow package that allows for asynchronous and distributed execution of tasks.

### Table of contents

  * [Quickstart](#quickstart-tldr)
  * [Introduction](#introduction)

## Quickstart (TL;DR)

1. Install this package using composer:

  ```
  composer require flowpack/jobqueue-common
  ```
  (or by adding the dependency to the composer manifest of an installed package)

2. Configure a basic queue by adding the following to your `Settings.yaml`:

  ```yaml
  Flowpack:
    JobQueue:
      Common:
        queues:
          'some-queue':
            className: 'Flowpack\JobQueue\Common\Queue\SerialQueue'
  ```

3. Initialize the queue (if required)

With

  ```
  ./flow queue:setup some-queue
  ```

you can setup the queue and/or verify its configuration.
In the case of the `SerialQueue` that step is not required.

  *Note:* The `queue:setup` command won't remove any existing messages, there is no harm in calling it multiple times

4. Annotate any *public* method you want to be executed asynchronously:

  ```php
  use Flowpack\JobQueue\Common\Annotations as Job;
  
  class SomeClass {
  
      /**
       * @Job\Defer(queueName="some-queue")
       */
      public function sendEmail($emailAddress)
      {
          // send some email to $emailAddress
      }
  }
  ```

  *Note:* The method needs to be *public* and it must not return anything

5. Done.

  Whenever the method `SomeClass::sendEmail()` is about to be called that method call is converted into a job that is executed in a separate thread.

## Introduction

To get started let's first define some terms:

<dl>
  <dt>Message</dt>
  <dd>
    A piece of information passed between programs or systems, sometimes also referred to as "Event".<br>
    In the JobQueue packages we use messages to transmit `Jobs`.
  </dd>
  <dt>Message Queue</dt>
  <dd>
    According to <a href="https://en.wikipedia.org/wiki/Message_queue">Wikipedia</a> "message queues [...] are software-engineering components used for inter-process communication (IPC), or for inter-thread communication within the same process".<br />
    In the context of the JobQueue packages we refer to "Message Queue" as a <a href="https://en.wikipedia.org/wiki/FIFO_(computing_and_electronics)">FIFO</a> buffer that distributes messages to one or more consumers, so that every message is only processed once.
  </dd>
  <dt>Job</dt>
  <dd>
    A unit of work to be executed (asynchronously).<br />
    In the JobQueue packages we use the Message Queue to store serialized jobs, so it acts as a "Job stream".
  </dd>
  <dt>Job Manager</dt>
  <dd>
    Central authority allowing adding and fetching jobs to/from the Message Queue.
  </dd>
</dl>

### Message Queue

The `Flowpack.JobQueue.Common` package comes with a very basic Message Queue implementation `Flowpack\JobQueue\Common\Queue\SerialQueue` that allows for execution of Jobs using sub requests.
It doesn't need any 3rd party tools or server loops and works for basic scenarios. But it has a couple of limitations to be aware of:

1. It is not actually a queue, but dispatches jobs immediately as they are queued. So it's not possible to distribute the work to multiple workers

2. The `JobManager` is not involved in processing of jobs so the jobs need to take care of error handling themselves

For advanced usage it is recommended to use one of the implementing packages like `Flowpack.JobQueue.Beanstalkd`, `Flowpack.JobQueue.Redis` or `Flowpack.JobQueue.Doctrine`.

#### Configuration

This is the simplest configuration for a queue:

```yaml
Flowpack:
  JobQueue:
    Common:
      queues:
        'test':
          className: 'Flowpack\JobQueue\Common\Queue\SerialQueue'
```

With this a queue named `test` will be available.

*Note:* For reusable packages you should consider adding a vendor specific prefixes to avoid collisions

##### Queue parameters

The following parameters are supported by all queues:

| Parameter               | Type    | Default          | Description                              |
| ----------------------- |---------| ----------------:| ---------------------------------------- |
| className               | string  | -                | FQN of the class implementing the queue  |
| maximumNumberOfReleases | integer | 3                | Max. number of times a message is re-<br>released to the queue if a job failed |
| queueNamePrefix         | string  | -                | Optional prefix for the internal queue name,<br>allowing to re-use the same backend over multiple installations |
| options                 | array   | -                | Options for the queue.<br>Implementation specific (see corresponding package) |
| releaseOptions          | array   | ['delay' => 300] | Options that will be passed to `release()` when a job failed<br>Implementation specific (see corresponding package)  |

A more complex example could look something like:

```yaml
Flowpack:
  JobQueue:
    Common:
      queues:
        'email':
          className: 'Flowpack\JobQueue\Beanstalkd\Queue\BeanstalkdQueue'
          maximumNumberOfReleases: 5
          queueNamePrefix: 'staging-'
          options:
            client:
              host: 127.0.0.11
              port: 11301
            defaultTimeout: 50
          releaseOptions:
            priority: 512
            delay: 120
        'log':
          className: 'Flowpack\JobQueue\Redis\Queue\RedisQueue'
          queueNamePrefix: 'staging-'
          options:
            defaultTimeout: 10
```

As you can see, you can have multiple queues in one installations. That allows you to use different backends/options for queues depending on the requirements.

##### Presets

If multiple queries share common configuration **presets** can be used to ease readability and maintainability:

```yaml
Flowpack:
  JobQueue:
    Common:
      presets:
        'default':
          className: 'Flowpack\JobQueue\Doctrine\Queue\DoctrineQueue'
          queueNamePrefix: 'staging-'
          options:
            pollInterval: 2
      queues:
        'email':
          preset: 'default'
          options:
            tableName: 'queue_email'
        'log':
          preset: 'default'
          options:
            tableName: 'queue_log'
            pollInterval: 1 # overrides "pollInterval" of the preset
```

This will configure two `DoctrineQueue`s "email" and "log" with some common options but different table names and poll intervals.

#### Command Line Interface

Use the `flowpack.jobqueue.common:queue:*` commands to interact with the job queues:

| Command         | Description                                                                |
| --------------- |----------------------------------------------------------------------------|
| queue:list      | List configured queues                                                     |
| queue:setup     | Initialize a queue (i.e. create required db tables, check connection, ...) |
| queue:flush     | Remove all messages from a queue (requires --force flag)                   |
| queue:submit    | Submit a message to a queue                                                |


### Job Queue


The job is an arbitrary class implementing `Flowpack\JobQueue\Common\Job\JobInterface`.
This package comes with one implementation `StaticMethodCallJob` that allows for invoking a public method (see [Quickstart](#quickstart-tldr))
but often it makes sense to create a custom Job:

```php
<?php
use Flowpack\JobQueue\Common\Job\JobInterface;
use Flowpack\JobQueue\Common\Queue\Message;
use Flowpack\JobQueue\Common\Queue\QueueInterface;

class SendEmailJob implements JobInterface
{
    protected $emailAddress;

    public function __construct($emailAddress)
    {
        $this->emailAddress = $emailAddress;
    }


    public function execute(QueueInterface $queue, Message $message)
    {
        // TODO: send the email to $this->emailAddress
        return true;
    }

    public function getIdentifier()
    {
        return 'SendEmailJob';
    }

    public function getLabel()
    {
        return sprintf('SendEmailJob (email: "%S")', $this->emailAddress);
    }
}
```

*Note:* It's crucial that the `execute()` method returns TRUE on success, otherwise the corresponding message will be released again and/or marked failed.


With that in place, the new job can be added to a queue like this:


```php
use Flowpack\JobQueue\Common\Job\JobInterface;
use Flowpack\JobQueue\Common\Job\JobManager;
use TYPO3\Flow\Annotations as Flow;

class SomeClass {

    /**
     * @Flow\Inject
     * @var JobManager
     */
    protected $jobManager;

    /**
     * @return void
     */
    public function queueJob()
    {
        $job = new SendEmailJob('some@email.com');
        $this->jobManager->queue('queue-name', $job);
    }
}
```


TBC..



