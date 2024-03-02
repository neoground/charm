<?php
/**
 * This file contains the UserModel class.
 */

namespace Charm\Vivid\Base;


use Charm\Vivid\Model;

/**
 * Class UserModel
 *
 * Base User Model class
 *
 * @package Charm\Vivid\Base
 */
class UserModel extends Model
{
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'secret',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]|bool
     */
    protected $guarded = ['updated_at'];

    /**
     * Get default (system) user
     *
     * @return self
     */
    public static function getDefaultUser(): self
    {
        return self::find(1);
    }

    /**
     * Get the display name of user.
     *
     * This is used by the framework internally.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        $fields = ['display_name', 'username', 'name', 'firstname'];
        foreach ($fields as $field) {
            $name = $this->$field;
            if (!empty($name)) {
                return $name;
            }
        }
        return '';
    }

    /**
     * Get language string of user
     *
     * This will be used on login via Guard and automatically set the
     * language based on the user's settings
     *
     * @return string
     */
    public function getLanguage(): string
    {
        return 'en';
    }

}