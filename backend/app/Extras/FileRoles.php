<?php

    if (!function_exists('getUserData')) {
        function getUserData($user) {
            $rol = [];

            foreach($user->getRoleNames() as $r){
                array_push($rol, array('name' => $r));
            }

            $userData = array(
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'is_2fa' => $user->is_2fa,
                'last_name' => $user->last_name,
                'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : $user->avatar,
                'user_details' => $user->userDetail,
                'roles' => $rol,
                'hash' => $user->password,
                'full_profile' => $user->full_profile,
                'supplier' => $user->supplier
            );

            return $userData;
        }
    }

    if (!function_exists('getPermissionsByRole')) {
        function getPermissionsByRole($user){

            $permissions = $user->getPermissionsViaRoles();
            $abilites = [];

            array_push($abilites, array('action' => 'view', 'subject' => 'Auth'));
            
            foreach($permissions as $permission){

                if($permission->name === 'administrator') {
                    array_push($abilites, array('action' => 'manage', 'subject' => 'all'));
                } else {
                    $values = explode(' ', $permission->name);
                    array_push($abilites, array('action' => $values[0], 'subject' => $values[1]));
                }
            }

            return $abilites;
        }
    }
?>
