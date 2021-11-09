<?php

namespace QuentinGab\WordpressOrm;

use DateTimeInterface;
use Exception;

/**
 * This is a Wordpress Model
 * you can use it to 
 */
class Wodel extends Base
{
    /**
     * post, page or custom post type
     */
    protected string $post_type = 'page';

    public array $order = [
        'orderby' => 'date',
        'order' => 'DESC'
    ];

    public ?int $limit = -1;

    public array $acf = [];
    public array $acf_keys = [
        // 'my_field' => "field_6189fd5611e1f"
    ];

    /**
     * You can casts property with dot notation
     */
    protected $casts = [
        // "acf.my_field"=>"int"
    ];

    public function __construct(array $data = [], $casts = false)
    {
        $this->fill($data, $casts);
    }

    public function get($limit = null)
    {
        $this->initQueryBuilder();

        if ($limit) {
            $this->limit($limit);
        }

        $posts = get_posts(
            $this->queryBuilder->buildWpQuery()
        );

        $posts = array_map(
            function ($item) {
                $data = get_object_vars($item);

                if (function_exists('get_fields')) {
                    $data_acf = get_fields($data['ID']);
                    if ($data_acf) {
                        $data['acf'] = $data_acf;
                    }
                }

                return new static(
                    $data,
                    true
                );
            },
            $posts
        );

        return $posts;
    }

    public function all()
    {
        return $this->get(-1);
    }

    public function find($id)
    {
        $this->where('p', $id);
        $this->where('post_status', "all");

        return $this->first();
    }

    public function current()
    {
        return $this->find(get_the_ID());
    }

    public function first()
    {
        $posts = $this->get(1);

        return empty($posts) ? null : $posts[0];
    }

    public function save()
    {
        $isNewPost = isset($this->ID);

        if ($isNewPost) {
            $this->creating();
        } else {
            $this->updating();
        }

        $result = wp_insert_post(
            [
                'ID' => $this->ID ?? 0,
                'post_content' => $this->post_content ?? '',
                'post_title' => $this->post_title ?? '',
                'post_excerpt' => $this->post_excerpt ?? '',
                'post_status' => $this->post_status ?? 'draft',
                'post_type' => $this->post_type ?? 'post',
                'comment_status' => $this->comment_status ?? '',
                'post_password' => $this->post_password ?? '',
                'post_parent' => $this->post_parent ?? 0
            ]
        );

        if (!$result) {
            throw new Exception("Post {$this->ID}:{$this->post_title} can't be saved");
        }

        if ($result) {
            $this->ID = $result;
        }

        //save acf fields
        if (function_exists('update_field') and $this->acf) {
            foreach ($this->acf as $name => $value) {
                if (static::dotGet($this->casts, "acf.$name") === "date") {
                    $value = date_format(
                        $value instanceof DateTimeInterface ? $value : date_create($value),
                        'Y-m-d H:i:s'
                    );
                }

                // prefer update with keys if provided
                if (
                    $this->acf_keys and
                    $this->acf_keys[$name]
                ) {
                    $name = $this->acf_keys[$name];
                }

                update_field($name, $value, $this->ID);
            }

            // do_action('acf/save_post', $this->ID);
        }

        return $this;
    }

    public function delete()
    {
        $this->deleting();

        return !!wp_delete_post($this->ID);
    }

    public function permalink()
    {
        return get_post_permalink($this->ID);
    }

    public function content()
    {
        return apply_filters('the_content', $this->post_content);
    }

    protected function initQueryBuilder()
    {
        if (!$this->queryBuilder) {
            $this->queryBuilder = new QueryBuilder();
            $this->queryBuilder->where('post_type', '=', $this->post_type);
            $this->queryBuilder->where('post_status', '=', "all");
        }
    }
}
