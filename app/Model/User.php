<?php
namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{

    protected $table = 'user';
    protected $fillable = ['name','email','password'];
    protected $hidden = ['password'];
    public $timestamps = true;

    public function roles()
{
    return $this->belongsToMany(\App\Model\Role::class, 'role_user', 'user_id', 'role_id')
                ->withTimestamps();
}
    public function hasRole(string $role): bool { return $this->roles()->where('name',$role)->exists(); }
}
