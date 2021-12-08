<?php

namespace QuentinGab\WordpressOrm;

use \WP_User;

class User
{

    protected $fillable = [
        'user_nicename',
        'user_email',
        'user_pass',
        'display_name',
        // add user meta here
    ];

    protected $meta = [
        // add user meta here
    ];

    protected $casts = [
        'ID' => 'int',
    ];

    /**
     * store wordpress object user
     */
    public $wp_user = null;

    public function __construct(array $data = [])
    {
        $this->fill($data);
    }

    /**
     * Retreive all users
     */
    public static function all()
    {
        $args = array(
            'fields' => 'all',
        );

        $wp_users = collect(get_users($args));

        return $wp_users->map(function ($wp_user) {
            $user = new static();
            $user->wp_user = $wp_user;
            $user->fill($wp_user->to_array(), true);
            $user->fill($user->get_meta(), true);

            return $user;
        });
    }

    public static function find($id)
    {
        $user = new static();

        $wp_user = new WP_User($id);
        if (!$wp_user) {
            return false;
        }

        $user->wp_user = $wp_user;

        $user->fill($wp_user->to_array(), true);
        $user->fill($user->get_meta(), true);

        return $user;
    }

    public function save()
    {
        if (static::exists($this->user_email)) {
            return $this->update();
        } else {
            return $this->create();
        }
    }

    public function update()
    {
        foreach ($this->meta as $key) {
            update_user_meta($this->ID, $key, $this->{$key});
        }

        return wp_update_user($this->userFields());
    }

    public function create()
    {
        $data = $this->userFields();

        $data['user_login'] = $this->user_email;
        $data['user_pass'] = $this->user_pass;

        $user_id = wp_insert_user($data);

        if (is_wp_error($user_id)) {
            return false;
        }

        $this->ID = $user_id;

        foreach ($this->meta as $key) {
            \update_user_meta($this->ID, $key, $this->{$key});
        }

        //hydrate instance with fresh user data
        $wp_user = new WP_User($user_id);
        if (!$wp_user) {
            return false;
        }
        $this->wp_user = $wp_user;
        $this->fill($wp_user->to_array(), true);
        $this->fill($this->get_meta(), true);

        return true;
    }

    public function updatePassword($password)
    {

        if (\wp_check_password($password, $this->user_pass, $this->ID)) {
            return false;
        }

        \wp_set_password($password, $this->ID);
        return true;
    }

    public function delete()
    {
        return wp_delete_user($this->ID);
    }


    public function get_meta()
    {
        $data = [];
        foreach ($this->meta as $key) {
            if ($this->wp_user->has_prop($key)) {
                $data[$key] = $this->wp_user->get($key);
            } else {
                $data[$key] = null;
            }
        }
        return $data;
    }

    /**
     * check user with this email is already registered
     */
    public static function exists($email)
    {
        return \email_exists($email) !== false;
    }

    /**
     * retrieve the current loggedin user
     */
    public static function current()
    {
        if (!is_user_logged_in()) {
            return false;
        }

        return static::find(get_current_user_id());
    }

    public function permissions()
    {
        if (!$this->wp_user) return;

        return $this->wp_user->allcaps;
    }

    public function roles()
    {
        if (!$this->wp_user) return;

        return $this->wp_user->roles;
    }

    public function can($roleOrPermission)
    {
        return in_array($roleOrPermission, $this->roles());
    }

    /**
     * Retreive only user field ready to insert in db with wp_insert_user
     */
    public function userFields()
    {
        return [
            'ID' => $this->ID,
            'user_email' => $this->user_email,
            'user_pass' => $this->user_pass,
            'user_login' => $this->user_login,
            'user_nicename' => $this->user_nicename,
            'display_name' => $this->display_name,
        ];
    }

    public function fill($array)
    {

        if (!is_array($array)) {
            return false;
        }

        foreach ($array as $key => $value) {
            $this->{$key} = $this->cast_field($key, $value);
        }

        return $this;
    }

    protected function cast_field($key, $value)
    {
        if (array_key_exists($key, $this->casts)) {
            $type = $this->casts[$key];
            switch ($type) {
                case 'int':
                    return intval($value);
                case 'bool':
                    return boolval($value);
                case 'string':
                    return strval($value);
            }
        }
        return $value;
    }


    public function toArray()
    {
        return [
            'ID' => $this->ID,
            'user_email' => $this->user_email,
            'user_login' => $this->user_login,
            'user_nicename' => $this->user_nicename,
            'display_name' => $this->display_name,
        ];
    }

    public function toJson()
    {
        return json_encode($this->toArray());
    }
}
