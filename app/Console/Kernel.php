<?php

namespace App\Console;
use App\Http\Controllers\AssistController;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            $controller = new AssistController();
            $controller->ReplyAssistAut();
        })->everyTenMinutes()->between('09:00', '10:30')->name("Replicacion de asistencia cada 5 min");//Respaldo solo de el ejercico actual

        $schedule->call(function () {
            $controller = new AssistController();
            $controller->ReplyAssistAut();
        })->everyTwoHours($minutes = 0)->between('10:30', '20:30')->name("Replicacion de asistencia cada 2 horas");//Respaldo solo de el ejercico actual
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    // protected function commands()
    // {
    //     $this->load(__DIR__.'/Commands');

    //     require base_path('routes/console.php');
    // }
}
