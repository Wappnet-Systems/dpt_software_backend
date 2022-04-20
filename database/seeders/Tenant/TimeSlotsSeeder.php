<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use App\Models\Tenant\TimeSlot;

class TimeSlotsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TimeSlot::truncate();

        $timeSlotArr = [
            [
                'slot_start_time' => '00:00',
                'slot_end_time' => '01:00'
            ],
            [
                'slot_start_time' => '01:00',
                'slot_end_time' => '02:00'
            ],
            [
                'slot_start_time' => '02:00',
                'slot_end_time' => '03:00'
            ],
            [
                'slot_start_time' => '03:00',
                'slot_end_time' => '04:00'
            ],
            [
                'slot_start_time' => '04:00',
                'slot_end_time' => '05:00'
            ],
            [
                'slot_start_time' => '05:00',
                'slot_end_time' => '06:00'
            ],
            [
                'slot_start_time' => '06:00',
                'slot_end_time' => '07:00'
            ],
            [
                'slot_start_time' => '07:00',
                'slot_end_time' => '08:00'
            ],
            [
                'slot_start_time' => '08:00',
                'slot_end_time' => '09:00'
            ],
            [
                'slot_start_time' => '09:00',
                'slot_end_time' => '10:00'
            ],
            [
                'slot_start_time' => '10:00',
                'slot_end_time' => '11:00'
            ],
            [
                'slot_start_time' => '11:00',
                'slot_end_time' => '12:00'
            ],
            [
                'slot_start_time' => '12:00',
                'slot_end_time' => '13:00'
            ],
            [
                'slot_start_time' => '13:00',
                'slot_end_time' => '14:00'
            ],
            [
                'slot_start_time' => '14:00',
                'slot_end_time' => '15:00'
            ],
            [
                'slot_start_time' => '15:00',
                'slot_end_time' => '16:00'
            ],
            [
                'slot_start_time' => '16:00',
                'slot_end_time' => '17:00'
            ],
            [
                'slot_start_time' => '17:00',
                'slot_end_time' => '18:00'
            ],
            [
                'slot_start_time' => '18:00',
                'slot_end_time' => '19:00'
            ],
            [
                'slot_start_time' => '19:00',
                'slot_end_time' => '20:00'
            ],
            [
                'slot_start_time' => '20:00',
                'slot_end_time' => '21:00'
            ],
            [
                'slot_start_time' => '21:00',
                'slot_end_time' => '22:00'
            ],
            [
                'slot_start_time' => '22:00',
                'slot_end_time' => '23:00'
            ],
            [
                'slot_start_time' => '23:00',
                'slot_end_time' => '00:00'
            ],
        ];

        foreach ($timeSlotArr as $value) {
            $timeSlot = new TimeSlot();
            $timeSlot->start_time = date('H:i:s', strtotime($value['slot_start_time']));
            $timeSlot->end_time = date('H:i:s', strtotime($value['slot_end_time']));
            $timeSlot->save();
        }
    }
}
