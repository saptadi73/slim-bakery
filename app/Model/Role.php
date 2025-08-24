<?php
namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name','label'];
    public function users() {
        return $this->belongsToMany(\App\Model\User::class, 'role_user', 'role_id', 'user_id')
                    ->withTimestamps();
    }
}
