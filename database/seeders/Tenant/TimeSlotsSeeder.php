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
        $timeSlotArr = [
            [
                'slot_start_time' => '00:00',
                'slot_end_time' => '00:15'
            ],
            [
                'slot_start_time' => '00:15',
                'slot_end_time' => '00:30'
            ],
            [
                'slot_start_time' => '00:30',
                'slot_end_time' => '00:45'
            ],
            [
                'slot_start_time' => '00:45',
                'slot_end_time' => '01:00'
            ],
            [
                'slot_start_time' => '01:00',
                'slot_end_time' => '01:15'
            ],
            [
                'slot_start_time' => '01:15',
                'slot_end_time' => '01:30'
            ],
            [
                'slot_start_time' => '01:30',
                'slot_end_time' => '01:45'
            ],
            [
                'slot_start_time' => '01:45',
                'slot_end_time' => '02:00'
            ],
            [
                'slot_start_time' => '02:00',
                'slot_end_time' => '02:15'
            ],
            [
                'slot_start_time' => '02:15',
                'slot_end_time' => '02:30'
            ],
            [
                'slot_start_time' => '02:30',
                'slot_end_time' => '02:45'
            ],
            [
                'slot_start_time' => '02:45',
                'slot_end_time' => '03:00'
            ],
            [
                'slot_start_time' => '03:00',
                'slot_end_time' => '03:15'
            ],
            [
                'slot_start_time' => '03:15',
                'slot_end_time' => '03:30'
            ],
            [
                'slot_start_time' => '03:30',
                'slot_end_time' => '03:45'
            ],
            [
                'slot_start_time' => '03:45',
                'slot_end_time' => '04:00'
            ],
            [
                'slot_start_time' => '04:00',
                'slot_end_time' => '04:15'
            ],
            [
                'slot_start_time' => '04:15',
                'slot_end_time' => '04:30'
            ],
            [
                'slot_start_time' => '04:30',
                'slot_end_time' => '04:45'
            ],
            [
                'slot_start_time' => '04:45',
                'slot_end_time' => '05:00'
            ],
            [
                'slot_start_time' => '05:00',
                'slot_end_time' => '05:15'
            ],
            [
                'slot_start_time' => '05:15',
                'slot_end_time' => '05:30'
            ],
            [
                'slot_start_time' => '05:30',
                'slot_end_time' => '05:45'
            ],
            [
                'slot_start_time' => '05:45',
                'slot_end_time' => '06:00'
            ],
            [
                'slot_start_time' => '06:00',
                'slot_end_time' => '06:15'
            ],
            [
                'slot_start_time' => '06:15',
                'slot_end_time' => '06:30'
            ],
            [
                'slot_start_time' => '06:30',
                'slot_end_time' => '06:45'
            ],
            [
                'slot_start_time' => '06:45',
                'slot_end_time' => '07:00'
            ],
            [
                'slot_start_time' => '07:00',
                'slot_end_time' => '07:15'
            ],
            [
                'slot_start_time' => '07:15',
                'slot_end_time' => '07:30'
            ],
            [
                'slot_start_time' => '07:30',
                'slot_end_time' => '07:45'
            ],
            [
                'slot_start_time' => '07:45',
                'slot_end_time' => '08:00'
            ],
            [
                'slot_start_time' => '08:00',
                'slot_end_time' => '08:15'
            ],
            [
                'slot_start_time' => '08:15',
                'slot_end_time' => '08:30'
            ],
            [
                'slot_start_time' => '08:30',
                'slot_end_time' => '08:45'
            ],
            [
                'slot_start_time' => '08:45',
                'slot_end_time' => '09:00'
            ],
            [
                'slot_start_time' => '09:00',
                'slot_end_time' => '09:15'
            ],
            [
                'slot_start_time' => '09:15',
                'slot_end_time' => '09:30'
            ],
            [
                'slot_start_time' => '09:30',
                'slot_end_time' => '09:45'
            ],
            [
                'slot_start_time' => '09:45',
                'slot_end_time' => '10:00'
            ],
            [
                'slot_start_time' => '10:00',
                'slot_end_time' => '10:15'
            ],
            [
                'slot_start_time' => '10:15',
                'slot_end_time' => '10:30'
            ],
            [
                'slot_start_time' => '10:30',
                'slot_end_time' => '10:45'
            ],
            [
                'slot_start_time' => '10:45',
                'slot_end_time' => '11:00'
            ],
            [
                'slot_start_time' => '11:00',
                'slot_end_time' => '11:15'
            ],
            [
                'slot_start_time' => '11:15',
                'slot_end_time' => '11:30'
            ],
            [
                'slot_start_time' => '11:30',
                'slot_end_time' => '11:45'
            ],
            [
                'slot_start_time' => '11:45',
                'slot_end_time' => '12:00'
            ],
            [
                'slot_start_time' => '12:00',
                'slot_end_time' => '12:15'
            ],
            [
                'slot_start_time' => '12:15',
                'slot_end_time' => '12:30'
            ],
            [
                'slot_start_time' => '12:30',
                'slot_end_time' => '12:45'
            ],
            [
                'slot_start_time' => '12:45',
                'slot_end_time' => '13:00'
            ],
            [
                'slot_start_time' => '13:00',
                'slot_end_time' => '13:15'
            ],
            [
                'slot_start_time' => '13:15',
                'slot_end_time' => '13:30'
            ],
            [
                'slot_start_time' => '13:00',
                'slot_end_time' => '13:45'
            ],
            [
                'slot_start_time' => '13:45',
                'slot_end_time' => '14:00'
            ],
            [
                'slot_start_time' => '14:00',
                'slot_end_time' => '14:15'
            ],
            [
                'slot_start_time' => '14:15',
                'slot_end_time' => '14:30'
            ],
            [
                'slot_start_time' => '14:00',
                'slot_end_time' => '14:45'
            ],
            [
                'slot_start_time' => '14:45',
                'slot_end_time' => '15:00'
            ],
            [
                'slot_start_time' => '15:00',
                'slot_end_time' => '15:15'
            ],
            [
                'slot_start_time' => '15:15',
                'slot_end_time' => '15:30'
            ],
            [
                'slot_start_time' => '15:00',
                'slot_end_time' => '15:45'
            ],
            [
                'slot_start_time' => '15:45',
                'slot_end_time' => '16:00'
            ],
            [
                'slot_start_time' => '16:00',
                'slot_end_time' => '16:15'
            ],
            [
                'slot_start_time' => '16:15',
                'slot_end_time' => '16:30'
            ],
            [
                'slot_start_time' => '16:00',
                'slot_end_time' => '16:45'
            ],
            [
                'slot_start_time' => '16:45',
                'slot_end_time' => '17:00'
            ],
            [
                'slot_start_time' => '17:00',
                'slot_end_time' => '17:15'
            ],
            [
                'slot_start_time' => '17:15',
                'slot_end_time' => '17:30'
            ],
            [
                'slot_start_time' => '17:00',
                'slot_end_time' => '17:45'
            ],
            [
                'slot_start_time' => '17:45',
                'slot_end_time' => '18:00'
            ],
            [
                'slot_start_time' => '18:00',
                'slot_end_time' => '18:15'
            ],
            [
                'slot_start_time' => '18:15',
                'slot_end_time' => '18:30'
            ],
            [
                'slot_start_time' => '18:00',
                'slot_end_time' => '18:45'
            ],
            [
                'slot_start_time' => '18:45',
                'slot_end_time' => '19:00'
            ],
            [
                'slot_start_time' => '19:00',
                'slot_end_time' => '19:15'
            ],
            [
                'slot_start_time' => '19:15',
                'slot_end_time' => '19:30'
            ],
            [
                'slot_start_time' => '19:00',
                'slot_end_time' => '19:45'
            ],
            [
                'slot_start_time' => '19:45',
                'slot_end_time' => '20:00'
            ],
            [
                'slot_start_time' => '20:00',
                'slot_end_time' => '20:15'
            ],
            [
                'slot_start_time' => '20:15',
                'slot_end_time' => '20:30'
            ],
            [
                'slot_start_time' => '20:00',
                'slot_end_time' => '20:45'
            ],
            [
                'slot_start_time' => '20:45',
                'slot_end_time' => '21:00'
            ],
            [
                'slot_start_time' => '21:00',
                'slot_end_time' => '21:15'
            ],
            [
                'slot_start_time' => '21:15',
                'slot_end_time' => '21:30'
            ],
            [
                'slot_start_time' => '21:00',
                'slot_end_time' => '21:45'
            ],
            [
                'slot_start_time' => '21:45',
                'slot_end_time' => '22:00'
            ],
            [
                'slot_start_time' => '22:00',
                'slot_end_time' => '22:15'
            ],
            [
                'slot_start_time' => '22:15',
                'slot_end_time' => '22:30'
            ],
            [
                'slot_start_time' => '22:00',
                'slot_end_time' => '22:45'
            ],
            [
                'slot_start_time' => '22:45',
                'slot_end_time' => '23:00'
            ],
            [
                'slot_start_time' => '23:00',
                'slot_end_time' => '23:15'
            ],
            [
                'slot_start_time' => '23:15',
                'slot_end_time' => '23:30'
            ],
            [
                'slot_start_time' => '23:00',
                'slot_end_time' => '23:45'
            ],
            [
                'slot_start_time' => '23:45',
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
