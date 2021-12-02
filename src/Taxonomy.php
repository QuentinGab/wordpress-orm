<?php

namespace QuentinGab\WordpressOrm;

/**
 * Represent a wordpress taxonomy/term
 */
class Taxonomy extends Base
{
    protected string $term_name = 'category';

    public function findOrCreate($name)
    {
        $name = ucfirst(strtolower($name));
        $term = static::init()->findByName($name);

        if (!$term) {
            wp_insert_term(
                $name,
                $this->term_name
            );
            $term = static::init()->findByName($name);
        }

        return $term;
    }

    /**
     * @param int|null $limit
     * @return array
     */
    public function get($limit = null)
    {
        $this->initQueryBuilder();

        if ($limit) {
            $this->limit($limit);
        }

        $posts = get_terms(
            $this->queryBuilder->buildWpQuery()
        );

        $posts = array_map(
            function ($item) {
                $data = $item->to_array();

                return new static(
                    $data,
                    true
                );
            },
            $posts
        );

        return $posts;
    }

    public function find($id)
    {
        return new self(get_term($id)->to_array());
    }

    public function findByName($name)
    {
        $term = get_term_by('name', $name, $this->term_name);
        if (!$term) return null;

        return new self($term->to_array());
    }

    public function children()
    {
        $this->children = array_map(
            function ($item) {
                return self::init()->find($item);
            },
            get_term_children($this->term_id, $this->term_name)
        );
        return $this->children;
    }

    public function toArray()
    {
        return [
            'term_id' => $this->term_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'count' => $this->count,
        ];
    }

    protected function initQueryBuilder()
    {
        if (!$this->queryBuilder) {
            $this->queryBuilder = new QueryBuilder();
            $this->queryBuilder->where('taxonomy', '=', $this->term_name);
            $this->queryBuilder->where('hide_empty', '=', false);
        }
    }
}
