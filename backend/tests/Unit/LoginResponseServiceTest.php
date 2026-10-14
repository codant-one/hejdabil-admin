<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\LoginResponseService;
use Mockery;
use Tests\TestCase;

class LoginResponseServiceTest extends TestCase
{
    public function test_token_data_preserves_the_existing_helpers_relations_and_keys(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->forceFill([
            'id' => 7,
            'email' => 'user@example.test',
            'name' => 'Test',
            'last_name' => 'User',
            'is_2fa' => 0,
            'password' => 'hashed-password',
            'full_profile' => true,
        ]);
        $user->setRelation('userDetail', null);
        $user->setRelation('supplier', null);
        $user->setRelation('offers', collect());
        $user->setRelation('commissions', collect());
        $user->shouldReceive('getAllPermissions')->once()->andReturn(collect([
            (object) ['name' => 'administrator'],
            (object) ['name' => 'view dashboard'],
        ]));
        $user->shouldReceive('getRoleNames')->once()->andReturn(collect(['admin']));
        $user->shouldReceive('load')->once()->with([
            'userDetail',
            'supplier.plan.features:id,name',
            'supplier.plan.featurePlans.feature:id,name',
            'supplier.boss.settings',
            'supplier.boss.settings.billing',
            'supplier.boss.settings.agreement',
            'supplier.boss.plan.features:id,name',
            'supplier.boss.plan.featurePlans.feature:id,name',
            'supplier.boss.user.userDetail',
            'supplier.boss.user.commissions',
            'supplier.boss.user.offers',
            'supplier.boss.billings',
            'supplier.boss.agreements',
            'supplier.settings',
            'supplier.settings.billing',
            'supplier.settings.agreement',
            'supplier.agreements',
            'supplier.billings',
            'supplier.user.commissions',
            'supplier.user.offers',
            'offers',
            'commissions',
        ])->andReturnSelf();

        $data = (new LoginResponseService)->tokenData($user, 'jwt-token');

        $this->assertSame(['accessToken', 'token_type', 'user_data', 'userAbilities'], array_keys($data));
        $this->assertSame('jwt-token', $data['accessToken']);
        $this->assertSame('bearer', $data['token_type']);
        $this->assertSame([
            'id', 'email', 'name', 'is_2fa', 'last_name', 'avatar', 'user_detail',
            'roles', 'hash', 'full_profile', 'supplier', 'offers', 'commissions',
        ], array_keys($data['user_data']));
        $this->assertSame([['name' => 'admin']], $data['user_data']['roles']);
        $this->assertSame($user->password, $data['user_data']['hash']);
        $this->assertSame([
            ['action' => 'view', 'subject' => 'Auth'],
            ['action' => 'manage', 'subject' => 'all'],
            ['action' => 'view', 'subject' => 'dashboard'],
        ], $data['userAbilities']);
    }
}
