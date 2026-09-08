<?php

namespace Tests\Feature;

use App\Models\UsageEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsageDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.usage.index'))->assertRedirect(route('login'));
    }

    public function test_regular_user_cannot_view_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.usage.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_all_regular_users_usage(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['name' => '利用者A', 'email' => 'user-a@example.com']);
        UsageEvent::create(['user_id' => $user->id, 'event_name' => 'today.view', 'occurred_at' => now()]);

        $this->actingAs($admin)
            ->get(route('admin.usage.index'))
            ->assertOk()
            ->assertSee('利用状況一覧')
            ->assertSee('利用者A')
            ->assertSee('user-a@example.com')
            ->assertSee('1日');
    }

    public function test_admin_account_is_not_counted_as_regular_user(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@test.com']);

        $this->actingAs($admin)
            ->get(route('admin.usage.index'))
            ->assertOk()
            ->assertDontSee('admin@test.com')
            ->assertSee('登録ユーザー')
            ->assertViewHas('registeredUsers', 0);
    }
}
