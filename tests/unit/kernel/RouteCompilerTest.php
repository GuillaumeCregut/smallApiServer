<?php

use App\Kernel\RouteCompiler;
use PHPUnit\Framework\TestCase;


class RouteCompilerTest extends TestCase
{

    private array $routes = [
        'user/{id}/post/{postId}' => [
            'GET' => ['UserController::class', 'getPost'],
            'PUT' => ['UserController::class', 'setPost'],
        ],
        'user/{id}' =>  [
            'GET' => ['UserController::class', 'getOneData'],
            'PUT' => ['UserController::class', 'setDatas'],
        ],
        'user'  =>  [
            'GET' => ['UserController::class', 'getDatas'],
            'POST' => ['UserController::class', 'setDatas'],
        ],
        'product'  =>  [
            'GET' => ['ProductController::class', 'getDatas'],
            'POST' => ['ProductController::class', 'setDatas'],
        ],
        'product/{slug}' =>  [
            'GET' => ['ProductController::class', 'getOneData'],
            'PUT' => ['ProductController::class', 'setDatas'],
        ],
    ];
    //testing compiler
    public function testSingleRoute(): void
    {
        $uri = 'user';
        $route = 'user';
        $pattern = RouteCompiler::compile($route);
        $this->assertEquals("~^user$~", $pattern);
        $this->assertEquals(1, preg_match($pattern, $uri));
    }

    public function testOnlyFindWithNoId(): void
    {
        $count = 0;
        $uri = 'user';
        $routes = [
            'only' => 'user',
            'withId' => 'user/{id}',
            'NotToBe' => 'user/{id}/post'
        ];
        $ExpectArray = [
            'only',
        ];
        $resultArray = [];
        foreach ($routes as $key => $route) {
            $pattern = RouteCompiler::compile($route);
            $result = preg_match($pattern, $uri);
            $count += $result;
            if ($result !== 0) {
                $resultArray[] = $key;
            }
        }
        $this->assertEquals(1, $count);
        $this->assertEquals($ExpectArray, $resultArray);
    }

    public function testOnlyFindWithId(): void
    {
        $count = 0;
        $uri = 'user/12';
        $routes = [
            'only' => 'user',
            'withId' => 'user/{id}',
            'NotToBe' => 'user/{id}/post'
        ];
        $ExpectArray = [
            'withId',
        ];
        $resultArray = [];
        foreach ($routes as $key => $route) {
            $pattern = RouteCompiler::compile($route);
            $result = preg_match($pattern, $uri);
            $count += $result;
            if ($result !== 0) {
                $resultArray[] = $key;
            }
        }
        $this->assertEquals(1, $count);
        $this->assertEquals($ExpectArray, $resultArray);
    }

    public function testIfInverseOnlyOne(): void
    {
        $count = 0;
        $uri = 'user';
        $routes = [
            'NotToBe' => 'user/{id}/post',
            'only' => 'user',
            'withId' => 'user/{id}',
        ];
        $ExpectArray = [
            'only',
        ];
        $resultArray = [];
        foreach ($routes as $key => $route) {
            $pattern = RouteCompiler::compile($route);
            $result = preg_match($pattern, $uri);
            $count += $result;
            if ($result !== 0) {
                $resultArray[] = $key;
            }
        }
        $this->assertEquals(1, $count);
        $this->assertEquals($ExpectArray, $resultArray);
    }

    public function testCompileWithPlaceholderProducesNamedGroup(): void
    {
        $pattern = RouteCompiler::compile('user/{id}');
        $this->assertEquals('~^user/(?P<id>[^/]+)$~', $pattern);
    }

    public function testCompileWithMultiplePlaceholders(): void
    {
        $pattern = RouteCompiler::compile('user/{id}/post/{postId}');
        $this->assertEquals('~^user/(?P<id>[^/]+)/post/(?P<postId>[^/]+)$~', $pattern);
    }

    public function testNamedCapturesAreExtracted(): void
    {
        $pattern = RouteCompiler::compile('user/{id}/post/{postId}');
        preg_match($pattern, 'user/42/post/7', $matches);

        $this->assertEquals('42', $matches['id']);
        $this->assertEquals('7', $matches['postId']);
    }


    public function testPlaceholderMatchesStringValue(): void
    {
        $pattern = RouteCompiler::compile('products/{slug}');
        $this->assertEquals(1, preg_match($pattern, 'products/my-product-name'));
    }

    public function testPlaceholderDoesNotMatchSlash(): void
    {
        $pattern = RouteCompiler::compile('user/{id}');
        $this->assertEquals(0, preg_match($pattern, 'user/12/extra'));
    }

    public function testNoPartialMatch(): void
    {
        $pattern = RouteCompiler::compile('user/{id}');
        $this->assertEquals(0, preg_match($pattern, 'user/12/post'));
    }

    // Trailing slash should not match
    public function testTrailingSlashDoesNotMatch(): void
    {
        $pattern = RouteCompiler::compile('user');
        $this->assertEquals(0, preg_match($pattern, 'user/'));
    }


    public function testDeepNestedRoute(): void
    {
        $pattern = RouteCompiler::compile('user/{id}/post/{postId}');

        $this->assertEquals(1, preg_match($pattern, 'user/42/post/7'));
        $this->assertEquals(0, preg_match($pattern, 'user/42/post'));
        $this->assertEquals(0, preg_match($pattern, 'user/42'));
    }

    //Testing finder
    public function testStaticRouteMatches(): void
    {
        $result = RouteCompiler::findRoute('user', $this->routes);

        $this->assertNotNull($result);
        $this->assertEquals('user', $result['routeName']);
        // No params on a static route
        $this->assertArrayNotHasKey('id', $result);
    }

    public function testAnotherStaticRouteMatches(): void
    {
        $result = RouteCompiler::findRoute('product', $this->routes);

        $this->assertNotNull($result);
        $this->assertEquals('product', $result['routeName']);
        $this->assertArrayNotHasKey('id', $result);
    }

    public function testRouteWithSingleParam(): void
    {
        $result = RouteCompiler::findRoute('user/42', $this->routes);

        $this->assertNotNull($result);
        $this->assertEquals('user/{id}', $result['routeName']);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('42', $result['id']);
    }

    public function testRouteWithMultipleParams(): void
    {
        $result = RouteCompiler::findRoute('user/42/post/7', $this->routes);

        $this->assertNotNull($result);
        $this->assertEquals('user/{id}/post/{postId}', $result['routeName']);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('42', $result['id']);
        $this->assertEquals('7', $result['postId']);
    }

    public function testRouteWithStringSlug(): void
    {
        $result = RouteCompiler::findRoute('product/my-product', $this->routes);

        $this->assertNotNull($result);
        $this->assertEquals('product/{slug}', $result['routeName']);
        $this->assertArrayHasKey('slug', $result);
        $this->assertEquals('my-product', $result['slug']);
    }

    public function testUnknownRouteReturnsNull(): void
    {
        $result = RouteCompiler::findRoute('unknown', $this->routes);
        $this->assertNull($result);
    }

    public function testPartialRouteReturnsNull(): void
    {
        $result = RouteCompiler::findRoute('user/42/post', $this->routes);
        $this->assertNull($result);
    }

    public function testTrailingSlashReturnsNull(): void
    {
        $result = RouteCompiler::findRoute('user/', $this->routes);
        $this->assertNull($result);
    }


    public function testParamsContainOnlyNamedKeys(): void
    {
        $result = RouteCompiler::findRoute('user/42/post/7', $this->routes);

        // No numeric keys (from preg_match) should leak through
        $this->assertArrayNotHasKey(0, $result);
        $this->assertArrayNotHasKey(1, $result);
        $this->assertArrayNotHasKey(2, $result);
    }

    public function testParamsIsEmptyArrayForStaticRoute(): void
    {
        $result = RouteCompiler::findRoute('product', $this->routes);

        $this->assertCount(1, $result);
    }

    public function testMoreSpecificRouteMatchesBeforeLessSpecific(): void
    {
        // Ensures 'user/{id}/post/{postId}' wins over 'user/{id}' for deep URLs
        $result = RouteCompiler::findRoute('user/42/post/7', $this->routes);
        $this->assertEquals('user/{id}/post/{postId}', $result['routeName']);
    }

    public function testParamValueIsAlwaysString(): void
    {
        $result = RouteCompiler::findRoute('user/42', $this->routes);
        $this->assertIsString($result['id']); // preg_match always returns strings
    }
}
