<?php

namespace QuentinGab\WordpressOrm;

class Model extends Base
{
    protected $table = 'table_example';

    protected $primary_key = "id";

    public array $order = [
        'orderby' => 'id',
        'order' => 'DESC'
    ];

    /**
     * You have to define updatable columns 
     */
    protected array $fillable = [
        "prenom",
        "nom"
    ];

    protected $casts = [
        'id' => 'int'
    ];

    public function __construct(array $data = [], $casts = false)
    {
        $this->fill($data, $casts);
    }

    public function get($limit = null)
    {
        global $wpdb;

        $this->initQueryBuilder();

        $this->limit($limit);

        $db = $wpdb->get_results(
            $this->queryBuilder->buildSqlQuery(),
            ARRAY_A
        );

        if (empty($db)) {
            return [];
        }

        $items = array_map(
            function ($item) {
                return new static($item, true);
            },
            $db
        );

        return $items;
    }

    public function all()
    {
        return $this->get();
    }

    public function first()
    {
        return $this->get(1)->first();
    }

    public function find($primary_key)
    {
        return $this->where($this->primary_key, $primary_key)->first();
    }

    public function save()
    {
        if ($this->getPrimaryKey()) {
            return $this->update();
        } else {
            return $this->create();
        }
    }

    public function create()
    {
        global $wpdb;

        $this->creating();

        $values = $this->getFillable();

        $values_type = array_map(
            function ($item) {
                if (is_int($item)) {
                    return "%d";
                } elseif (is_float($item)) {
                    return "%f";
                }
                return "%s";
            },
            array_values($values)
        );

        $isSuccess = !!$wpdb->insert(
            $this->table,
            $values,
            $values_type
        );

        if (!$isSuccess) {
            return false;
        }

        $this->{$this->primary_key} = $wpdb->insert_id;

        return true;
    }

    public function update()
    {
        global $wpdb;

        $this->updating();

        $values = $this->getFillable();

        $values_type = array_map(
            function ($item) {
                if (is_int($item)) {
                    return "%d";
                } elseif (is_float($item)) {
                    return "%f";
                }
                return "%s";
            },
            array_values($values)
        );

        $isSuccess = !!$wpdb->update(
            $this->table,
            $values,
            [
                $this->primary_key => $this->getPrimaryKey()
            ],
            $values_type
        );

        if (!$isSuccess) {
            return false;
        }

        $this->{$this->primary_key} = $wpdb->insert_id;

        return true;
    }

    public function delete()
    {
        global $wpdb;

        if (!$this->getPrimaryKey()) {
            return false;
        }

        $primary_key_type = is_int($this->getPrimaryKey()) ? '%d' : "%s";

        $deleted = $wpdb->delete(
            $this->table,
            [
                $this->primary_key => $this->getPrimaryKey()
            ],
            [
                $primary_key_type
            ]
        );

        return $deleted > 0 ? true : false;
    }

    public function getFillable()
    {
        return array_filter(
            $this->toArray(),
            function ($value, $key) {
                return in_array($key, $this->fillable);
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    public function getPrimaryKey()
    {
        return $this->{$this->primary_key} ?? null;
    }

    protected function initQueryBuilder()
    {
        if (!$this->queryBuilder) {
            $this->queryBuilder = new QueryBuilder();
            $this->queryBuilder->table($this->table);
            $this->queryBuilder->driver($this->driver);
        }
    }
}
