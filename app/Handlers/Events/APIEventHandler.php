<?php
/**
 * APIEventHandler.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Handlers\Events;


use Exception;
use FireflyIII\Mail\AccessTokenCreatedMail;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Laravel\Passport\Events\AccessTokenCreated;
use Laravel\Passport\Token;
use Log;
use Mail;
use Request;
use Session;

/**
 * Class APIEventHandler
 */
class APIEventHandler
{
    /**
     * @param AccessTokenCreated $event
     *
     * @return bool
     */
    public function accessTokenCreated(AccessTokenCreated $event): bool
    {
        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        $user       = $repository->findNull((int)$event->userId);
        if (null === $user) {
            Log::error('Access Token generated but no user associated.');

            return true;
        }

        $email     = $user->email;
        $ipAddress = Request::ip();

        Log::debug(sprintf('Now in APIEventHandler::accessTokenCreated. Email is %s, IP is %s', $email, $ipAddress));
        try {
            Log::debug('Trying to send message...');
            Mail::to($email)->send(new AccessTokenCreatedMail($email, $ipAddress));
            // @codeCoverageIgnoreStart
        } catch (Exception $e) {
            Log::debug('Send message failed! :(');
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            Session::flash('error', 'Possible email error: ' . $e->getMessage());
        }
        Log::debug('If no error above this line, message was sent.');

        // @codeCoverageIgnoreEnd
        return true;


    }

}