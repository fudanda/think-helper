## 设计模式-使用 [返回](../README.md)
`use fdd\CustomRequest;`

`use fdd\designpattern\responsibilitychain\Request;`

`use fdd\designpattern\responsibilitychain\Start;`

`use app\logic\chain\CheckUser;`
## 责任链
```php
        //请求对象
        $request =  CustomRequest::make(request()->param());
        //实例化责任链对象
        $start = Start::make();
        $checkUser = CheckUser::make();
        //责任链
        $start
            ->setNext($checkUser);
        // ->setNext($checkMember)
        // ->setNext($addOrder);
        //启动
        $start->start($request);
        var_dump($request->callback);
```
