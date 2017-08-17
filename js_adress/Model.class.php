<?php 

class Model
{
    protected $link = null;//连接标识
    protected $tabName = null;//用于存储表名
    protected $fields = [];//字段列表
    protected $pk;//主键
    protected $keys;//要查询的字段们
    protected $where;//查询条件
    protected $order;//排序条件
    protected $limit;//分页条件

    //初始化数据库连接
    public function __construct($tabName)
    {
        //返回对象,存为link连接属性
        $this->link = mysqli_connect(HOST, USER, PASS, DB);
        mysqli_set_charset($this->link, CHAR);
        //接收 实例化时 传入的表名
        $this->tabName = $tabName;
        //查询所有字段
        $this->getField();
    }

    //查询全部
    public function select()
    {
        //判断有无字段条件
        $keys = '*';// 默认值
        if (!empty($this->keys)) {
            $keys = $this->keys;//有则使用
            $this->keys = null;//每次清除查询条件
        }

        //判断有无where条件
        $where = '';
        if (!empty($this->where)) {
            //注意WHERE后面有空格,再拼接where条件
            $where = 'WHERE '.$this->where;
            $this->where = null;//每次用完清除条件
        }

        //判断有无order条件
        $order = '';
        if (!empty($this->order)) {
            //注意SQL格式
            $order = 'ORDER BY '.$this->order;
            $this->order = null;//每次用完清除条件
        }

        //判断有无limit条件
        $limit = '';
        if (!empty($this->limit)) {
            //注意SQL格式
            $limit = 'LIMIT '.$this->limit;
            $this->limit = null;//每次用完清除条件
        }

        //SQL里 放置各种 处理好的 变量条件
        $sql = "SELECT {$keys} FROM {$this->tabName} {$where} {$order} {$limit}";
        return $this->query($sql);
    }

    //查询单条数据
    public function find($findValue, $findKey = 'id')
    {
        $keys = '*';// 默认值
        //判断有无字段条件
        if (!empty($this->keys)) {
            $keys = $this->keys;
            $this->keys = null;//每次清除查询条件
        }
        $sql = "SELECT {$keys} FROM {$this->tabName} WHERE `{$findKey}`='{$findValue}' LIMIT 1";
        //接收查询的结果
        $data = $this->query($sql);
        //判断结果是否为空,为空返回false
        if (empty($data)) {
            return false;//没查到东西
        }
        //返回查询到的数据(一维数组)
        return $data[0];
    }

    //获取要查询的条件,对象链操作
    public function where($where)
    {
        //设置要查询的条件
        $this->where = $where;
        return $this;//返回自己
    }

    //获取排序的条件,对象链操作
    public function order($order)
    {
        //设置排序条件
        $this->order = $order;
        return $this;//返回自己
    }

    //获取分页条件,对象链操作
    public function limit($limit)
    {
        //设置分页条件
        $this->limit = $limit;
        return $this;//返回自己
    }

    //获取要查询的字段名
    public function field($arr)
    {
        //判断参数 是否是数组
        if (!is_array($arr)) {return $this;}
        //遍历参数 删除非法字段
        foreach ($arr as $key => $val) {
            if (!in_array($val, $this->fields)) {
                unset($arr[$key]);
            }
        }
        //判断参数是否为空
        if (empty($arr)) {return $this;}

        //生成 字段条件 存为属性
        $this->keys = implode(',', $arr);

        return $this;//返回自己 对象链操作
    }

    //删除数据
    public function del($delValue, $delKey = 'id')
    {
        $sql = "DELETE FROM {$this->tabName} WHERE `{$delKey}`='{$delValue}'";
        return $this->execute($sql);
    }

    //新增数据
    public function add($data = array())
    {
        //如果没有传递参数, 我们就从POST里拿数据
        if (empty($data)) {
            $data = $_POST;
        }

        //筛选POST数据
        foreach ($data as $k => $v) {
            //如果POST里的$k 在字段列表之中,就保留
            if (in_array($k, $this->fields)) {
                $list[$k] = $v;
            }
        }

        //生成SQL中的key和value值
        $keys = implode(',', array_keys($list));
        $values = implode("','", array_values($list));

        //SQL
        $sql = "INSERT INTO {$this->tabName} ($keys) VALUES('$values')";
        return $this->execute($sql);
    }

    //改
    public function update($data = array())
    {
        //如果没有传递参数, 我们就从POST里拿数据
        if (empty($data)) {
            $data = $_POST;
        }
       //筛选POST数据
       foreach ($data as $k => $v) {
           //如果POST里的$k 在字段列表之中,就保留
           if (in_array($k, $this->fields) && $k != $this->pk) {
                $list[] = "`{$k}`='{$v}'";
           }
       }
       //生成SET条件
       $set = implode(',', $list);
        //sql
        $sql = "UPDATE {$this->tabName} SET {$set} WHERE `{$this->pk}`='{$data[$this->pk]}'";
    }


    //统计总条目数
    public function count()
    {
        //判断有无where条件
        $where = '';
        if (!empty($this->where)) {
            //注意WHERE后面有空格,再拼接where条件
            $where = 'WHERE '.$this->where;
            $this->where = null;//每次用完清除条件
        }

        $sql = "SELECT COUNT(*) total FROM {$this->tabName} {$where}";
        $total = $this->query($sql);
        return $total[0]['total'];
    }


    /***********************辅助方法*****************************/

    //获取数据表内的字段们~
    private function getField()
    {
        //查询表结构
        $sql = "DESC {$this->tabName}";
        $list = $this->query($sql);
        // var_dump($list);
        //遍历得到全部字段
        $fields = [];
        foreach ($list as $val) {
            $fields[] = $val['Field'];
            if ($val['Key'] == 'PRI') {
                $this->pk = $val['Field'];
            }
        }
        //给属性赋值
        $this->fields = $fields;
    }


    //执行查询 返回结果集或false
    private function query($sql)
    {
        //执行SQL语句
        $result = mysqli_query($this->link, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            $list = [];//存储查询结果
            $list = mysqli_fetch_all($result, MYSQLI_ASSOC);
            return $list;//返回查询结果数组
        } else {
            return false;//查询失败,返回false
        }
    }

    //执行增删改 返回执行结果, 增加时返回自增ID
    private function execute($sql)
    {
        //执行SQL语句
        $result = mysqli_query($this->link, $sql);
        //处理结果集
        if ($result && mysqli_affected_rows($this->link) > 0) {
            //添加时 返回自增ID
            if (mysqli_insert_id($this->link) > 0) {
                //自增成功
                return mysqli_insert_id($this->link);
            } else {
                // 删改成功
                return true;
            }
        } else {
            //操作失败
            return false;
        }
        
    }

    //销毁资源
    public function __destruct()
    {
        mysqli_close($this->link);
    }

}


