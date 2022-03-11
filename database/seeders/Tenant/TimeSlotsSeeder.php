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
        ];

        foreach ($timeSlotArr as $value) {
            $timeSlot = new TimeSlot();
            $timeSlot->start_time = date('H:i:s',strtotime($value['slot_start_time']));
            $timeSlot->end_time = date('H:i:s',strtotime($value['slot_end_time']));
            $timeSlot->save();
        }
    }
}
