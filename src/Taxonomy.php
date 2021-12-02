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
        $term = get_term_by('name', $name, $this->term_name);
        if ($term) {
            return $term;
        } else {
            wp_insert_term(
                $name,
                $this->term_name
            );
            $term = get_term_by('name', $name, $this->term_name);
        }

        return $term;
    }

    public static function find($id)
    {
        return new self(get_term($id)->to_array());
    }

    public function children()
    {
        $this->children = array_map(
            function ($item) {
                return self::find($item);
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
}
