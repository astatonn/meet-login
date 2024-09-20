<?php

namespace App\Log;

use Monolog\Logger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Monolog\Handler\AbstractProcessingHandler;

class RoomLogger extends AbstractProcessingHandler
{
    public $table;
    public function __construct($level = Logger::INFO, $bubble = true)
    {
        $this->table = 'penso_meet_logs';
        parent::__construct($level, $bubble);
    }

    /**
     * @param  array $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        $logger = new Logger("RoomLogger");
        return $logger->pushHandler(new RoomLogger());
    }


    public function write(array $record): void
    {
        $data = array(
            'message'       => $record['message'],
            'context'       => json_encode($record['context']),
            'level'         => $record['level'],
            'level_name'    => $record['level_name'],
            'channel'       => $record['channel'],
            'record_datetime' => $record['datetime']->format('Y-m-d H:i:s'),
            'extra'         => json_encode($record['extra']),
            'formatted'     => $record['formatted'],
            'remote_addr'   => $_SERVER['REMOTE_ADDR'],
            'user_agent'    => $_SERVER['HTTP_USER_AGENT'],
        );

            DB::table($this->table)->insert($data);
    }
}
