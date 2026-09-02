<?php

/**
 * @file users.php
 * @path examples/host/app/app_1/Endpoints/users.php
 * @version 1.0.0
 * @date 2026-05-20
 * @author Walter Torres
 * @copyright Copyright 2026, Bluewater.
 * @license OSL-3.0
 * @maintainer Bluewater Team
 * @status dev
 *
 * Defines the example users HTTP endpoint and its serialized response contract.
 */

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

/**
 * Demonstrates list, lookup, creation, and deletion of example users.
 *
 * AppHeaderMiddleware decorates every response, while deletion additionally
 * requires API-key authentication. Persistence and domain operations remain in
 * UserRepository; the endpoint owns transport status selection only.
 */
#[UseMiddleware(AppHeaderMiddleware::class)]
final class Users extends Endpoint
{
    /** @return list<UserDto> Users ordered by persistent identifier. */
    #[Summary('List users')]
    public function get(UserRepository $users): array
    {
        return $users->all();
    }

    /**
     * Returns one user or a credential-free 404 problem response.
     *
     * @param positive-int $id Persistent user identifier from the route.
     */
    #[Summary('Get a user')]
    public function getById(int $id, UserRepository $users): UserDto|Response
    {
        return $users->find($id) ?? Response::problem(404, 'User not found');
    }

    /** Creates and returns a user from an already validated request DTO. */
    #[Summary('Create a user')]
    public function post(CreateUserRequest $input, UserRepository $users): UserDto
    {
        return $users->create($input->email, $input->name);
    }

    /**
     * Deletes one user after API-key authentication.
     *
     * @param positive-int $id Persistent user identifier from the route.
     *
     * @return Response Empty 204 on deletion or a 404 problem when absent.
     */
    #[Summary('Delete a user')]
    #[UseMiddleware(ApiKeyMiddleware::class)]
    public function deleteById(int $id, UserRepository $users): Response
    {
        return $users->delete($id) ? Response::noContent() : Response::problem(404, 'User not found');
    }
}
