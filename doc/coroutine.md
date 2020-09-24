## 协程-使用 [返回](../README.md)
`use fdd\CustomRequest;`

`use app\logic\chain\CheckUser;`
```php

    $request =  CustomRequest::make(request()->param());
    $scheduler = Scheduler::make();
    $scheduler->addTask($this->task($request));
    $scheduler->run();

    public function task($request)
    {
        //检测用户
        yield from CheckUser::make()->yield()->Check($request);

        //检测其他
        //yield from CheckOther::make()->yield()->Check($request);
    }
```