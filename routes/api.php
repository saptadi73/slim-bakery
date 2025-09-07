<?php

use Slim\App;
use App\Services\AuthService;
use App\Services\UserService;
use App\Services\RoleService; // Tambahkan RoleService jika diperlukan
use App\Supports\JsonResponder;
use App\Supports\RequestHelper;
use Firebase\JWT\JWT;
use App\Middlewares\JwtMiddleware;

return function (App $app) {
    // Route home
    $app->get('/', function ($request, $response, $args) {
        $response->getBody()->write("Hello Slim 4 + Eloquent ORM!");
        return $response;
    });

    // Registrasi pengguna dengan role
    $app->post('/register', function ($request, $response) {
        $data = RequestHelper::getJsonBody($request);

        // Validasi input
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            return JsonResponder::error($response, 'Invalid input', 400);
        }

        // Tentukan role_id default (misalnya 'User' dengan id 1)
        $role = \App\Models\Role::where('name', 'User')->first(); // Sesuaikan dengan role yang ada
        if (!$role) {
            return JsonResponder::error($response, 'Role not found', 404);
        }

        // Panggil fungsi register untuk membuat user baru
        $user = AuthService::register($data['name'], $data['email'], $data['password'], $role->id);

        return JsonResponder::success($response, $user, 'User registered');
    });

    // Login pengguna
    $app->post('/login', function ($request, $response) {
        $data = RequestHelper::getJsonBody($request);
        if (empty($data['email']) || empty($data['password'])) {
            return JsonResponder::error($response, 'Invalid input', 400);
        }
        $result = AuthService::login($data['email'], $data['password']);
        if ($result['success']) {
            return JsonResponder::success($response, ['token' => $result['token'],'user'=>$result['user']], 'Login success');
        }
        return JsonResponder::error($response, $result['message'], 401);
    });

    $app->post('/update/role', function ($request, $response) {
        $data = RequestHelper::getJsonBody($request);
        if (empty($data['user_id']) || empty($data['role_id'])) {
            return JsonResponder::error($response, 'Invalid input', 400);
        }

        // Cari user berdasarkan ID
        $result = AuthService::updateUserRole($data['user_id'], $data['role_id']);
        if ($result['success']) {
            return JsonResponder::success($response, $result['user'], 'User role updated');
        }else {
            return JsonResponder::error($response, $result['message'], 400);
        }
    })->add(new JwtMiddleware());

    // Mengambil profil pengguna
    $app->get('/profile', function ($request, $response) {
        $jwt = $request->getAttribute('jwt');
        $user = \App\Models\User::find($jwt['sub']); // Ambil user berdasarkan JWT

        // Mengambil informasi role
        $role = $user->role;  // Menampilkan role terkait pengguna

        return JsonResponder::success($response, [
            'user' => $user,
            'role' => $role
        ], 'Profile data');
    })->add(new JwtMiddleware());

    // Logout pengguna
    $app->post('/logout', function ($request, $response) {
        $jwt = $request->getAttribute('jwt');
        if (!$jwt) {
            return JsonResponder::error($response, 'Invalid or missing token', 401);
        }
        return JsonResponder::success($response, [], 'Logout success');
    })->add(new JwtMiddleware());

    // Update informasi pengguna
    $app->post('/user/update/{id}', function ($request, $response, $args) {
        $id = $args['id'];
        $data = RequestHelper::getJsonBody($request);

        // Update data user
        $user = UserService::update($id, $data);
        if ($user) {
            return JsonResponder::success($response, $user, 'User updated');
        }
        return JsonResponder::error($response, 'User not found', 404);
    })->add(new JwtMiddleware());

    // Hapus pengguna
    $app->post('/user/delete/{id}', function ($request, $response, $args) {
        $id = $args['id'];
        $deleted = UserService::delete($id);
        if ($deleted) {
            return JsonResponder::success($response, [], 'User deleted');
        }
        return JsonResponder::error($response, 'User not found', 404);
    })->add(new JwtMiddleware());
};
