<?php

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;
use Tightenco\Ziggy\Ziggy;

class RouteModelBindingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $router = app('router');

        $router->get('users/{user}', function (User $user) {
            return '';
        })->name('users');
        $router->get('tokens/{token}', function ($token) {
            return '';
        })->name('tokens');
        $router->get('users/{user}/{number}', function (User $user, int $n) {
            return '';
        })->name('users.numbers');

        if ($this->laravelVersion(7)) {
            $router->get('blog/{category}/{post:slug}', function (PostCategory $category, Post $post) {
                return '';
            })->name('posts');
            $router->get('blog/{category}/{post:slug}/{tag:slug}', function (PostCategory $category, Post $post, Tag $tag) {
                return '';
            })->name('posts.tags');
        }

        $router->getRoutes()->refreshNameLookups();
    }

    /** @test */
    public function can_register_implicit_route_model_bindings()
    {
        $expected = [
            'users' => [
                'uri' => 'users/{user}',
                'methods' => ['GET', 'HEAD'],
                'bindings' => [
                    'user' => 'uuid',
                ],
            ],
        ];

        $this->assertSame($expected, (new Ziggy)->filter('users')->toArray());
    }

    /** @test */
    public function can_ignore_route_parameters_not_bound_to_eloquent_models()
    {
        $this->assertSame([
            'tokens' => [
                'uri' => 'tokens/{token}',
                'methods' => ['GET', 'HEAD'],
            ],
        ], (new Ziggy)->filter(['tokens'])->toArray());
    }

    /** @test */
    public function can_handle_bound_and_unbound_params_in_the_same_route()
    {
        $expected = [
            'users.numbers' => [
                'uri' => 'users/{user}/{number}',
                'methods' => ['GET', 'HEAD'],
                'bindings' => [
                    'user' => 'uuid',
                ],
            ],
        ];

        $this->assertSame($expected, (new Ziggy)->filter('users.numbers')->toArray());
    }

    /** @test */
    public function can_handle_multiple_scoped_bindings()
    {
        if (! $this->laravelVersion(7)) {
            $this->markTestSkipped('Requires Laravel >=7');
        }

        $this->assertSame([
            'posts' => [
                'uri' => 'blog/{category}/{post}',
                'methods' => ['GET', 'HEAD'],
                'bindings' => [
                    'category' => 'id',
                    'post' => 'slug',
                ],
            ],
            'posts.tags' => [
                'uri' => 'blog/{category}/{post}/{tag}',
                'methods' => ['GET', 'HEAD'],
                'bindings' => [
                    'category' => 'id',
                    'post' => 'slug',
                    'tag' => 'slug',
                ],
            ],
        ], (new Ziggy)->filter('posts*')->toArray());
    }

    /** @test */
    public function can_merge_implicit_and_scoped_bindings()
    {
        if (! $this->laravelVersion(7)) {
            $this->markTestSkipped('Requires Laravel >=7');
        }

        $this->assertSame([
            'users' => [
                'uri' => 'users/{user}',
                'methods' => ['GET', 'HEAD'],
                'bindings' => [
                    'user' => 'uuid',
                ],
            ],
            'tokens' => [
                'uri' => 'tokens/{token}',
                'methods' => ['GET', 'HEAD'],
            ],
            'users.numbers' => [
                'uri' => 'users/{user}/{number}',
                'methods' => ['GET', 'HEAD'],
                'bindings' => [
                    'user' => 'uuid',
                ],
            ],
            'posts' => [
                'uri' => 'blog/{category}/{post}',
                'methods' => ['GET', 'HEAD'],
                'bindings' => [
                    'category' => 'id',
                    'post' => 'slug',
                ],
            ],
            'posts.tags' => [
                'uri' => 'blog/{category}/{post}/{tag}',
                'methods' => ['GET', 'HEAD'],
                'bindings' => [
                    'category' => 'id',
                    'post' => 'slug',
                    'tag' => 'slug',
                ],
            ],
        ], (new Ziggy)->toArray()['namedRoutes']);
    }

    /** @test */
    public function can_include_bindings_in_json()
    {
        if (! $this->laravelVersion(7)) {
            $this->markTestSkipped('Requires Laravel >=7');
        }

        $json = '{"baseUrl":"http:\/\/ziggy.dev\/","baseProtocol":"http","baseDomain":"ziggy.dev","basePort":null,"defaultParameters":[],"namedRoutes":{"users":{"uri":"users\/{user}","methods":["GET","HEAD"],"bindings":{"user":"uuid"}},"tokens":{"uri":"tokens\/{token}","methods":["GET","HEAD"]},"users.numbers":{"uri":"users\/{user}\/{number}","methods":["GET","HEAD"],"bindings":{"user":"uuid"}},"posts":{"uri":"blog\/{category}\/{post}","methods":["GET","HEAD"],"bindings":{"category":"id","post":"slug"}},"posts.tags":{"uri":"blog\/{category}\/{post}\/{tag}","methods":["GET","HEAD"],"bindings":{"category":"id","post":"slug","tag":"slug"}}}}';

        $this->assertSame($json, (new Ziggy)->toJson());
    }
}

class User extends Model
{
    public function getRouteKeyName()
    {
        return 'uuid';
    }
}

class PostCategory extends Model
{
    //
}

class Post extends Model
{
    //
}

class Tag extends Model
{
    //
}
