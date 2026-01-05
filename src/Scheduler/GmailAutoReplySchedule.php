<?php
// src/Scheduler/GmailAutoReplySchedule.php

namespace App\Scheduler;

use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\Trigger\CronExpressionTrigger;

#[AsSchedule]
class GmailAutoReplySchedule implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(
                new CronExpressionTrigger('*/10 * * * *'), // Toutes les 10 minutes
                ['command' => 'gmail:auto-reply']
            );
    }
}
