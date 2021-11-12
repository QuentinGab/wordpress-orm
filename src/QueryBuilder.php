<?php

namespace QuentinGab\WordpressOrm;

class QueryBuilder
{
    public string $driver = "mysql";

    public string $table = "";

    public array $where = [];

    public ?int $limit = null;

    public array $order = [
        'orderby' => 'id',
        'order' => 'ASC'
    ];


    public function __construct()
    {
        # code...
    }

    /**
     * @return static
     */
    public function where($column, $operator = "=", $value)
    {
        $this->where[$column] = [
            'type' => 'AND',
            'operator' => $operator,
            'value' => $value
        ];
        return $this;
    }

    /**
     * @return static
     */
    public function orWhere($column, $operator = "=", $value)
    {
        $this->where[$column] = [
            'type' => 'OR',
            'operator' => $operator,
            'value' => $value
        ];
        return $this;
    }

    /**
     * @return array
     */
    public function buildWpQuery()
    {
        $defaults =
            [
                'post_status' => 'publish',
            ];

        $formattedWhere = array_map(
            function ($item) {
                return $item['value'];
            },
            $this->where
        );

        return array_merge(
            $defaults,
            $this->order,
            [
                'numberposts' => $this->limit
            ],
            $formattedWhere
        );
    }

    /**
     * @return string
     */
    public function buildSqlQuery()
    {
        global $wpdb;

        $order = "ORDER BY {$this->order['orderby']} {$this->order['order']}";

        $from = "FROM {$this->table}";

        if ($this->driver === "mysql") {
            $limit = $this->limit ? "LIMIT {$this->limit}" : "";
        } else {
            $limit = $this->limit ? "TOP {$this->limit}" : "";
        }

        if (empty($this->where)) {
            if ($this->driver === "mysql") {
                return "SELECT * $from $order $limit";
            } else {
                return "SELECT $limit * $from $order";
            }
        }

        $where = array_values($this->where);

        $values = array_map(function ($item) {
            return $item['value'] ?? null;
        }, $where);

        $values_type = array_map(
            function ($item) {
                if (is_int($item)) {
                    return "%d";
                } elseif (is_float($item)) {
                    return "%f";
                }
                return "%s";
            },
            $values
        );

        $keys = array_keys($this->where);

        $length = count($keys);

        $whereAsSql = "";
        for ($i = 0; $i < $length; $i++) {
            if ($i > 0 and $i < $length) {
                $whereAsSql .= " {$where[$i]['type']} ";
            }

            $whereAsSql .= $keys[$i] . $where[$i]['operator'] . $values_type[$i];
        }

        if ($this->driver === "mysql") {
            return $wpdb->prepare(
                "SELECT * $from WHERE $whereAsSql $order $limit",
                $values
            );
        } else {
            return $wpdb->prepare(
                "SELECT $limit * $from WHERE $whereAsSql $order",
                $values
            );
        }
    }

    /**
     * @return static
     */
    public function driver(string $value)
    {
        $this->driver = $value;
        return $this;
    }

    /**
     * @return static
     */
    public function table(string $name)
    {
        $this->table = $name;
        return $this;
    }

    /**
     * @return static
     */
    public function limit(?int $value)
    {
        $this->limit = $value;
        return $this;
    }

    /**
     * @return static
     */
    public function order($orderBy, $order = "DESC")
    {
        if (is_array($orderBy)) {
            $orderBy = join(",", $orderBy);
        }

        $this->order = [
            'orderby' => $orderBy,
            'order' => $order
        ];

        return $this;
    }
}