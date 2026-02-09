<?php

namespace App\Traits;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use Carbon\Carbon;

use App\Models\UserDetails;
use App\Models\Supplier;
use App\Models\Offer;
use App\Models\Commission;

/**
 * Trait for models with stores
 */
trait UserHelper
{
    /**** Relationship ****/
    public function userDetail() {
        return $this->hasOne(UserDetails::class, 'user_id', 'id');
    }

    public function supplier() {
        return $this->hasOne(Supplier::class, 'user_id', 'id');
    }

    public function offers() {
        return $this->hasMany(Offer::class, 'user_id', 'id');
    }

    public function commissions() {
        return $this->hasMany(Commission::class, 'user_id', 'id');
    }

    /**** Public methods ****/
    public function getOnlineAttribute($value) {
        if($value!=null)
            return $this->asDateTime($value);
        else
            return $value;
    }

    public static function updateProfile($request, $user) {
        $user->update([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'full_profile' => true
        ]);

        UserDetails::updateOrCreateUser($request, $user);
        
        return $user;
    }

    public static function createUser($request) {
        $user = self::create([
            'name' => $request->name,
            'last_name' =>  $request->last_name,
            'email' => strtolower($request->email),
            'password' => Hash::make($request->password)
        ]);

        //Si NO es cliente se evalua la existencia del Rol.
        if (!request('is_client'))
            $user->syncRoles($request->roles);

        $user->givePermissionTo('view dashboard');

        UserDetails::updateOrCreateUser($request, $user);

        return $user;
    }

    public static function updateUser($request, $user) {
        $user->update([
            'name' => $request->name,
            'last_name' =>  $request->last_name,
            'email' => strtolower($request->email)
        ]);

        $user->roles()->detach();  
        $user->syncRoles($request->roles);
        
        UserDetails::updateOrCreateUser($request, $user);

        return $user;
    }

    public static function deleteUser($id) {
        $user = self::find($id);
        $user->delete();
    }

    public static function activateUser($id) {
        $user = self::onlyTrashed()->where('id', $id)->first();
        $user->restore();
    }

    public static function getOnline($request) {

        $users = self::select('id','online')
                     ->whereIn('id', explode(',', $request->ids))
                     ->get();

        return $users;
    }

    public static function updateAvatar($request, $user) {

        if ($request->hasFile('image')) {
            $image = $request->file('image');

            $path = 'avatars/';

            $file_data = uploadFile($image, $path, $user->avatar);

            $user->update([
                'avatar' => $file_data['filePath']
            ]);
        }
        
        return $user;
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where('name', 'LIKE', '%' . $search . '%')
            ->orWhere('last_name', 'LIKE', '%' . $search . '%')
            ->orWhere('email', 'LIKE', '%' . $search . '%')
            ->orWhereRaw("CONCAT(name, ' ', last_name) LIKE ?", ['%' . $search . '%'])
            ->orWhereHas('roles', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'LIKE', '%' . $search . '%');
                });
            })
            ->orWhereHas('userDetail', function ($dq) use ($search) {
                $dq->where('personal_phone', 'LIKE', '%' . $search . '%');
            });
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy) {
        $query->orderBy($orderByField, $orderBy);
    }
    
    public function scopeApplyFilters($query, array $filters) {
        $filters = collect($filters);

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('role_name')) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('name', $filters->get('role_name'));
            });
        }

        if ($filters->get('orderByField') || $filters->get('orderBy')) {
            $field = $filters->get('orderByField') ? $filters->get('orderByField') : 'created_at';
            $orderBy = $filters->get('orderBy') ? $filters->get('orderBy') : 'asc';
            $query->whereOrder($field, $orderBy);
        }
    }

    public function scopePaginateData($query, $limit) {
        if ($limit == 'all') {
            return collect(['data' => $query->get()]);
        }

        return $query->paginate($limit);
    }

}
