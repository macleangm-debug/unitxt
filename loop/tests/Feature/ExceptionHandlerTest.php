<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ExceptionHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_admin_url_opens_admin_login_instead_of_crashing(): void
    {
        $this->get('/admin')
            ->assertRedirect(route('staff.login', ['admin' => 1]));

        $this->get(route('staff.login', ['admin' => 1]))
            ->assertOk()
            ->assertSee(__('loop.admin_login'), false)
            ->assertSee('710000000', false)
            ->assertSee(__('loop.demo_credentials'), false);
    }

    public function test_missing_page_uses_branded_error_and_is_listed_for_admin(): void
    {
        $this->get('/this-page-is-not-a-loop-route')
            ->assertNotFound()
            ->assertSee(__('loop.error_404_title'), false)
            ->assertSee(__('loop.error_response'), false);

        $this->assertDatabaseHas('exception_hits', [
            'path' => '/this-page-is-not-a-loop-route',
            'status_code' => 404,
        ]);

        $admin = User::factory()->admin()->create(['phone' => '710222099']);

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.errors.index'))
            ->assertOk()
            ->assertSee(__('loop.admin_errors'), false)
            ->assertSee('/this-page-is-not-a-loop-route', false);

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(trans_choice('loop.admin_errors_banner', 1, ['count' => 1]), false)
            ->assertSee('admin-bell', false)
            ->assertSee('admin-nav__badge', false)
            ->assertDontSee('admin-alert', false);

        $hit = \App\Models\ExceptionHit::query()->where('path', '/this-page-is-not-a-loop-route')->first();
        $this->assertNotNull($hit);

        $this->actingAs($admin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.errors.show', $hit))
            ->assertOk()
            ->assertSee(__('loop.admin_errors_detail_blurb'), false)
            ->assertSee('/this-page-is-not-a-loop-route', false)
            ->assertSee(class_basename($hit->exception_class), false)
            ->assertSee(__('loop.error_mark_fixed'), false)
            ->assertSee(__('loop.error_visit'), false);
    }

    public function test_server_error_uses_branded_page_and_records_the_url(): void
    {
        Route::middleware('web')->get('/__loop-test-boom', function () {
            throw new RuntimeException('Loop test boom');
        });

        $this->get('/__loop-test-boom')
            ->assertStatus(500)
            ->assertSee(__('loop.error_500_title'), false)
            ->assertSee('Loop test boom', false);

        $this->assertDatabaseHas('exception_hits', [
            'path' => '/__loop-test-boom',
            'status_code' => 500,
            'message' => 'Loop test boom',
        ]);
    }

    public function test_legacy_login_url_opens_loop_instead_of_a_broken_page(): void
    {
        $this->get('/login')->assertRedirect(route('home'));
        $this->get('/login?admin=1')->assertRedirect(route('staff.login', ['admin' => 1]));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('login'));
    }
}
