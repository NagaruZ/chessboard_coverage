<?php
/**
 * Created by PhpStorm.
 * User: Nagisa
 * Date: 2018/12/23
 * Time: 0:32
 */

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class PerformanceEvaluation extends Command
{
    protected $board;

    protected $piece_id = 0;

    protected $pieces;
    protected $result;
    protected function __get_pattern_recursive_no_res__($size, $board, $occupied_block)
    {
        if($size == 1)
            return;
        $t = $this->piece_id++;
        $sub_board_size = $size/2;

        // if occupied block is in the top left part of the chessboard
        if($occupied_block['row'] < $board['row'] + $sub_board_size && $occupied_block['col'] < $board['col'] + $sub_board_size)
        {
            $this->__get_pattern_recursive_no_res__($sub_board_size, $board, $occupied_block); // then recursively process the top left sub chessboard
        }
        else
        {
            // specify the bottom right corner block to be the new occupied block in the sub chessboard
            $new_occupied_block = [
                'row' => $board['row'] + $sub_board_size - 1,
                'col' => $board['col'] + $sub_board_size - 1
            ];
            $this->board[$new_occupied_block['row']][$new_occupied_block['col']] = $t;
            $this->__get_pattern_recursive_no_res__($sub_board_size, $board, $new_occupied_block);
        }

        // if occupied block is in the top right part of the chessboard
        if($occupied_block['row'] < $board['row'] + $sub_board_size && $occupied_block['col'] >= $board['col'] + $sub_board_size)
        {
            $new_board_pos['row'] = $board['row'];
            $new_board_pos['col'] = $board['col'] + $sub_board_size;
            $this->__get_pattern_recursive_no_res__($sub_board_size, $new_board_pos, $occupied_block); // then recursively process the top right sub chessboard
        }
        else
        {
            $new_board_pos['row'] = $board['row'];
            $new_board_pos['col'] = $board['col'] + $sub_board_size;
            // specify the bottom left corner block to be the new occupied block in the sub chessboard
            $new_occupied_block = [
                'row' => $board['row'] + $sub_board_size - 1,
                'col' => $board['col'] + $sub_board_size
            ];
            $this->board[$new_occupied_block['row']][$new_occupied_block['col']] = $t;
            $this->__get_pattern_recursive_no_res__($sub_board_size, $new_board_pos, $new_occupied_block);
        }

        // if occupied block is in the bottom left part of the chessboard
        if($occupied_block['row'] >= $board['row'] + $sub_board_size && $occupied_block['col'] < $board['col'] + $sub_board_size)
        {
            $new_board_pos['row'] = $board['row'] + $sub_board_size;
            $new_board_pos['col'] = $board['col'];
            $this->__get_pattern_recursive_no_res__($sub_board_size, $new_board_pos, $occupied_block); // then recursively process the bottom left sub chessboard
        }
        else
        {
            $new_board_pos['row'] = $board['row'] + $sub_board_size;
            $new_board_pos['col'] = $board['col'];
            // specify the top right corner block to be the new occupied block in the sub chessboard
            $new_occupied_block = [
                'row' => $board['row'] + $sub_board_size,
                'col' => $board['col'] + $sub_board_size - 1
            ];
            $this->board[$new_occupied_block['row']][$new_occupied_block['col']] = $t;
            $this->__get_pattern_recursive_no_res__($sub_board_size, $new_board_pos, $new_occupied_block);
        }

        // if occupied block is in the bottom right part of the chessboard
        if($occupied_block['row'] >= $board['row'] + $sub_board_size && $occupied_block['col'] >= $board['col'] + $sub_board_size)
        {
            $new_board_pos['row'] = $board['row'] + $sub_board_size;
            $new_board_pos['col'] = $board['col'] + $sub_board_size;
            $this->__get_pattern_recursive_no_res__($sub_board_size, $new_board_pos, $occupied_block); // then recursively process the bottom right sub chessboard
        }
        else
        {
            $new_board_pos['row'] = $board['row'] + $sub_board_size;
            $new_board_pos['col'] = $board['col'] + $sub_board_size;
            // specify the top left corner block to be the new occupied block in the sub chessboard
            $new_occupied_block = [
                'row' => $board['row'] + $sub_board_size,
                'col' => $board['col'] + $sub_board_size
            ];
            $this->board[$new_occupied_block['row']][$new_occupied_block['col']] = $t;
            $this->__get_pattern_recursive_no_res__($sub_board_size, $new_board_pos, $new_occupied_block);
        }
    }

    protected function configure()
    {
        $this->setName("performance_evaluation");
    }

    protected function execute(Input $input, Output $output)
    {
        $attempt_num = 5;
        for($t = 1; $t <= $attempt_num; $t++)
        {
            $n = 2;
            $output->writeln("Attempt $t");
            unset($this->board);
            while($n <= 1<<15)
            {
                for($i = 0; $i<$n; $i++)
                {
                    for($j = 0; $j<$n; $j++)
                    {
                        $this->board[$i][$j] = -1;
                    }
                }
                $initial_chessboard = [
                    'row' => 0,
                    'col' => 0
                ];
                $initial_occupied_block = [
                    'row' => rand(0,$n - 1),
                    'col' => rand(0,$n - 1)
                ];
                $start_time = microtime(true);
                $this->__get_pattern_recursive_no_res__($n, $initial_chessboard, $initial_occupied_block);
                $end_time = microtime(true);
                $duration = ($end_time - $start_time) * 1000;
                Db::table('performance')
                    ->insert(['n' => $n, 'duration' => $duration, 'time' => (new \Datetime)->format('Y-m-d H:i:s')]);
                $n *= 2;
            }
        }
        $output->writeln("Finished.");
    }

}