<?php

declare(strict_types=1);

namespace Apps\App1\Endpoints;

use Apps\App1\DTO\CreateUserRequest;
use Apps\App1\DTO\UserDto;
use Apps\App1\Middleware\AppHeaderMiddleware;
use Apps\App1\Services\UserRepository;
use Bluewater\Auth\ApiKeyMiddleware;
use Bluewater\Endpoint\Endpoint;
use Bluewater\Http\Response;
use Bluewater\Middleware\UseMiddleware;
use Bluewater\OpenApi\Summary;

#[UseMiddleware(AppHeaderMiddleware::class)]
final class Users extends Endpoint
{
    #[Summary('List users')]
    public function get(UserRepository $users): array
    {
        return $users->all();
    }

    #[Summary('Get a user')]
    public function getById(int $id, UserRepository $users): UserDto|Response
    {
        return $users->find($id) ?? Response::problem(404, 'User not found');
    }

    #[Summary('Create a user')]
    public function post(CreateUserRequest $input, UserRepository $users): UserDto
    {
        return $users->create($input->email, $input->name);
    }

    #[Summary('Delete a user')]
    #[UseMiddleware(ApiKeyMiddleware::class)]
    public function deleteById(int $id, UserRepository $users): Response
    {
        return $users->delete($id) ? Response::noContent() : Response::problem(404, 'User not found');
    }
}
