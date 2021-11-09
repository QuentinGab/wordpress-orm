<?php

namespace QuentinGab\WordpressOrm;

class Base
{
    protected ?QueryBuilder $queryBuilder = null;

    public ?int $limit = null;

    protected $driver = "mysql";

    protected $casts = [];

    /**
     * perform action before creating a new post
     */
    public function creating()
    {
        # code...
    }

    /**
     * perform action before updating the post
     */
    public function updating()
    {
        # code...
    }

    /**
     * perform action before deleting post
     */
    public function deleting()
    {
        # code...
    }

    protected function fill(array $data, $casts = false)
    {
        if ($casts) {

            foreach ($this->casts as $key => $cast) {
                $value = static::dotGet($data, $key);

                if ($value) {

                    if (
                        $cast === "int"
                    ) {
                        $value = intval($value);
                    } elseif (
                        $cast === "float"
                    ) {
                        $value = floatval($value);
                    } elseif (
                        $cast === "string"
                    ) {
                        $value = strval($value);
                    } elseif (
                        $cast === "date"
                    ) {
                        $value = date_create($value);
                    } elseif (is_callable($cast)) {

                        $value = $cast($value);
                    }


                    static::dotSet(
                        $data,
                        $key,
                        $value
                    );
                }
            }
        }

        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
        return $this;
    }

    public function where($column, $operator, $value = null)
    {
        $this->initQueryBuilder();

        if ($value) {
            $this->queryBuilder->where($column, $operator, $value);
        } else {
            $this->queryBuilder->where($column, "=", $operator);
        }

        return $this;
    }

    public function orWhere($column, $operator, $value = null)
    {
        $this->initQueryBuilder();

        if ($value) {
            $this->queryBuilder->orWhere($column, $operator, $value);
        } else {
            $this->queryBuilder->orWhere($column, "=", $operator);
        }

        return $this;
    }

    public function order($orderBy, $order = "DESC")
    {
        $this->initQueryBuilder();

        $this->queryBuilder->order($orderBy, $order);

        return $this;
    }

    public function limit($value)
    {
        $this->initQueryBuilder();

        $this->queryBuilder->limit($value);

        return $this;
    }

    public function toArray()
    {
        return get_object_vars($this);
    }

    public function toJson()
    {
        return json_encode($this->toArray());
    }

    public static function dotGet($array, $key, $default = null)
    {
        if (is_null($key)) return $array;

        if (isset($array[$key])) return $array[$key];

        foreach (explode('.', $key) as $segment) {
            if (
                !is_array($array) ||
                !array_key_exists($segment, $array)
            ) {
                return value($default);
            }

            $array = $array[$segment];
        }

        return $array;
    }

    public static function dotSet(&$array, $key, $value)
    {
        if (is_null($key)) return $array = $value;

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = array();
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }
}
