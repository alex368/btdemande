<?php

namespace App\Service;

class QuarterService
{
    public function getQuarter(\DateTimeInterface $date): string
    {
        $month = (int) $date->format('n'); // 1 à 12

        if ($month >= 1 && $month <= 3) {
            return 'T1';
        }
        if ($month >= 4 && $month <= 6) {
            return 'T2';
        }
        if ($month >= 7 && $month <= 9) {
            return 'T3';
        }

        return 'T4'; // 10, 11, 12
    }
}
