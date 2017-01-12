<?php
namespace FatPanda\Illuminate\WordPress\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Support\Collection;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{   
    static $profileSettingMetaKeyPrefix = 'profile.';

    use Authenticatable;

    protected $primaryKey = 'ID';

    protected $table = 'users';

    /**
     * Keep a cached copy of the WP_User object paired to this
     * User Model. Update it anytime the User model is modified.
     */
    protected $wp_user;

    protected $dates = [ 
        'user_registered',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_nicename', 'user_email',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'user_pass',
    ];

    static function boot()
    {
        static::saved(function($user) {
            $user->refreshWpUserCache();
        });
    }

    function refreshWpUserCache()
    {   
        if (empty($this->id)) {
            return false;
        }
        if (empty($this->wp_user)) {
            $this->wp_user = get_user_by('ID', $this->id);
        }
    }

    function getIdAttribute()
    {
        return !empty($this->attributes['ID']) ? $this->attributes['ID'] : null;
    }

    function setIdAttribute($value)
    {
        $this->attributes['ID'] = $value;
    }

    function getFirstNameAttribute()
    {
        return $this->meta('first_name')->first();
    }

    function getLastNameAttribute()
    {
        return $this->meta('last_name')->first();
    }

    function getNameAttribute()
    {
        $name = trim("{$this->first_name} {$this->last_name}");
        if (empty($name)) {
            $name = $this->display_name;
        }
        return $name;
    }

    function meta($name = null)
    {
        return new Collection(get_user_meta($this->id, $name));
    }

    function updateMeta($name, $value) {
        return update_user_meta($this->id, $name, $value);
    }

    function addMeta($name, $value) {
        return add_user_meta($this->id, $name, $value);
    }

    function getGravatarAttribute()
    {
        return '//www.gravatar.com/avatar/' . md5(strtolower($this->user_email)) . '.jpg';
    }

    function can($ability, $arguments = [])
    {
        if (empty($this->wp_user)) {
            $this->refreshWpUserCache();
        }

        if (empty($this->wp_user)) {
            return false;
        }

        $args = array_slice( func_get_args(), 1 );
        $args = array_merge( array( $ability ), $args );

        return call_user_func_array( array( $this->wp_user, 'has_cap' ), $args );
    }

    function getProfileSettings($name = '')
    {
        $settings = [];

        if ($name) {

            $settings[$name] = new Collection( get_user_meta($this->id, static::$profileSettingMetaKeyPrefix.$name) );

        } else {

            if (!$meta = get_user_meta($this->id)) {
                return $settings;
            }

            foreach($meta as $meta_key => $values) {
                if (substr($meta_key, 0, strlen(static::$profileSettingMetaKeyPrefix)) === static::$profileSettingMetaKeyPrefix) {
                    $settings[substr($meta_key, strlen(static::$profileSettingMetaKeyPrefix))] = new Collection( $values );
                }
            }

        }

        return new Collection( $settings );
    }

    function deleteProfileSetting($name)
    {
        return delete_user_meta($this->id, static::$profileSettingMetaKeyPrefix.$name);
    }

    function addProfileSetting($name, $value = null, $unique = false)
    {
        $meta_key = static::$profileSettingMetaKeyPrefix.$name;
        add_user_meta($this->id, $meta_key, $value, $unique);
        return $this->getProfileSettings($name);
    }

    function updateProfileSetting($name, $value = null)
    {
        $meta_key = static::$profileSettingMetaKeyPrefix.$name;
        update_user_meta($this->id, $meta_key, $value);
        return $this->getProfileSettings($name);
    }

    static function makePrivateActionLink($user, $userMetaField = '_private', $flush = false, $length = 12)
    {
        $id = base64_encode($user->ID);
        
        $key = $flush ? false : get_user_meta($user->ID, $userMetaField, true);
        if (!$key) {
            $key = wp_generate_password($length);
            update_user_meta($user->ID, $userMetaField, $key);
        }

        $time = time();
        $nonce = md5($id.$key.$time);

        $params = [ 
            'i' => $id,
            't' => $time,
            'n' => $nonce
        ];

        $hash = base64_encode(json_encode($params));

        return (object) [
            'key' => $key,
            'hash' => $hash
        ];
    }
    
    static function verifyPrivateActionHash($hash, $userMetaField = '_private')
    {
        $params = (array) json_decode(base64_decode($hash));

        if ($params === false || empty($params)) {
            // TODO: allow for login URL to be configurable
            return false;
        }

        $user = get_user_by('ID', base64_decode($params['i']));
        if (!$user) {
            return false;
        }
        
        // make sure that the nonce hasn't expired
        if (time() - $params['t'] > ( 60 * 60 * 24 )) {
            // if it's still there, make sure it's gone!
            delete_user_meta($user->ID, $userMetaField);
            return false;
        }

        if (!$key = get_user_meta($user->ID, $userMetaField, true)) {
            return false;
        }

        // validate the nonce
        if ($params['n'] !== md5($params['i'].$key.$params['t'])) {
            return false;
        }

        return $user;
    }

    /**
     * Get the current user.
     * @return User instance mapped to current WP_User;
     * otherwise returns false.
     */
    static function current()
    {
        $current = false;

        if (is_user_logged_in()) {
            $current = new static();
            $user = wp_get_current_user();

            // $current->wp_user = $user;
            
            foreach($user->data as $name => $value) {
                $current->$name = $value;
            }   
        }

        return $current;
    }
}
