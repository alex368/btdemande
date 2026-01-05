<?php

namespace App\Schedule;

use Cron\CronExpression;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Message\CommandMessage;
use Symfony\Component\Scheduler\Trigger\CronExpressionTrigger;

#[AsSchedule]
class GmailAutoReplySchedule implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        $trigger = new CronExpressionTrigger(
            CronExpression::factory('*/10 * * * *')
        );

        $message = new RecurringMessage(
            new CommandMessage('gmail:auto-reply'),
            $trigger
        );

        return (new Schedule())->add($message);
    }
}
