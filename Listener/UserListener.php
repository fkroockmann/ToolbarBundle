<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Bundle\ToolbarBundle\Listener;

use BackBee\Event\Event;
use BackBee\Site\Site;
use BackBee\Security\User;

/*
 * Listener to theme events
 *
 * @category    BackBee
 * @package     BackBee\Event
 * @subpackage  Listener
 * @copyright   Lp digital system
 * @author      n.dufreche <nicolas.dufreche@lp-digital.fr>
 * @author      Mickaël Andrieu <mickael.andrieu@lp-digital.fr>
 */
class UserListener
{
    public static function onUserCreated(Event $event)
    {
        $application = $event->getDispatcher()->getApplication();
        $site = $application->getSite();
        $user = $event->getTarget();

        if ($user instanceof User && $site instanceof Site) {

            $mailer = $application->getMailer();

            if (null === $mailer) {
                return;
            }

            $body = $application->getRenderer()->partial(
                'Email/NewUser.twig',
                [
                    'user' => $user,
                    'site_name' => $site->getLabel(),
                    'home_link' => $site->getServerName()
                ]
            );

            $message = static::buildMessage(
                $application->getSecurityContext()->getToken()->getUser(),
                $user,
                $body,
                $application->getSite()
            );

            $mailer->send($message);
        }
    }

    public static function buildMessage($admin, $user, $body, $site)
    {
        $adminEmail = !is_null($admin->getEmail())
                        ? $admin->getEmail()
                        : 'administrator@' . $site->getServerName();

        $adminName = (is_null($admin->getFirstName()) && is_null($admin->getLastName()))
                        ? $site->getLabel()
                        : $admin->getFirstName() . ' ' . $admin->getLastName();

        $message = \Swift_Message::newInstance();
        $message->setSender($adminEmail, $adminName);
        $message->addFrom($adminEmail, $adminName);

        $userName = (is_null($user->getFirstName()) && is_null($user->getLastName()))
                        ? $user->getLogin()
                        : $user->getFirstName() . ' ' . $user->getLastName();

        $message->addTo($user->getEmail(), $userName);

        $message->setSubject('Account creation on ' . $site->getLabel());
        $message->setBody($body, 'text/html', 'UTF-8');

        return $message;
    }
}