<?php

namespace fdd\coroutine;

use SplQueue;
use Generator;

/**
 *  协程任务调度
 *  Class Scheduler
 */
class Scheduler
{
    /**
     * @var SplQueue
     */
    protected $taskQueue;
    /**
     * @var int
     */
    protected $tid = 0;
    /**    * Scheduler constructor.    */
    protected $maxTaskId = 0;
    protected $taskMap = []; // taskId => task

    public function __construct()
    {
        /* 原理就是维护了一个队列，
        * 前面说过，从编程角度上看，协程的思想本质上就是控制流的主动让出（yield）和恢复（resume）机制
        * */
        $this->taskQueue = new SplQueue();
    }

    public static function make()
    {
        return new self();
    }
    /**
     * 增加一个任务    *
     * @param  $task
     * @return int
     */
    public function addTask(Generator $task)
    {
        $tid = $this->tid;
        $task = new Task($tid, $task);
        $this->taskQueue->enqueue($task);
        $this->tid++;
        return $tid;
    }
    /**    * 把任务进入队列    *    * @param Task $task    */
    public function schedule(Task $task)
    {
        $this->taskQueue->enqueue($task);
    }
    /**    * 运行调度器    */
    public function run()
    {
        while (!$this->taskQueue->isEmpty()) {
            // 任务出队
            $task = $this->taskQueue->dequeue();
            $res = $task->run();
            // 运行任务直到 yield
            if (!$task->isFinished()) {
                $this->schedule($task);
                // 任务如果还没完全执行完毕，入队等下次执行
            }
        }
    }
}
