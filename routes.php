<?php
declare(strict_types=1);

function routes(string $route, string $method): array {
  // route => [class, method, guard]
  $map = [
    'GET' => [
      '/'          => ['AuthController','showLogin', null],
      '/login'     => ['AuthController','showLogin', null],
      '/dashboard' => ['DashboardController','index', 'auth'],
    ],
    'POST' => [
      '/login'  => ['AuthController','login', null],
      '/logout' => ['AuthController','logout','auth'],
    ],
  ];
  $r = $map[$method][$route] ?? null;
  if ($r) return $r;
  $route = rtrim($route,'/');
  return $map[$method][$route] ?? ['AuthController','showLogin', null];
}
