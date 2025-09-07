<?php
namespace App\Services;

use App\Models\Role;
use  App\Supports\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;
class RoleService
{
    public static function listRoles(Response $response)
    {
        try {
            $roles = Role::all();
            return JsonResponder::success($response, $roles, 'Daftar role berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }
}
?>