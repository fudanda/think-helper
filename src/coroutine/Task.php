<?php

namespace fdd\coroutine;

use Generator;

/**
 * 协程Task任务类
 */
class Task
{
    protected $taskId;
    protected $coroutine;
    protected $beforeFirstYield = true;
    protected $sendValue;
    /**
     * Task constructor.
     * @param $taskId
     * @param Generator $coroutine
     */
    public function __construct($taskId, Generator $coroutine)
    {
        $this->taskId = $taskId;
        $this->coroutine = $coroutine;
        // 换成这个，实际Task->run的就是stackedCoroutine这个函数，不是$coroutine保存的闭包函数了
        // $this->coroutine = $this->stackedCoroutine($coroutine);
    }

    /**
     * 获取当前的Task的ID
     *
     * @return mixed
     */
    public function getTaskId()
    {
        return $this->taskId;
    }

    /**
     * 判断Task执行完毕了没有
     *@return bool
     */
    public function isFinished()
    {
        return !$this->coroutine->valid();
    }

    /**
     * 设置下次要传给协程的值，比如 $id = (yield $xxxx)，这个值就给了$id了
     *
     * @param $value
     */
    public function setSendValue($value)
    {
        $this->sendValue = $value;
    }

    // public function Check(Request $request);

    /**
     * 运行任务
     * @return mixed
     */
    public function run()
    {
        // 这里要注意，生成器的开始会reset，所以第一个值要用current获取
        // $this->check($request);

        if ($this->beforeFirstYield) {
            $this->beforeFirstYield = false;
            return $this->coroutine->current();
        } else {
            // 我们说过了，用send去调用一个生成器
            $retval = $this->coroutine->send($this->sendValue);
            $this->sendValue = null;
            return $retval;
        }
    }

    // function stackedCoroutine(Generator $gen)
    // {
    //     $stack = new SplStack;
    //     // 不断遍历这个传进来的生成器
    //     for (;;) {
    //         // $gen可以理解为指向当前运行的协程闭包函数（生成器）
    //         $value = $gen->current();
    //         // 获取中断点，也就是yield出来的值
    //         if ($value instanceof Generator) {
    //             // 如果是也是一个生成器，这就是子协程了，把当前运行的协程入栈保存
    //             $stack->push($gen);
    //             $gen = $value;
    //             // 把子协程函数给gen，继续执行，注意接下来就是执行子协程的流程了
    //             continue;
    //         }
    //         // 我们对子协程返回的结果做了封装，下面讲
    //         $isReturnValue = $value instanceof \CoroutineReturnValue;
    //         // 子协程返回`$value`需要主协程帮忙处理
    //         if (!$gen->valid() || $isReturnValue) {
    //             if ($stack->isEmpty()) {
    //                 return;
    //             }
    //             // 如果是gen已经执行完毕，或者遇到子协程需要返回值给主协程去处理
    //             $gen = $stack->pop();
    //             //出栈，得到之前入栈保存的主协程
    //             $gen->send($isReturnValue ? $value->getValue() : NULL);
    //             // 调用主协程处理子协程的输出值
    //             continue;
    //         }
    //         $gen->send(yield $gen->key() => $value);
    //         // 继续执行子协程
    //     }
    // }
}
